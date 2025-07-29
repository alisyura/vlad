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
        elseif (($_SERVER['REQUEST_METHOD'] === 'GET') && (Auth::check())) {
            CSRF::refreshToken();
            $adminRoute = Config::getAdminCfg('AdminRoute');
            header("Location: /$adminRoute/dashboard");
            exit;
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

        $dm = new DashboardModel();

        $adminRoute = Config::getAdminCfg('AdminRoute');
        $user = (new UserModel())->getUserByLogin($_SESSION['user_login']);
        $user_name = $user['name'];

        // Получаем данные для dashboard
        $data = [
            'admin_route' => $adminRoute,
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'posts_count' => $dm->getPostsCount(),
            'pages_count' => $dm->getPagesCount(),
            'users_count' => $dm->getUsersCount(),
            'recent_activities' => $dm->getRecentActivities()
        ];
        
        $content = View::render('../app/views/admin/dashboard.php', $data);

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

    private function getPostCount()
    {
        // Ваш код для получения количества постов
        return 42; // пример
    }

    private function getPageCount()
    {
        // Ваш код для получения количества страниц
        return 5; // пример
    }

    private function getUserCount()
    {
        // Ваш код для получения количества пользователей
        return 1; // пример
    }
}
