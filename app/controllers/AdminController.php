<?php
// app/controllers/AdminController.php

class AdminController {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- Проверка и обработка POST ---
            $token = $_POST['csrf_token'] ?? '';
            if (!CSRF::validateToken($token)) {
                // После неудачной проверки желательно обновить токен
                CSRF::refreshToken(); // Можно добавить
                $error='Ошибка CSRF-токена. Попробуйте ещё раз.';
                require '../app/views/admin/login.php';
                return;
            }

            if (Auth::login($_POST['login'], $_POST['password'])) {
                 // После успешного логина обновляем токен (хорошая практика)
                CSRF::refreshToken();
                $adminRoute = Config::getAdminCfg('AdminRoute');
                header("Location: /$adminRoute/dashboard");
                exit;
            }
            $error = 'Неверный логин или пароль';
            // Если логин неудачен, токен остаётся тем же, что и в форме
        }

        // --- Отображение формы GET или повторный показ после ошибки ---
        // Генерируем (или получаем существующий) токен перед отображением формы
        // Это гарантирует, что в скрытом поле и в куке будут актуальные значения
        CSRF::generateToken(); // Или просто CSRF::getToken(), если generateToken внутри проверит существование

        require '../app/views/admin/login.php';
    }

    public function dashboard() {
        if (!Auth::check()) {
            $adminRoute = Config::getAdminCfg('AdminRoute');
            header("Location: /$adminRoute/login");
            exit;
        }

        // Получаем данные для dashboard
        $data = [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'posts_count' => 4,//$this->getPostCount(),
            'pages_count' => 5,//$this->getPageCount(),
            'users_count' => 6,//$this->getUserCount(),
            'recent_activities' => 7//$this->getRecentActivities()
        ];
        
        $content = View::render('../app/views/admin/dashboard.php', $data);
        //View::render('../app/views/admin/layout.php', array_merge($data, ['content' => $content]));


        // Здесь загружаем данные для админ-панели
        require '../app/views/admin/admin_layout.php';
    }

    public function logout() {
        Auth::logout();
        // После логаута тоже стоит обновить токен или очистить его
        // CSRF::refreshToken(); // Можно добавить
        $adminRoute = Config::getAdminCfg('AdminRoute');
        header("Location: /$adminRoute/login");
    }
}
