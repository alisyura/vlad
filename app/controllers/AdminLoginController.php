<?php
// app/controllers/AdminLoginController.php

class AdminLoginController extends BaseController
{
    private AuthService $authService;

    public function __construct(Request $request, View $view, AuthService $authService)
    {
        parent::__construct($request, $view);
        $this->authService = $authService;
    }

    public function login() {
        if ($this->request->getMethod() === 'POST') {
            // --- Проверка и обработка POST ---
            $token = $this->request->post('csrf_token') ?? '';
            if (!CSRF::validateToken($token)) {
                // После неудачной проверки желательно обновить токен
                CSRF::refreshToken(); // Можно добавить
                $error='Ошибка CSRF-токена. Попробуйте ещё раз.';
                $this->view->renderLogin(['error' => $error]);
                return;
            }

            if ($this->authService->login(
                $this->request->post('login'), $this->request->post('password'))) {
                 // После успешного логина обновляем токен (хорошая практика)
                CSRF::refreshToken();
                $adminRoute = Config::get('admin.AdminRoute');
                header("Location: /$adminRoute/dashboard");
                exit;
            }
            $error = 'Неверный логин или пароль';
            // Если логин неудачен, токен остаётся тем же, что и в форме
        }
        elseif (($this->request->getMethod() === 'GET') && ($this->authService->check())) {
            CSRF::refreshToken();
            $adminRoute = Config::get('admin.AdminRoute');
            header("Location: /$adminRoute/dashboard");
            exit;
        }

        // --- Отображение формы GET или повторный показ после ошибки ---
        // Генерируем (или получаем существующий) токен перед отображением формы
        // Это гарантирует, что в скрытом поле и в куке будут актуальные значения
        CSRF::generateToken(); // Или просто CSRF::getToken(), если generateToken внутри проверит существование

        $data=[];
        if (isset($error) && !empty($error))
        {
            $data = ['error' => 'Неверный логин или пароль'];
        }
        $this->view->renderLogin($data);
    }

    public function logout() {
        $this->authService->logout();
        // После логаута тоже стоит обновить токен или очистить его
        // CSRF::refreshToken(); // Можно добавить
        $adminRoute = Config::get('admin.AdminRoute');
        header("Location: /$adminRoute/login");
    }
}