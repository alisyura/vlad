<?php
// app/middleware/CsrfMiddleware.php

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        // Методы, которые требуют CSRF-защиты
        $unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        // Проверяем, что текущий метод запроса требует защиты
        if (!in_array(strtoupper($_SERVER['REQUEST_METHOD']), $unsafeMethods)) {
            return true;
        }

        $token = '';

        // 1. Сначала ищем токен в заголовке X-CSRF-TOKEN (для AJAX)
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // 2. Если токена в заголовке нет, ищем его в теле запроса (для обычных форм)
        // Для PUT/PATCH/DELETE токен часто передается в теле запроса, как и для POST.
        if (empty($token) && isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }

        // Если токена нет нигде, или он невалидный, прерываем выполнение
        if (empty($token) || !CSRF::validateToken($token)) {
            http_response_code(403);
            
            // Если это AJAX-запрос, возвращаем JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            } else {
                // Иначе делаем редирект или показываем страницу с ошибкой
                header("Location: /error?code=403");
            }
            exit;
        }

        return true;
    }
}