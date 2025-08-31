<?php
// app/middleware/AjaxMiddleware.php

class AjaxMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
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