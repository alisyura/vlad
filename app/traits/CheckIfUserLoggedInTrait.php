<?php

// app/traits/CheckIfUserLoggedInTrait.php

trait CheckIfUserLoggedInTrait
{
    private function checkIfUserLoggedIn()
    {
        if (!Auth::check()) {
            // Проверяем, является ли запрос AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                // Если это AJAX, возвращаем JSON-ошибку 401
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
                exit;
            } else {
                // Если это обычный запрос, делаем редирект
                $adminRoute = Config::get('admin.AdminRoute');
                header("Location: /$adminRoute/login");
                exit;
            }
        }
    }
}