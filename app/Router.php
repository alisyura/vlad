<?php

class Router {
    use ShowClientErrorViewTrait;

    private $routes = [];
    
    public function addRoute($pattern, $handler, $middlewares = [], array $options = []) {
        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares, // Добавляем поддержку списка middleware
            'method'     => $options['method'] ?? 'GET', // По умолчанию GET, если не указано
        ];
    }
    
    // public function dispatch(Request $request, ?ViewAdmin $viewAdmin) {
    public function dispatch(Container $container) {
        $request = $container->make(Request::class);
        $uri = strtok($request->getUri(), '?');
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            $handler = $route['handler'];
            $middlewares = $route['middlewares']; // Получаем список middleware для этого маршрута

            if (preg_match("#^$pattern$#", $uri, $matches)) {
                $requestMethod = strtoupper($request->getMethod());
                if ($requestMethod === 'OPTIONS') {
                    http_response_code(204); // No Content - успешный ответ без тела
                    exit;
                }
                // Проверяем, соответствует ли метод запроса ожидаемому
                if ($route['method'] !== 'ANY')
                {
                    // Разбиваем строку и фильтруем пустые значения
                    $allowedMethods = array_filter(array_map('trim', explode(',', $route['method'])));
                    // Преобразуем все в верхний регистр
                    $allowedMethods = array_map('strtoupper', $allowedMethods);
                        
                    if (!in_array($requestMethod, $allowedMethods)) {
                        http_response_code(405); // Method Not Allowed
                        exit;
                    }
                }

                array_shift($matches);

                // Выполняем middleware ДО основного обработчика
                foreach ($middlewares as $middleware) {
                    $params = [];

                    // Проверяем, есть ли двоеточие в имени middleware
                    if (is_string($middleware) && strpos($middleware, ':') !== false) {
                        // Разделяем строку на имя класса и параметры
                        [$middleware, $paramString] = explode(':', $middleware, 2);
                        
                        // Преобразуем строку параметров в массив.
                        // Если параметров несколько, их можно разделять запятой, например 'param1,param2'
    
                        // Преобразуем всю строку в нижний регистр
                        $paramString = strtolower($paramString);

                        // Разделяем строку на массив по запятой
                        $params = explode(',', $paramString);

                        // Используем array_map для удаления пробелов и пустых элементов
                        // array_filter удалит пустые значения (например, из-за лишних запятых)
                        $params = array_filter(array_map('trim', $params));
                    }
                    
                     // Проверяем, является ли $middleware строкой (именем класса) или объектом/замыканием
                    if (is_string($middleware) && class_exists($middleware)) {
                        $middlewareInstance = $container->make($middleware);
                        // Предполагаем, что у middleware есть метод handle, возвращающий true/false или выбрасывающий исключение
                        if ($middlewareInstance instanceof MiddlewareInterface) {
                            $paramToPass = (!empty($params) && !($paramString === '')) ? $params : null;
                            $result = $middlewareInstance->handle($paramToPass);
                            // Если middleware вернул false или выбросил исключение (например, редирект), останавливаем выполнение
                            if ($result === false) {
                                return; // Или можно бросить исключение
                            }
                        }
                    } elseif (is_callable($middleware)) {
                         // Поддержка анонимных функций как middleware
                        $result = call_user_func($middleware);
                         if ($result === false) {
                            return;
                        }
                    }
                    // Можно добавить другие типы middleware по необходимости
                }

                // Если все middleware прошли успешно, вызываем основной обработчик
                if (//str_starts_with(strtolower($uri), '/sitemap') || 
                    str_starts_with(strtolower($uri), strtolower('/'.Config::get('admin.AdminRoute')))) {
                    // это временно, пока не внедрен service container везде
                    array_unshift($matches, $request); // вставляем первым параметром
                    $viewAdmin = $container->make(View::class);
                    array_splice($matches, 1, 0, [$viewAdmin]);
                }
                else {
                    array_unshift($matches, $container); // вставляем первым параметром
                }
                
                call_user_func_array($handler, $matches);
                return;
            }
        }

        $view = $container->make(View::class);
        $this->renderErrorView($view, 'Страница не найдена', '', 404);
        return;
    }
}
