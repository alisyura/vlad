<?php
// app/controllers/AdminLoginController.php

class AdminLoginController extends BaseController
{
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- Проверка и обработка POST ---
            $token = $_POST['csrf_token'] ?? '';
            if (!CSRF::validateToken($token)) {
                // После неудачной проверки желательно обновить токен
                CSRF::refreshToken(); // Можно добавить
                $error='Ошибка CSRF-токена. Попробуйте ещё раз.';
                $this->view->renderLogin(['error' => $error]);
                return;
            }

            if (Auth::login($_POST['login'], $_POST['password'])) {
                 // После успешного логина обновляем токен (хорошая практика)
                CSRF::refreshToken();
                $adminRoute = Config::get('admin.AdminRoute');
                header("Location: /$adminRoute/dashboard");
                exit;
            }
            $error = 'Неверный логин или пароль';
            // Если логин неудачен, токен остаётся тем же, что и в форме
        }
        elseif (($_SERVER['REQUEST_METHOD'] === 'GET') && (Auth::check())) {
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
        Auth::logout();
        // После логаута тоже стоит обновить токен или очистить его
        // CSRF::refreshToken(); // Можно добавить
        $adminRoute = Config::get('admin.AdminRoute');
        header("Location: /$adminRoute/login");
    }
}