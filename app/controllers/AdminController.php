<?php
// app/controllers/AdminController.php

class AdminController {
    private function checkIfUserLoggedIn()
    {
        if (!Auth::check()) {
            // Проверяем, является ли запрос AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                // Если это AJAX, возвращаем JSON-ошибку 401
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
                exit;
            } else {
                // Если это обычный запрос, делаем редирект
                $adminRoute = Config::get('admin.AdminRoute');
                header("Location: /$adminRoute/login");
                exit;
            }
        }
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

        $adminRoute = Config::get('admin.AdminRoute');
        try {
            $admPostsModel = new AdminPostsModel();

            // --- Получение и валидация параметров сортировки ---
            $sortBy = $_GET['sort'] ?? 'updated_at';
            $sortOrder = $_GET['order'] ?? 'DESC';

            $allowedSorts = ['id', 
                'title', 
                'author', 
                'categories', 
                'tags', 
                'status', 
                'updated_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'updated_at';
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

                // Добавляем новый ключ с полным URL
                if ($post['article_type'] === 'page') {
                    $post['full_url'] = 'page/' . $post['url'];
                } else {
                    $post['full_url'] = $post['url'];
                }
                
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

            
            
            // Генерируем массив ссылок для умной пагинации
            // Базовый URL для админки
            $basePageUrl = '/' . htmlspecialchars($adminRoute) . "/{$articleType}s";
            $paginationLinks = generateSmartPaginationLinks($currentPage, $totalPages, $basePageUrl);

            $data = [
                'adminRoute' => $adminRoute,
               // 'user_name' => $user_name,
                'title' => 'Список ' . ($articleType === 'post' ? 'постов' : 'страниц'),
                'active' => "{$articleType}s", // для подсветки в левом меню
                'posts' => $posts,
                'articleType' => $articleType,
                'allowDelete' => Auth::isAdmin(),
                'pagination' => [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ],
                'pagination_links' => $paginationLinks,
                'base_page_url' => $basePageUrl,
                'current_sort_by' => $sortBy,
                'current_sort_order' => $sortOrder,
                'styles' => [
                    'posts_list.css'
                ],
                'jss' => [
                    'posts_list.js'                    
                ]
            ];
            
            // Используется в admin_layout
            $user_name = Auth::getUserName();
            // $content = View::render('../app/views/admin/posts/list.php', $data);
            // $route_path = 'posts-list';
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/posts/list.php', $data);

        } catch (PDOException $e) {
            Logger::error("Database error in listPosts: " . $e->getTraceAsString());
            $data = [
                'adminRoute' => $adminRoute,
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить данные. Пожалуйста, попробуйте позже.'
            ];
            // $content = View::render('../app/views/admin/errors/error_view.php', $data);
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/errors/error_view.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $data = [
                'adminRoute' => $adminRoute,
                'title' => 'Ошибка',
                'error_message' => 'Произошла непредвиденная ошибка.'
            ];
            // $content = View::render('../app/views/admin/errors/error_view.php', $data);
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/errors/error_view.php', $data);
        }
    }

    /**
     * Точка входа в создание нового поста из маршрутизатора
     */
    public function createPostGet() {
        $this->showCreateArticleForm('post');
    }

    /**
     * Точка входа в создание новой страницы из маршрутизатора
     */
    public function createPageGet() {
        $this->showCreateArticleForm('page');
    }

