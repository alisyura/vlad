<?php
// app/controllers/AdminLoginController.php

class AdminLoginController extends BaseAdminController
{
    private AuthService $authService;

    public function __construct(Request $request, View $view, 
        AuthService $authService, ResponseFactory $responseFactory)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->authService = $authService;
    }

    public function login(): Response {
        if ($this->getRequest()->getMethod() === 'POST') {
            // --- Проверка и обработка POST ---
            $token = $this->getRequest()->post('csrf_token') ?? '';
            if (!CSRF::validateToken($token)) {
                // После неудачной проверки нужно обновить токен
                CSRF::refreshToken(); // Можно добавить
                $error='Ошибка CSRF-токена. Попробуйте ещё раз.';

                return $this->renderLogin(['error' => $error]);
            }

            if ($this->authService->login(
                $this->getRequest()->post('login'), $this->getRequest()->post('password'))) {
                 // После успешного логина обновляем токен (хорошая практика)
                CSRF::refreshToken();
                $adminRoute = Config::get('admin.AdminRoute');

                return $this->redirect("/$adminRoute/dashboard");
            }
            $error = 'Неверный логин или пароль';
            // Если логин неудачен, токен остаётся тем же, что и в форме
        }
        elseif (($this->getRequest()->getMethod() === 'GET') && ($this->authService->check())) {
            CSRF::refreshToken();
            $adminRoute = Config::get('admin.AdminRoute');

            return $this->redirect("/$adminRoute/dashboard");
        }

        // --- Отображение формы GET или повторный показ после ошибки ---
        // Генерируем (или получаем существующий) токен перед отображением формы
        // Это гарантирует, что в скрытом поле и в куке будут актуальные значения
        CSRF::generateToken(); // Или просто CSRF::getToken(), если generateToken внутри проверит существование

        $data=[];
        if (isset($error) && !empty($error))
        {
            $data = ['error' => $error];
        }
        return $this->renderLogin($data);
    }

    public function logout() {
        $this->authService->logout();
        // После логаута тоже стоит обновить токен или очистить его
        // CSRF::refreshToken(); // Можно добавить
        $adminRoute = Config::get('admin.AdminRoute');

        return $this->redirect("/$adminRoute/login");
    }
}