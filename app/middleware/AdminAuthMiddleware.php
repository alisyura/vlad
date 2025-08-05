<?php

// app/middleware/AdminAuthMiddleware.php

class AdminAuthMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет авторизацию администратора.
     * @return bool True если авторизован, иначе выполнение скрипта прерывается.
     */
    public function handle(): bool
    {
        if (!\Auth::check()) {
            $adminRoute = Config::getAdminCfg('AdminRoute');

            // Определяем, является ли запрос AJAX по явному заголовку
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
                      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit; 
            } else {
                header("Location: /$adminRoute/login");
                exit; 
            }
        }
        return true;
    }
}