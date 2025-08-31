<?php

class Router {
    private $routes = [];
    
    public function addRoute($pattern, $handler, $middlewares = [], array $options = []) {
        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares, // Добавляем поддержку списка middleware
            'method'     => $options['method'] ?? 'GET', // По умолчанию GET, если не указано
        ];
    }
    
    public function dispatch($uri) {
        $uri = strtok($uri, '?');
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            $handler = $route['handler'];
            $middlewares = $route['middlewares']; // Получаем список middleware для этого маршрута

            if (preg_match("#^$pattern$#", $uri, $matches)) {
                $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
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
                     // Проверяем, является ли $middleware строкой (именем класса) или объектом/замыканием
                    if (is_string($middleware) && class_exists($middleware)) {
                        $middlewareInstance = new $middleware();
                        // Предполагаем, что у middleware есть метод handle, возвращающий true/false или выбрасывающий исключение
                        if (method_exists($middlewareInstance, 'handle')) {
                            $result = $middlewareInstance->handle();
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
                call_user_func_array($handler, $matches);
                return;
            }
        }

        // Если маршрут не найден
        header("HTTP/1.0 404 Not Found");
        $content = View::render('../app/views/errors/404.php', [
            'title' => '404'
        ]);
        require '../app/views/layout.php';
        return;
    }
}
