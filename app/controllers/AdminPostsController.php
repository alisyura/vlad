<?php
// app/controllers/AdminPostsController .php

class AdminPostsController extends BaseController
{
    use UrlHelperTrait;

    /**
     * Отображает список постов/страниц в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     * @param string $articleType Тип статьи (post/page).
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
            $isTrash = $this->hasThrash($basePageUrl);

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
     * Точка входа в создание нового поста/страницы из маршрутизатора
     * 
     * @param string $articleType Тип статьи (post/page).
     */
    public function create($articleType) {
        $this->showCreateArticleForm($articleType);
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
     * Точка входа в изменение поста/страницы из маршрутизатора
     * 
     * @param int $postId ID поста для редактирования.
     * @param string $articleType Тип статьи (post/page).
     */
    public function edit($postId, $articleType)
    {
        $this->showEditArticleForm($postId, $articleType);
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
}