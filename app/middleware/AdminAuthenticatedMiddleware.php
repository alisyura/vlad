<?php

// app/middleware/AdminAuthenticatedMiddleware.php

class AdminAuthenticatedMiddleware implements MiddlewareInterface
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
     * Проверяет авторизацию администратора.
     * @return bool True если авторизован, иначе выполнение скрипта прерывается.
     */
    public function handle(?array $param = null): bool
    {
        // на будущее, когда переведу на сервис контейнер
        //private ResponseFactory $responseFactory;

        // public function __construct(ResponseFactory $responseFactory)
        // {
        //     $this->responseFactory = $responseFactory;
        // }
        
        if (!$this->authService->isUserAdmin()) {
            $adminRoute = Config::get('admin.AdminRoute');

            // Определяем, является ли запрос AJAX по явному заголовку
            $isAjax = $this->request->isAjax();
            
            if ($isAjax) {
                // return $this->responseFactory->createJsonResponse(
                //     ['success' => false, 'message' => 'Доступ запрещен'], 
                //     403
                // );

                $this->sendErrorJsonResponse('Доступ запрещен', 403);
                exit; 
            } else {
                if (!$this->authService->check()) {
                    // А. Сценарий 401: Пользователь НЕ вошел.
                    // Перенаправляем на форму входа.
                    header("HTTP/1.1 401 Unauthorized");
                    header("Location: /$adminRoute/login");

                    // return $this->responseFactory->createRedirectResponse(
                    //     "/{$adminRoute}/login", 
                    //     401 // Или 302, если не хотите явно указывать 401
                    // );
                } else {
                    // Б. Сценарий 403: Пользователь вошел, но НЕ админ.
                    // Перенаправляем на доступную ему страницу.
                    header("HTTP/1.1 403 Forbidden");
                    header("Location: /$adminRoute/dashboard");

                    // return $this->responseFactory->createRedirectResponse(
                    //     "/{$adminRoute}/dashboard", 
                    //     403 // Или 302
                    // );
                }

                exit; 
            }
        }
        return true;
    }
}