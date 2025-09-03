<?php

// app/middleware/AdminAuthenticatedMiddleware.php

class AdminAuthenticatedMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет авторизацию администратора.
     * @return bool True если авторизован, иначе выполнение скрипта прерывается.
     */
    public function handle(): bool
    {
        if (!\Auth::isUserAdmin()) {
            $adminRoute = Config::get('admin.AdminRoute');

            // Определяем, является ли запрос AJAX по явному заголовку
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
                      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
                exit; 
            } else {
                header("Location: /$adminRoute/login");
                exit; 
            }
        }
        return true;
    }
}