<?php
// app/controllers/AdminController.php

class AdminController {
    private function checkIfUserLoggedIn()
    {
        if (!Auth::check()) {
            $adminRoute = Config::get('admin.AdminRoute');
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

        require '../app/views/admin/login.php';
    }

    public function dashboard() {
        $this->checkIfUserLoggedIn();

        $dm = new DashboardModel();

        $adminRoute = Config::get('admin.AdminRoute');
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
        $adminRoute = Config::get('admin.AdminRoute');
        header("Location: /$adminRoute/login");
    }

    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     */
    public function postsList($currentPage = 1)
    {
        $this->processArticlesList($currentPage, 'post');
    }

    /**
     * Отображает список страниц в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     */
    public function pagesList($currentPage = 1)
    {
        $this->processArticlesList($currentPage, 'page');
    }

    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     * @param string $articleType Тип статьи. post или page
     */
    private function processArticlesList($currentPage = 1, $articleType = 'post') {
        $this->checkIfUserLoggedIn();

        try {
            $admPostsModel = new AdminPostsModel();

            // --- Получение и валидация параметров сортировки ---
            $sortBy = $_GET['sort'] ?? 'created_at';
            $sortOrder = $_GET['order'] ?? 'DESC';

            $allowedSorts = ['id', 'title', 'author', 'categories', 'tags', 'status', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at';
            }

            $allowedOrders = ['ASC', 'DESC'];
            $sortOrder = strtoupper($sortOrder);
            if (!in_array($sortOrder, $allowedOrders)) {
                $sortOrder = 'DESC';
            }
            // --- Конец обработки параметров сортировки ---

            // Определяем параметры пагинации
            $postsPerPage = Config::get('admin.posts_per_page'); // Количество постов на страницу
            $currentPage = max(1, (int)$currentPage); // Убеждаемся, что страница не меньше 1
            $offset = ($currentPage - 1) * $postsPerPage; // Вычисляем смещение

            // Получаем общее количество постов
            $totalPosts = $admPostsModel->getTotalPostsCount($articleType);
            // Вычисляем общее количество страниц
            $totalPages = ceil($totalPosts / $postsPerPage);
            
            // Убеждаемся, что текущая страница не превышает общее количество
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $postsPerPage;

            // Получаем посты для текущей страницы
            $posts = $admPostsModel->getPosts($articleType, $postsPerPage, $offset,
                $sortBy, $sortOrder);
            
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

            $adminRoute = Config::get('admin.AdminRoute');
            // Используется в admin_layout
            $user_name = Auth::getUserName();

            // Генерируем массив ссылок для умной пагинации
            // Базовый URL для админки
            $basePageUrl = '/' . htmlspecialchars($adminRoute) . "/{$articleType}s";
            $paginationLinks = generateSmartPaginationLinks($currentPage, $totalPages, $basePageUrl);

            $data = [
                'adminRoute' => $adminRoute,
               // 'user_name' => $user_name,
                'title' => 'Список постов',
                'active' => 'posts',
                'posts' => $posts,
                'pagination' => [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ],
                'pagination_links' => $paginationLinks,
                'base_page_url' => $basePageUrl,
                'current_sort_by' => $sortBy,
                'current_sort_order' => $sortOrder
            ];
            
            // Используется в admin_layout
            $content = View::render('../app/views/admin/posts/list.php', $data);
            $route_path = 'posts-list';
            require '../app/views/admin/admin_layout.php';

        } catch (PDOException $e) {
            Logger::error("Database error in listPosts: " . $e->getTraceAsString());
            $data = [
                'adminRoute' => Config::get('admin.AdminRoute'),
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить посты. Пожалуйста, попробуйте позже.'
            ];
            $content = View::render('../app/views/admin/error_view.php', $data);
            require '../app/views/admin/admin_layout.php';
        } catch (Exception $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $data = [
                'adminRoute' => Config::get('admin.AdminRoute'),
                'title' => 'Ошибка',
                'error_message' => 'Произошла непредвиденная ошибка.'
            ];
            $content = View::render('../app/views/admin/error_view.php', $data);
            require '../app/views/admin/admin_layout.php';
        }
    }

    public function createPost() {
        try {
            $this->checkIfUserLoggedIn();

            $adminPostsModel = new AdminPostsModel();
            $adminRoute = Config::get('admin.AdminRoute');
            
            $user_id = Auth::getUserId();
            $user_name = Auth::getUserName();

            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => 'post',
                'user_name' => $user_name,
                'title' => 'Создать новый пост',
                'active' => 'posts',
                'post' => null,
                'categories' => [],
                'tags' => [],
                'errors' => [],
                'is_new_post' => true
            ];
        
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['csrf_token'] ?? '';
                if (!CSRF::validateToken($token)) {
                    $data['errors'][] = 'Ошибка CSRF-токена. Попробуйте ещё раз.';
                    CSRF::refreshToken();
                } else {
                    $title = trim($_POST['title'] ?? '');
                    $content = $_POST['content'] ?? '';
                    $url = $this->sanitizeUrl($_POST['url'] ?? '');
                    $status = $_POST['status'] ?? 'draft';
                    $meta_title = trim($_POST['meta_title'] ?? '');
                    $meta_description = trim($_POST['meta_description'] ?? '');
                    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
                    $excerpt = trim($_POST['excerpt'] ?? '');
                    $selectedCategories = $_POST['categories'] ?? [];

                    // $tagsString = $_POST['tags'] ?? [];
                    $selectedTags = $_POST['tags'] ?? [];
                    $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

                    $thumbnailUrl = trim($_POST['post_image_url'] ?? ''); 

                    if (empty($title)) {
                        $data['errors'][] = 'Заголовок поста обязателен.';
                    }
                    if (empty($url)) {
                        $data['errors'][] = 'URL поста обязателен.';
                    } else if (!$adminPostsModel->isUrlUnique($url)) {
                         $data['errors'][] = 'Указанный URL уже занят.';
                    }

                    if (empty($data['errors'])) {
                        $postData = [
                            'user_id' => $user_id,
                            'article_type' => 'post',
                            'status' => $status,
                            'title' => $title,
                            'content' => $content,
                            'url' => $url,
                            'meta_title' => $meta_title,
                            'meta_description' => $meta_description,
                            'meta_keywords' => $meta_keywords,
                            'excerpt' => $excerpt,
                            'thumbnail_url' => $thumbnailUrl,
                        ];

                        $postId = $adminPostsModel->createPost($postData, $selectedCategories, $tagsString);
                        
                        if ($postId) {
                            header("Location: /$adminRoute/posts");
                            exit;
                        } else {
                            $data['errors'][] = 'Произошла ошибка при сохранении поста в базу данных.';
                        }
                    }
                    
                    $data['post'] = [
                        'title' => $title,
                        'url' => $url,
                        'content' => $content,
                        'status' => $status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'meta_keywords' => $meta_keywords,
                        'excerpt' => $excerpt,
                        'thumbnail_url' => $thumbnailUrl,
                        'selected_categories' => $selectedCategories,
                        'selected_tags' => $tagsString
                    ];
                }
            }
        } catch (Throwable $e) {
            Logger::error("An unexpected error occurred: " . $e->getTraceAsString());
            $data['errors'][] = 'Произошла непредвиденная ошибка. Пожалуйста, попробуйте снова или свяжитесь с администратором.';
            
            // Если ошибка произошла, мы все равно заполняем данные для формы
            $data['post'] = [
                'title' => $_POST['title'] ?? '',
                'url' => $_POST['url'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'meta_keywords' => $_POST['meta_keywords'] ?? '',
                'excerpt' => $_POST['excerpt'] ?? '',
                'thumbnail_url' => $_POST['post_image_url'] ?? '',
                'selected_categories' => $_POST['categories'] ?? [],
                'selected_tags' => $_POST['tags'] ?? ''
            ];
        }

        // Этот код выполняется всегда, независимо от того, был ли POST-запрос или произошла ошибка
        $data['categories'] = $adminPostsModel->getAllCategories();
        $data['tags'] = $adminPostsModel->getAllTags();

        $data['csrf_token'] = CSRF::getToken();
    
        $content = View::render('../app/views/admin/posts/edit_create.php', $data);
        $route_path = 'edit_create';
        require '../app/views/admin/admin_layout.php';
    }
    
