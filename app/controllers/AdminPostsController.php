<?php
// app/controllers/AdminPostsController .php

class AdminPostsController extends BaseController
{
    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     */
    public function list($currentPage = 1, $articleType)
    {
        $this->processList($currentPage, $articleType);
    }

    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     * @param string $articleType Тип статьи. post или page
     */
    private function processList($currentPage = 1, $articleType = 'post') {
        // $adminRoute = $this->getAdminRoute();
        $userName = Auth::getUserName();
        try {
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

            // Базовый URL для админки
            $basePageUrl=$this->getBasePageUrl();
            $isTrash = (new UrlHelperService())->hasThrash($basePageUrl);

            // Определяем параметры пагинации
            $postsPerPage = Config::get('admin.PostsPerPage'); // Количество постов на страницу

            $postsListModel = new PostsListModel();
            // Получаем общее количество постов
            $totalPosts = $postsListModel->getTotalPostsCount($articleType, $isTrash);
    
            // Генерируем массив ссылок для умной пагинации
            $ps = new PaginationService();
            $paginParams = $ps->calculatePaginationParams(Config::get('admin.PostsPerPage'), $currentPage, 
                $totalPosts, $basePageUrl);

            ['totalPages' => $totalPages, 
                'offset' => $offset, 
                'paginationLinks' => $paginationLinks] = $paginParams;
            
            

            // Получаем посты для текущей страницы
            $posts = $postsListModel->getPostsList($articleType, $postsPerPage, $offset,
                $sortBy, $sortOrder, $isTrash);
            
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

            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'user_name' => $userName,
                'title' => 'Список ' . ($articleType === 'post' ? 'постов' : 'страниц'),
                'active' => "{$articleType}s", // для подсветки в левом меню
                'posts' => $posts,
                'articleType' => $articleType,
                'allowDelete' => Auth::isUserAdmin(),
                'pagination' => [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ],
                'pagination_links' => $paginationLinks,
                'base_page_url' => $basePageUrl,
                'current_sort_by' => $sortBy,
                'current_sort_order' => $sortOrder,
                // 'thrash_link' => '/' . $this->getAdminRoute() . "/thrashbox/{$articleType}s",
                'isTrash' => $isTrash,
                'styles' => [
                    'posts_list.css'
                ],
                'jss' => [
                    'posts_list.js'                    
                ]
            ];
            
            $this->viewAdmin->renderAdmin('admin/posts/list.php', $data);

        } catch (PDOException $e) {
            Logger::error("Database error in listPosts: " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Не удалось загрузить данные. Пожалуйста, попробуйте позже.');
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
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

            $adminPostsModel = new AdditionalModel();

            Logger::debug("$logHeader. adminRoute $adminRoute");

            $pageTitle = ($articleType==='post') ? 'Создать новый пост' : 'Создать новую страницу';
            $returnToListUrl = "/{$adminRoute}/{$articleType}s";
            $returnToListTitle = ($articleType==='post') ? 'К списку постов' : 'К списку страниц';
            $formAction = "/{$adminRoute}/{$articleType}s/api/create";
            $publishButtonTitle = 'Опубликовать ' . ($articleType == 'post' ? 'пост' : 'страницу');
            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                'user_name' => Auth::getUserName(),
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
        
            $this->viewAdmin->renderAdmin('admin/posts/edit_create.php', $data);
            
        } catch (Throwable $e) {
            Logger::error("$logHeader. An unexpected error occurred: " . $e->getTraceAsString());

            $data = [
                'adminRoute' => $adminRoute,
                'user_name' => Auth::getUserName(),
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить посты. Пожалуйста, попробуйте позже.'
            ];
            $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
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
        
        $json_data = file_get_contents('php://input');
        // Декодируем JSON-строку в ассоциативный массив PHP
        $post_data = json_decode($json_data, true);

            
        $adminPostsModel = new AdminPostsModel();
        
        $title = trim($post_data['title'] ?? '');
        $content = $post_data['content'] ?? '';
        $url = transliterate($post_data['url'] ?? '');
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
        } else if ($adminPostsModel->postExists(null, $url)) {
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
    
            $postData = (new AdminPostsModel())->getPostById($postId, $articleType);
            if ($postData === null) {
                // Если пост не найден, рендерим специальный view и выходим
                $data = [
                    'adminRoute' => $adminRoute,
                    'user_name' => $user_name,
                    'error_message' => 'Запись не найдена.'
                ];
                
                $this->viewAdmin->renderAdmin('admin/errors/not_found_view.php', $data);
                return; // Ранний выход
            }
            $addModel = new AdditionalModel();
            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                'user_name' => $user_name,
                'pageTitle' => $pageTitle,
                'publishButtonTitle' => $publishButtonTitle,
                'active' => "{$articleType}s", // для подсветки в левом меню
                'post' => $postData,
                'categories' => $addModel->getAllCategories(),
                'tags' => $addModel->getAllTags(),
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

            $this->viewAdmin->renderAdmin('admin/posts/edit_create.php', $data);
        }
        catch(Throwable $e)
        {
            Logger::error("$logHeader. An unexpected error occurred: " . $e->getTraceAsString());

            $data = [
                'adminRoute' => $adminRoute,
                'user_name' => $user_name,
                'title' => 'Ошибка',
                'error_message' => 'Не удалось загрузить данные. Пожалуйста, попробуйте позже.'
            ];

            $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
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


        $json_data = file_get_contents('php://input');
        $decodedData = json_decode($json_data, true);

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

        $adminPostsModel = new AdminPostsModel();
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
     * Выполняет мягкое удаление поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function deletePost()
    {
        // Считываем JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);

        // Проверка ID
        if (!is_numeric($postId)) {
            $this->sendErrorJsonResponse('Неверный ID поста.');
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $post = $adminPostsModel->postExists((int)$postId);

            if (!$post) {
                $this->sendErrorJsonResponse('Пост не найден', 404);
                return;
            }

            // Помечаем пост как удалённый
            $admPostsModel = new AdminPostsModel();
            $admPostsModel->setPostAsDeleted($postId);

            $this->sendSuccessJsonResponse('Пост успешно удалён.');
        } catch (Exception $e) {
            Logger::error("Ошибка при удалении поста $postId: " . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при удалении поста', 500);
        }
    }
}