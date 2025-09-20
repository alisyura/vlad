<?php
// app/middleware/AjaxMiddleware.php

class AjaxMiddleware implements MiddlewareInterface
{
    use JsonResponseTrait;

    public function handle(?array $param = null): bool
    {
        // Если метод запроса верный, проверяем наличие AJAX-заголовка
        $http_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        if (empty($http_requested_with) || strtolower($http_requested_with) !== 'xmlhttprequest') {
            $this->sendErrorJsonResponse('Неверный формат запроса.', 403);
            exit;
        }
        
        return true;
    }
}