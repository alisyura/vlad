<?php
// app/middleware/AjaxMiddleware.php

class AjaxMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        // Эти методы требуют AJAX-формата, если они используются на этом маршруте
        $allowedAjaxMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

        // Если метод запроса не является одним из разрешённых, это ошибка
        if (!in_array($requestMethod, $allowedAjaxMethods)) {
            http_response_code(405); // Method Not Allowed
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Метод запроса не поддерживается на этом маршруте.']);
            exit;
        }

        // Если метод запроса верный, проверяем наличие AJAX-заголовка
        $http_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        if (empty($http_requested_with) || strtolower($http_requested_with) !== 'xmlhttprequest') {
            http_response_code(403); // Forbidden
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Неверный формат запроса.']);
            exit;
        }
        
        return true;
    }
}