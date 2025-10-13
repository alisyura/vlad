<?php

// app/middleware/UserAuthenticatedMiddleware.php

class UserAuthenticatedMiddleware implements MiddlewareInterface
{
    use JsonResponseTrait;

    private AuthService $authService;
    private Request $request;

    public function __construct(AuthService $authService, Request $request)
    {
        $this->authService = $authService;
        $this->request = $request;
    }
    
    /**
     * Проверяет авторизацию пользователя.
     * @return bool True если авторизован, иначе выполнение скрипта прерывается.
     */
    public function handle(?array $param = null): bool
    {
        if (!$this->authService->check()) {
            $adminRoute = Config::get('admin.AdminRoute');

            // Определяем, является ли запрос AJAX по явному заголовку
            $isAjax = $this->request->isAjax();
            
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