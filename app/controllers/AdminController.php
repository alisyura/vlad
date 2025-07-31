<?php
// app/controllers/AdminController.php

class AdminController {

    private function checkIfUserLoggedIn()
    {
        if (!Auth::check()) {
            $adminRoute = Config::getAdminCfg('AdminRoute');
            header("Location: /$adminRoute/login");
            exit;
        }
    }

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
        $this->checkIfUserLoggedIn();

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

    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     */
    public function postsList($currentPage = 1) {
        $this->checkIfUserLoggedIn();

        try {
            $admPostsModel = new AdminPostsModel();

            // Определяем параметры пагинации
            $postsPerPage = Config::getAdminCfg('posts_per_page'); // Количество постов на страницу
            $currentPage = max(1, (int)$currentPage); // Убеждаемся, что страница не меньше 1
            $offset = ($currentPage - 1) * $postsPerPage; // Вычисляем смещение

            // Получаем общее количество постов
            $totalPosts = $admPostsModel->getTotalPostsCount();
            // Вычисляем общее количество страниц
            $totalPages = ceil($totalPosts / $postsPerPage);
            
            // Убеждаемся, что текущая страница не превышает общее количество
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $postsPerPage;

            // Получаем посты для текущей страницы
            $posts = $admPostsModel->getPosts($postsPerPage, $offset);
            
            // Обрабатываем каждый пост для форматирования и подготовки к выводу
            foreach ($posts as &$post) {
                $post['formatted_created_at'] = date('d.m.Y', strtotime($post['created_at']));
                $post['formatted_updated_at'] = date('d.m.Y', strtotime($post['updated_at']));

                // Собираем категории в строку HTML-ссылок
                $category_names = [];
                if (!empty($post['categories'])) {
                    foreach ($post['categories'] as $category) {
                        $category_names[] = '<a href="/cat/' . htmlspecialchars($category['url']) . '" target="_blank">' . htmlspecialchars($category['name']) . '</a>';
                    }
                }
                $post['category_names'] = !empty($category_names) ? implode(', ', $category_names) : '<span class="text-muted">—</span>';

                // Собираем теги в строку HTML-ссылок
                $tag_names = [];
                if (!empty($post['tags'])) {
                    foreach ($post['tags'] as $tag) {
                        $tag_names[] = '<a href="/tag/' . htmlspecialchars($tag['url']) . '" target="_blank">' . htmlspecialchars($tag['name']) . '</a>';
                    }
                }
                $post['tag_names'] = !empty($tag_names) ? implode(', ', $tag_names) : '<span class="text-muted">—</span>';

                // Определяем отображаемый статус
                switch($post['status'])
                {
                    case 'draft':
                        $post['display_status'] = '<span class="badge bg-warning text-dark">Черновик</span>';
                        break;
                    case 'published':
                        $post['display_status'] = '<span class="badge bg-success">Опубликовано</span>';
                        break;
                    case 'pending':
                        $post['display_status'] = '<span class="badge bg-info">Ожидание</span>'; // Используем bg-info
                        break;
                    case 'deleted':
                        $post['display_status'] = '<span class="badge bg-secondary">Удален</span>'; // Используем bg-secondary
                        break;
                    default:
                        $post['display_status'] = '<span class="badge bg-light-custom text-dark">Неизвестно</span>'; 
                        break;
                }
            }
            unset($post);

            $adminRoute = Config::getAdminCfg('AdminRoute');
            $user_name = '';
            if (isset($_SESSION['user_login'])) {
                $userModel = new UserModel();
                $user = $userModel->getUserByLogin($_SESSION['user_login']);
                $user_name = $user['name'] ?? 'Администратор';
            }

            // Генерируем массив ссылок для умной пагинации
            // Базовый URL для админки
            $basePageUrl = '/' . htmlspecialchars($adminRoute) . '/posts';
            $paginationLinks = generateSmartPaginationLinks($currentPage, $totalPages, $basePageUrl);

            $data = [
                'adminRoute' => $adminRoute,
                'user_name' => $user_name,
                'title' => 'Список постов',
                'active' => 'posts',
                'posts' => $posts,
                'pagination' => [ // Передаем данные для пагинации в представление
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages
                ],
                'pagination_links' => $paginationLinks,
                'base_page_url' => $basePageUrl
            ];
            
            $content = View::render('../app/views/admin/posts/list.php', $data);
            $route_path = 'posts-list';
            require '../app/views/admin/admin_layout.php';

        } catch (PDOException $e) {
            Logger::error("Database error in listPosts: " . $e->getMessage());
            $data = [
                'adminRoute' => Config::getAdminCfg('AdminRoute'),
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить посты. Пожалуйста, попробуйте позже.'
            ];
            $content = View::render('../app/views/admin/error_view.php', $data);
            require '../app/views/admin/admin_layout.php';
        } catch (Exception $e) {
            Logger::error("Error in listPosts: " . $e->getMessage());
            $data = [
                'adminRoute' => Config::getAdminCfg('AdminRoute'),
                'title' => 'Ошибка',
                'error_message' => 'Произошла непредвиденная ошибка.'
            ];
            $content = View::render('../app/views/admin/error_view.php', $data);
            require '../app/views/admin/admin_layout.php';
        }
    }
}
