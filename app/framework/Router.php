<?php

// app/framework/Router.php
class Router {
    private $routes = [];
    private ResponseFactory $responseFactory;
    private ErrorResponseFactory $errorFactory;

    public function __construct(ResponseFactory $responseFactory, ErrorResponseFactory $errorFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->errorFactory = $errorFactory;
    }
    
    public function addRoute($pattern, $handler, $middlewares = [], array $options = []) {
        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares, // Добавляем поддержку списка middleware
            'method'     => $options['method'] ?? 'GET', // По умолчанию GET, если не указано
        ];
    }
    
    public function dispatch(Container $container) {
        $request = $container->make(Request::class);
        $uri = strtok($request->getUri(), '?');
        $requestMethod = strtoupper($request->getMethod());

        // временно, пока переводим все контроллеры на вывод через response
        ob_start();

        try {
            foreach ($this->routes as $route) {
                $pattern = $route['pattern'];
                $handler = $route['handler'];
                $middlewares = $route['middlewares']; // Получаем список middleware для этого маршрута

                if (preg_match("#^$pattern$#", $uri, $matches)) {
                    if ($requestMethod === 'OPTIONS') {
                        // No Content - успешный ответ без тела
                        $this->responseFactory->createEmptyResponse(204)->send();
                        return;
                    }
                    // Проверяем, соответствует ли метод запроса ожидаемому
                    if ($route['method'] !== 'ANY')
                    {
                        // Разбиваем строку и фильтруем пустые значения
                        $allowedMethods = array_filter(array_map('trim', explode(',', $route['method'])));
                        // Преобразуем все в верхний регистр
                        $allowedMethods = array_map('strtoupper', $allowedMethods);
                            
                        if (!in_array($requestMethod, $allowedMethods)) {
                            // Очищаем буфер, чтобы убрать любой нежелательный вывод 
                            // (например, от старого контроллера, который выбросил исключение).
                            ob_end_clean();
                            $this->errorFactory->createClientError(
                                    '405 Метод не разрешен', 
                                    'Метод запроса не поддерживается для данного ресурса.', 
                                    405
                                )->send();
                            return;
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
                            // Если параметров несколько, их нужно разделять запятой, например 'param1,param2'
        
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
                        // Сюда можно добавить другие типы middleware по необходимости
                    } // Конец цикла middleware

                    array_unshift($matches, $container); // вставляем первым параметром
                    
                    $response = call_user_func_array($handler, $matches);
                    if ($response instanceof Response) {
                        // ОЧИЩАЕМ буфер. Если контроллер вернул Response, 
                        // любой предыдущий вывод (от старого контроллера или middleware)
                        ob_end_clean();

                        $response->send(); // Отправляем Response
                        return;
                    }

                    // Response НЕ возвращен.
                    // Если сюда дошли, значит, контроллер отработал, не вернул Response и, 
                    // вероятно, уже вывел контент в буфер (старый механизм).
                    
                    // СЛИВАЕМ содержимое буфера в браузер и завершаем буферизацию.
                    ob_end_flush();
                    return;

                    // Если контроллер не вернул Response, это ошибка 500.
                    // Будет использовано, когда все контроллеры переведу на response
                    // throw new RouteException("Обработчик маршрута не вернул объект Response.");
                }
            }

            // Очищаем буфер, прежде чем отправить 404
            ob_end_clean();

            // 5. 404 Not Found (замена $this->showErrorView)
            $this->errorFactory->createClientError(
                    '404 Страница не найдена', 
                    'Запрошенный ресурс не найден на сервере.', 
                    404
                )->send();
            return;
        } catch (HttpException $e) { // потом перенести в ErrorHandler
            // Очищаем буфер, чтобы убрать любой нежелательный вывод 
            // (например, от старого контроллера, который выбросил исключение).
            ob_end_clean();

            // Глобальная обработка исключений (500 Internal Server Error)
            $this->errorFactory->createClientError(
                $e->getMessage(), 
                'Произошла непредвиденная ошибка.', 
                $e->getCode()
            )->send();
            return;
        } catch (Throwable $e) { // потом перенести в ErrorHandler
            // Очищаем буфер, чтобы убрать любой нежелательный вывод 
            // (например, от старого контроллера, который выбросил исключение).
            ob_end_clean();

            // Глобальная обработка исключений (500 Internal Server Error)
            $this->errorFactory->createClientError(
                'Ошибка сервера 500', 
                'Произошла внутренняя ошибка сервера.', 
                500
            )->send();
            return;
        }
    }
}