    public function editPost($postId)
    {
        $this->checkIfUserLoggedIn();
        $adminPostsModel = new AdminPostsModel();
        $adminRoute = Config::get('admin.AdminRoute');
        $is_new_post = false;
        $user_name = Auth::getUserName();

        $data = [
            'adminRoute' => $adminRoute,
            'articleType' => 'post',
            'user_name' => $user_name,
            'title' => 'Редактировать пост',
            'active' => 'posts',
            'post' => null,
            'categories' => [],
            'tags' => [],
            'errors' => [],
            'is_new_post' => $is_new_post
        ];

        // Если это POST-запрос (отправка формы редактирования)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!CSRF::validateToken($token)) {
                $data['errors'][] = 'Ошибка CSRF-токена. Попробуйте ещё раз.';
                CSRF::refreshToken();
            } else {
                $title = trim($_POST['title'] ?? '');
                $content = $_POST['content'] ?? '';
                $url = $this->sanitizeUrl($_POST['url'] ?? '');
                $status = $_POST['status'] ?? 'draft';
                $meta_title = trim($_POST['meta_title'] ?? '');
                $meta_description = trim($_POST['meta_description'] ?? '');
                $meta_keywords = trim($_POST['meta_keywords'] ?? '');
                $excerpt = trim($_POST['excerpt'] ?? '');
                $selectedCategories = $_POST['categories'] ?? [];
                $selectedTags = $_POST['tags'] ?? [];
                $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;
                $thumbnailUrl = trim($_POST['post_image_url'] ?? '');

                if (empty($title)) {
                    $data['errors'][] = 'Заголовок поста обязателен.';
                }
                if (empty($url)) {
                    $data['errors'][] = 'URL поста обязателен.';
                } else {
                    // Дополнительная проверка уникальности URL при редактировании
                    $existingPost = $adminPostsModel->getPostByUrl($url);
                    if ($existingPost && $existingPost['id'] != $postId) {
                        $data['errors'][] = 'Указанный URL уже занят.';
                    }
                }

                if (empty($data['errors'])) {
                    $postData = [
                        'status' => $status,
                        'title' => $title,
                        'content' => $content,
                        'url' => $url,
                        'excerpt' => $excerpt,
                        'meta_description' => $meta_description,
                        'meta_keywords' => $meta_keywords,
                        'thumbnail_url' => $thumbnailUrl,
                    ];

                    if ($adminPostsModel->updatePost($postId, $postData, $selectedCategories, $tagsString)) {
                        $_SESSION['message'] = 'Пост успешно обновлен!';
                        header("Location: /{$adminRoute}/posts");
                        exit;
                    } else {
                        $data['errors'][] = 'Произошла ошибка при обновлении поста в базу данных.';
                    }
                }

                // Заполняем данные формы из POST-запроса, если есть ошибки
                $data['post'] = [
                    'id' => $postId,
                    'title' => $title,
                    'url' => $url,
                    'content' => $content,
                    'status' => $status,
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'meta_keywords' => $meta_keywords,
                    'excerpt' => $excerpt,
                    'thumbnail_url' => $thumbnailUrl,
                    'selected_categories' => $selectedCategories,
                    'selected_tags' => $selectedTags
                ];
            }
        }
        
        // Этот блок выполняется для GET-запросов или при ошибке POST-запроса
        if (empty($data['post'])) {
            $data['post'] = $adminPostsModel->getPostById($postId);
            if (!$data['post']) {
                header("Location: /{$adminRoute}/posts");
                exit;
            }
        }
        
        // Получаем все категории и теги, чтобы заполнить списки в форме
        $data['categories'] = $adminPostsModel->getAllCategories();
        $data['tags'] = $adminPostsModel->getAllTags();
        $data['csrf_token'] = CSRF::getToken();

        // Устанавливаем заголовок
        $data['title'] = 'Редактировать пост: ' . htmlspecialchars($data['post']['title'] ?? '');

        $content = View::render('../app/views/admin/posts/edit_create.php', $data);
        $route_path = 'edit_create';
        require '../app/views/admin/admin_layout.php';
    }


    /**
     * Поиск меток по названию для автодополнения (POST-запрос).
     */
    public function searchTags()
    {
        $this->checkIfUserLoggedIn();

        // Получаем токен из заголовка AJAX-запроса
        $csrfTokenFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // Используем ваш существующий метод для валидации токена
        if (!CSRF::validateToken($csrfTokenFromHeader)) {
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }
        
        // Считываем JSON из тела запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $query = $data['q'] ?? '';
        
        if (mb_strlen($query) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $tags = $adminPostsModel->searchTagsByName($query);
            
            header('Content-Type: application/json');
            echo json_encode($tags);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            Logger::error('Ошибка при поиске меток: ' . $e->getMessage());
        }
    }

    public function checkUrl()
    {
        try
        {
            header('Content-Type: application/json');

            // 1. Проверяем, что это AJAX-запрос
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ запрещён.']);
                exit;
            }

            // 2. Получаем данные из тела POST-запроса
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? '';
            $csrfToken = $input['csrf_token'] ?? '';

            // 3. Проверяем CSRF-токен (предполагаем, что у вас есть функция для этого)
            if (!CSRF::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['error' => 'Неверный CSRF-токен.']);
                exit;
            }

            if (empty($url)) {
                echo json_encode(['is_unique' => false]);
                return;
            }

            $postModel = new AdminPostsModel();
            // В данном случае мы не передаём ID, так как пост создаётся
            $isUnique = $postModel->isUrlUnique($url); 

            echo json_encode(['is_unique' => $isUnique]);
        }
        catch(Exception $e)
        {
            Logger::error("Ошибка при проверке URL $url" . $e->getMessage());
            http_response_code(403);
            echo json_encode(['error' => 'Ошибка при проверке URL']);
            exit;
        }
    }

    /**
     * Вспомогательная функция для очистки имени файла
     *  */ 
    private function sanitizeUrl(string $url): string
    {
        // Транслитерация (реализуй свою или используй библиотеку)
        return transliterate($url);
    }

    /**
     * Удаляет пост по ID (через AJAX).
     * Ожидает POST-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function deletePost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Метод не разрешён.']);
            return;
        }

        // Проверяем, что это AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Доступ запрещён.']);
            return;
        }

        // Считываем JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = $input['post_id'] ?? null;
        $csrfToken = $input['csrf_token'] ?? '';

        // Валидация CSRF
        if (!CSRF::validateToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            return;
        }

        // Проверка авторизации
        $this->checkIfUserLoggedIn();

        // Проверка ID
        if (!is_numeric($postId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Неверный ID поста.']);
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $post = $adminPostsModel->getPostById((int)$postId);

            if (!$post) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Пост не найден.']);
                return;
            }

            // Помечаем пост как удалённый (или удаляем полностью — как у вас реализовано)
            // Допустим, у вас есть метод deletePost, который помечает статус как 'deleted'
            // Или удаляет связи и сам пост.

            // Пример: если вы просто помечаете как удалённый
            // $sql = "UPDATE posts SET status = 'deleted', updated_at = :updated_at WHERE id = :id";
            // $stmt = $this->db->prepare($sql);
            // $stmt->execute([
            //     ':id' => $postId,
            //     ':updated_at' => date('Y-m-d H:i:s')
            // ]);
            $admPostsModel = new AdminPostsModel();
            $admPostsModel->setPostAsDeleted($postId);

            // Или, если нужно полностью удалить пост и связи:
            // $adminPostsModel->deletePostWithRelations($postId); // реализуйте при необходимости

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Пост успешно удалён.'
            ]);
        } catch (Exception $e) {
            Logger::error("Ошибка при удалении поста $postId: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка сервера.']);
        }
    }
}
