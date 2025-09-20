<?php

// app/middleware/UserAuthenticatedMiddleware.php

class UserAuthenticatedMiddleware implements MiddlewareInterface
{
    use JsonResponseTrait;
    
    /**
     * Проверяет авторизацию администратора.
     * @return bool True если авторизован, иначе выполнение скрипта прерывается.
     */
    public function handle(?array $param = null): bool
    {
        if (!\Auth::check()) {
            $adminRoute = Config::get('admin.AdminRoute');

            // Определяем, является ли запрос AJAX по явному заголовку
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
                      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                $this->sendErrorJsonResponse('Доступ запрещен', 401);
                exit; 
            } else {
                header("Location: /$adminRoute/login");
                exit; 
            }
        }
        return true;
    }
}