    /**
     * Открывает страницу создания нового поста/страницы
     * 
     * @param string $articleType Тип статьи (post/page)
     */
    private function showCreateArticleForm(string $articleType) {
        $adminRoute = Config::get('admin.AdminRoute');

        $logHeader = ($articleType === 'post') ? 'createPostGet' : 'createPageGet';

        try {
            Logger::debug("$logHeader. begin");
            $this->checkIfUserLoggedIn();

            $adminPostsModel = new AdminPostsModel();
            
            // $user_id = Auth::getUserId();
            // $user_name = Auth::getUserName();

            Logger::debug("$logHeader. adminRoute $adminRoute");

            $pageTitle = ($articleType==='post') ? 'Создать новый пост' : 'Создать новую страницу';
            $returnToListUrl = "/{$adminRoute}/{$articleType}s";
            $returnToListTitle = ($articleType==='post') ? 'К списку постов' : 'К списку страниц';
            $formAction = "/{$adminRoute}/{$articleType}s/api/create";
            $publishButtonTitle = 'Опубликовать ' . ($articleType == 'post' ? 'пост' : 'страницу');
            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                // 'user_name' => $user_name,
                'title' => '', //тк создаем новый пост
                'active' => "{$articleType}s", // для подсветки в левом меню
                'pageTitle' => $pageTitle,
                'publishButtonTitle' => $publishButtonTitle,
                'post' => null,
                'categories' => [],
                'tags' => [],
                // 'errors' => [],
                'is_new_post' => true,
                'categories' => $adminPostsModel->getAllCategories(),
                'tags' => $adminPostsModel->getAllTags(),
                'returnToListUrl' => [
                    'url' => $returnToListUrl,
                    'title' => $returnToListTitle
                ],
                'formAction' => $formAction,
                'styles' => [
                    'edit_create.css',
                    'edit_create_mediateka.css'
                ],
                'jss' => [
                    'absolute' => 'tinymce/tinymce.min.js',
                    'edit_create_tag_selector.js',
                    'edit_create_mediateka.js',
                    'edit_create.js'
                ]
            ];

            Logger::debug("$logHeader. data", $data);
        
            // $route_path = 'edit_create';
            // $content = View::render('../app/views/admin/posts/edit_create.php', $data);
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/posts/edit_create.php', $data);
            
        } catch (Throwable $e) {
            Logger::error("$logHeader. An unexpected error occurred: " . $e->getTraceAsString());

            $data = [
                'adminRoute' => $adminRoute,
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить посты. Пожалуйста, попробуйте позже.'
            ];
            $user_name = Auth::getUserName();
            // $content = View::render('../app/views/admin/errors/error_view.php', $data);
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/errors/error_view.php', $data);
        }

        
    }

    /**
     * Точка входа на создание нового поста (AJAX POST запрос)
     */
    public function createPostPost() {
        $this->createArticle('post');
    }

    /**
     * Точка входа на создание новой страницы (AJAX POST запрос)
     */
    public function createPagePost() {
        $this->createArticle('page');
    }

    /**
     * Создает запись с типом из articleType
     * Вызывается по AJAX POST
     */
    private function createArticle($articleType) {
        header('Content-Type: application/json');


        Logger::debug("createPostPost. Начало");

        $this->checkIfUserLoggedIn();

        // вызов должен прийти методом пост. должен быть установлен HTTP_X_REQUESTED_WITH
        // и он должен быть равен XMLHttpRequest
        Logger::debug("createPostPost. REQUEST_METHOD = {$_SERVER['REQUEST_METHOD']}");
        $http_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        Logger::debug("createPostPost. HTTP_X_REQUESTED_WITH = {$http_requested_with}");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' 
            || empty($http_requested_with)
            || strtoupper($http_requested_with) !== strtoupper('XMLHttpRequest')) {
            
            echo json_encode(['success' => false, 'message' => 'Неверный REQUEST_METHOD или HTTP_X_REQUESTED_WITH.']);
            http_response_code(403);
            exit;
        }

        $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        Logger::debug("createPostPost. post token = $csrf_token");
        
        if (!CSRF::validateToken($csrf_token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            exit;
        } 

        Logger::debug("createPostPost. post token is valid");
        
        $json_data = file_get_contents('php://input');
        // Декодируем JSON-строку в ассоциативный массив PHP
        $post_data = json_decode($json_data, true);

            
        $adminPostsModel = new AdminPostsModel();
        
        $title = trim($post_data['title'] ?? '');
        $content = $post_data['content'] ?? '';
        $url = $this->sanitizeUrl($post_data['url'] ?? '');
        $status = $post_data['status'] ?? 'draft';
        $meta_title = trim($post_data['meta_title'] ?? '');
        $meta_description = trim($post_data['meta_description'] ?? '');
        $meta_keywords = trim($post_data['meta_keywords'] ?? '');
        $excerpt = trim($post_data['excerpt'] ?? '');
        $selectedCategories = $post_data['categories'] ?? [];

        $selectedTags = $post_data['tags'] ?? [];
        $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

        $thumbnailUrl = trim($post_data['post_image_url'] ?? ''); 

        if (empty($title)) {
            Logger::debug("createPostPost. title empty");
            $data['errors'][] = 'Заголовок поста обязателен.';
        }
        if (empty($url)) {
            Logger::debug("createPostPost. url empty");
            $data['errors'][] = 'URL поста обязателен.';
        } else if (!$adminPostsModel->isUrlUnique($url)) {
            Logger::debug("createPostPost. url exists");
            $data['errors'][] = 'Указанный URL уже занят.';
        }

        if (!empty($data['errors'])) {
            Logger::debug("createPostPost. ошибки заполнены. выход");
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Неверно заполнены поля.',
                'errors' => $data['errors']]);
            exit;
        }

            
        $user_id = Auth::getUserId();
        $postData = [
            'user_id' => $user_id,
            'article_type' => $articleType,
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
            $adminRoute = Config::get('admin.AdminRoute');
            $msgText = ($articleType == 'post' ? 'Пост успешно создан' : 'Страница успешно создана');
            echo json_encode(['success' => true, 
                'redirect' => "/$adminRoute/{$articleType}s",
                'message' => $msgText]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Произошла ошибка при создании поста.']);
        }

    }
    
    /**
     * Точка входа в изменение поста из маршрутизатора
     */
    public function editPostGet($postId)
    {
        $this->showEditArticleForm($postId, 'post');
    }

    /**
     * Точка входа в изменение страницы из маршрутизатора
     */
    public function editPageGet($postId)
    {
        $this->showEditArticleForm($postId, 'page');
    }

    /**
     * Открывает страницу изменения поста/страницы
     *
     * @param int $postId Id статьи
     * @param string $articleType Тип статьи (post/page)
     */
    private function showEditArticleForm($postId, $articleType)
    {
        $adminRoute = Config::get('admin.AdminRoute');
        $user_name = Auth::getUserName();
        $logHeader = ($articleType === 'post') ? 'editPostGet' : 'editPageGet';
        try
        {
            $this->checkIfUserLoggedIn();
            $adminPostsModel = new AdminPostsModel();

            $config = [
                'post' => [
                    'listTitle' => 'К списку постов',
                    'formAction' => "/{$adminRoute}/posts/api/edit",
                    'pageTitle' => 'Редактирование поста: ',
                    'listUrl' => "/{$adminRoute}/posts",
                ],
                'page' => [
                    'listTitle' => 'К списку страниц',
                    'formAction' => "/{$adminRoute}/pages/api/edit",
                    'pageTitle' => 'Редактирование страницы: ',
                    'listUrl' => "/{$adminRoute}/pages",
                ]
            ];

            $returnToListTitle = $config[$articleType]['listTitle'];
            $formAction = $config[$articleType]['formAction'];
            $pageTitle = $config[$articleType]['pageTitle'];
            $returnToListUrl = $config[$articleType]['listUrl'];
            $publishButtonTitle = 'Обновить ' . ($articleType == 'post' ? 'пост' : 'страницу');
    
            $postData = $adminPostsModel->getPostById($postId, $articleType);
            if ($postData === null) {
                // Если пост не найден, рендерим специальный view и выходим
                $data = [
                    'adminRoute' => $adminRoute,
                    'error_message' => 'Запись не найдена.'
                ];
                // $content = View::render('../app/views/admin/errors/not_found_view.php', $data);
                // require '../app/views/admin/admin_layout.php';
                View::renderAdmin('../app/views/admin/errors/not_found_view.php', $data);
                return; // Ранний выход
            }
            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                'user_name' => $user_name,
                'pageTitle' => $pageTitle,
                'publishButtonTitle' => $publishButtonTitle,
                'active' => "{$articleType}s", // для подсветки в левом меню
                'post' => $postData,
                'categories' => $adminPostsModel->getAllCategories(),
                'tags' => $adminPostsModel->getAllTags(),
                // 'errors' => [],
                'is_new_post' => false,
                'formAction' => $formAction,
                'returnToListUrl' => [
                        'url' => $returnToListUrl,
                        'title' => $returnToListTitle
                ],
                'styles' => [
                    'edit_create.css',
                    'edit_create_mediateka.css'
                ],
                'jss' => [
                    'absolute' => 'tinymce/tinymce.min.js',
                    'edit_create_tag_selector.js',
                    'edit_create_mediateka.js',
                    'edit_create.js'                    
                ]
            ];

            // Устанавливаем заголовок
            $data['pageTitle'] .= htmlspecialchars($data['post']['title'] ?? '');

            // $content = View::render('../app/views/admin/posts/edit_create.php', $data);
            // $route_path = 'edit_create';
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/posts/edit_create.php', $data);
        }
        catch(Throwable $e)
        {
            Logger::error("$logHeader. An unexpected error occurred: " . $e->getTraceAsString());

            $data = [
                'adminRoute' => $adminRoute,
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить данные. Пожалуйста, попробуйте позже.'
            ];

            // $content = View::render('../app/views/admin/errors/error_view.php', $data);
            // require '../app/views/admin/admin_layout.php';
            View::renderAdmin('../app/views/admin/errors/error_view.php', $data);
        }
    }

    /**
     * Точка входа на редактирование нового поста (AJAX PUT запрос)
     */
    public function editPostPut() {
        $this->editArticle('post');
    }

    /**
     * Точка входа на редактирование новой страницы (AJAX PUT запрос)
     */
    public function editPagePut() {
        $this->editArticle('page');
    }

    /**
     * Изменяет запись с типом из articleType
     * Вызов по AJAX PUT
     */
    private function editArticle($articleType)
    {
        header('Content-Type: application/json');


        Logger::debug("editArticle. Начало");

        $this->checkIfUserLoggedIn();

        // вызов должен прийти методом PUT. должен быть установлен HTTP_X_REQUESTED_WITH
        // и он должен быть равен XMLHttpRequest
        Logger::debug("editArticle. REQUEST_METHOD = {$_SERVER['REQUEST_METHOD']}");
        $http_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        Logger::debug("editArticle. HTTP_X_REQUESTED_WITH = {$http_requested_with}");

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' 
            || empty($http_requested_with)
            || strtoupper($http_requested_with) !== strtoupper('XMLHttpRequest')) {
            
            echo json_encode(['success' => false, 'message' => 'Неверный REQUEST_METHOD или HTTP_X_REQUESTED_WITH.']);
            http_response_code(403);
            exit;
        }

        

        $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        Logger::debug("editArticle. post token = $csrf_token");
        
        if (!CSRF::validateToken($csrf_token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            exit;
        } 

        Logger::debug("editArticle. post token is valid");

        $json_data = file_get_contents('php://input');
        $decodedData = json_decode($json_data, true);

        $adminPostsModel = new AdminPostsModel();

        $postId = filter_var($decodedData['id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim($decodedData['title'] ?? '');
        $content = $decodedData['content'] ?? '';
        $status = $decodedData['status'] ?? 'draft';
        $meta_title = trim($decodedData['meta_title'] ?? '');
        $meta_description = trim($decodedData['meta_description'] ?? '');
        $meta_keywords = trim($decodedData['meta_keywords'] ?? '');
        $excerpt = trim($decodedData['excerpt'] ?? '');
        $selectedCategories = $decodedData['categories'] ?? [];

        $selectedTags = $decodedData['tags'] ?? [];
        $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

        $thumbnailUrl = trim($decodedData['post_image_url'] ?? '');

        if (!$adminPostsModel->postExists($postId))
        {
            Logger::debug("editArticle. post does not exists. postId={$postId}");
            $data['errors'][] = 'Пост не найден.';
        }
        if (empty($title)) {
            Logger::debug("editArticle. title empty");
            $data['errors'][] = 'Заголовок поста обязателен.';
        }

        if (!empty($data['errors'])) {
            Logger::debug("editArticle. ошибки заполнены. выход");
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Неверно заполнены поля.',
                'errors' => $data['errors']]);
            exit;
        }


        $user_id = Auth::getUserId();
        $postData = [
            'user_id' => $user_id,
            'article_type' => $articleType,
            'status' => $status,
            'title' => $title,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'excerpt' => $excerpt,
            'thumbnail_url' => $thumbnailUrl,
        ];

        $updateResult = $adminPostsModel->updatePost($postId, $postData, $selectedCategories, $tagsString);
        
        if ($updateResult) {
            $adminRoute = Config::get('admin.AdminRoute');
            $msgText = ($articleType == 'post' ? 'Пост успешно обновлен' : 'Страница успешно обновлена');
            echo json_encode(['success' => true, 
                'redirect' => "/$adminRoute/{$articleType}s/edit/{$postId}",
                'message' => $msgText]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Произошла ошибка при создании поста.']);
        }

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
     * Выполняет мягкое удаление поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function deletePost()
    {
        // Проверка авторизации
        $this->checkIfUserLoggedIn();

        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
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

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        // Валидация CSRF
        if (!CSRF::validateToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            return;
        }

        // Считываем JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);

        // Проверка ID
        if (!is_numeric($postId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Неверный ID поста.']);
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $post = $adminPostsModel->postExists((int)$postId);

            if (!$post) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Пост не найден.']);
                return;
            }

            // Помечаем пост как удалённый
            $admPostsModel = new AdminPostsModel();
            $admPostsModel->setPostAsDeleted($postId);

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
