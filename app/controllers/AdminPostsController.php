<?php
// app/controllers/AdminPostsController.php

class AdminPostsController extends BaseAdminController
{
    use UrlHelperTrait;

    private PostModelAdmin $model;
    private ListModel $listmodel;
    private AuthService $authService;
    private PaginationService $pageinationService;

    public function __construct(
        Request $request, 
        View $view, 
        PostModelAdmin $model,
        ListModel $listmodel, 
        AuthService $authService, 
        PaginationService $pageinationService, ResponseFactory $responseFactory)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->model = $model;
        $this->listmodel = $listmodel;
        $this->authService = $authService;
        $this->pageinationService = $pageinationService;
    }
    /**
     * Отображает список постов/страниц в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function list($articleType, $currentPage = 1): Response
    {
        return $this->processList($articleType, $currentPage);
    }

    /**
     * Отображает список постов в админ-панели с пагинацией.
     * @param int $currentPage Номер текущей страницы (из URL, по умолчанию 1).
     * @param string $articleType Тип статьи. post или page
     * @return Response
     */
    private function processList($articleType = 'post', $currentPage = 1): Response {
        $userName = $this->authService->getUserName();
        Logger::debug('processList. begin', ['articleType' => $articleType, 'currentPage' => $currentPage]);
        try {
            // получение фильтров
            $filterData = [
                'selectedStatus' => $this->getRequest()->status ?? '',
                'selectedPostDate' => $this->getRequest()->post_date ?? '',
                'selectedSearchQuery' => $this->getRequest()->searchquery ?? '',
                'selectedCategory' => $this->getRequest()->category_id ?? '',
            ];

            Logger::debug('processList. filterData', $filterData);

            // --- Получение и валидация параметров сортировки ---
            $sortBy = $this->getRequest()->sort ?? 'updated_at';
            $sortOrder = $this->getRequest()->order ?? 'DESC';

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

            Logger::debug('processList. Параметры сортировки', ['sortOrder' => $sortOrder, 'sortBy' => $sortBy]);
            // --- Конец обработки параметров сортировки ---

            // Базовый URL для админки
            $basePageUrl=$this->getRequest()->getBasePageUrl();
            $isTrash = $this->hasThrash($basePageUrl);

            // Определяем параметры пагинации
            $postsPerPage = Config::get('admin.PostsPerPage'); // Количество постов на страницу

            Logger::debug('processList. Основные параметры', ['basePageUrl' => $basePageUrl, 'isTrash' => $isTrash, 'postsPerPage' => $postsPerPage]);

            // Получаем общее количество постов
            $totalPosts = $this->model->getTotalPostsCount($articleType, $isTrash, $filterData);
    
            // Генерируем массив ссылок для умной пагинации
            $paginParams = $this->pageinationService->calculatePaginationParams(
                (int)$postsPerPage, $currentPage, $totalPosts, $basePageUrl);

            ['totalPages' => $totalPages, 
                'offset' => $offset, 
                'paginationLinks' => $paginationLinks] = $paginParams;
            
            $queryPagingParams = buildQueryString($filterData, $sortBy, $sortOrder, true);
            $querySortingParams = buildQueryString($filterData, $sortBy, $sortOrder, false);

            Logger::debug('processList. Параметры пагинации', ['totalPosts' => $totalPosts, 'totalPages' => $totalPages, 'offset' => $offset]);

            Logger::debug('processList. Вызывается getPostsList', [
                'articleType' => $articleType, 
                'postsPerPage' => $postsPerPage, 
                'offset' => $offset,
                'sortBy' => $sortBy, 
                'sortOrder' => $sortOrder, 
                'isTrash' => $isTrash,
                'filterData' => $filterData
            ]);

            // Получаем посты для текущей страницы
            $posts = $this->model->getPostsList($articleType, $postsPerPage, $offset,
                $sortBy, $sortOrder, $isTrash, $filterData);

            Logger::debug('processList. Получено ' . count($posts ?? -1), []);
            
            
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
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'posts' => $posts,
                'articleType' => $articleType,
                'allowDelete' => $this->authService->isUserAdmin(),
                'pagination' => [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ],
                'pagination_links' => $paginationLinks,
                'base_page_url' => $basePageUrl,
                'current_sort_by' => $sortBy,
                'current_sort_order' => $sortOrder,
                'queryPagingParams' => $queryPagingParams,
                'querySortingParams' => $querySortingParams,
                'isTrash' => $isTrash,
                'filter' => [
                    'categories' => $this->listmodel->getAllCategories(),
                    'statuses' => [
                        'Ожидание' => PostModelAdmin::STATUS_PENDING,
                        'Опубликован' => PostModelAdmin::STATUS_PUBLISHED,
                        'Удален' => PostModelAdmin::STATUS_DELETED,
                        'Черновик' => PostModelAdmin::STATUS_DRAFT
                    ],
                    ...($filterData),
                    // При установке фильтра сбрасываем все сортировки и предыдущие фильтры
                    'formAction' => $basePageUrl
                ],
                'styles' => [
                    'posts_list.css',
                    'http' => 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'
                ],
                'jss' => [
                    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ru.js',
                    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/index.js',
                    'posts_list.js',
                    'common.js'
                ]
            ];

            return $this->renderHtml('admin/posts/list.php', $data);
        } catch (PDOException $e) {
            Logger::error("Database error in listPosts", ['currentPage' => $currentPage, 'articleType' => $articleType], $e);
            throw new HttpException('Не удалось загрузить данные. Пожалуйста, попробуйте позже.', 400, $e);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: ", ['currentPage' => $currentPage, 'articleType' => $articleType], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    /**
     * Точка входа в создание нового поста/страницы из маршрутизатора
     * 
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function create($articleType): Response {
        return $this->showCreateArticleForm($articleType);
    }

    /**
     * Открывает страницу создания нового поста/страницы
     * 
     * @param string $articleType Тип статьи (post/page)
     * @return Response
     */
    private function showCreateArticleForm(string $articleType): Response {
        $adminRoute = $this->getAdminRoute();
        $userName = $this->authService->getUserName();

        try {
            $pageTitle = ($articleType==='post') ? 'Создать новый пост' : 'Создать новую страницу';
            $returnToListUrl = "/{$adminRoute}/{$articleType}s";
            $returnToListTitle = ($articleType==='post') ? 'К списку постов' : 'К списку страниц';
            $formAction = "/{$adminRoute}/{$articleType}s/api/create";
            $publishButtonTitle = 'Опубликовать ' . ($articleType == 'post' ? 'пост' : 'страницу');
            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                'user_name' => $userName,
                'title' => '', //тк создаем новый пост
                'active' => "{$articleType}s", // для подсветки в левом меню
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'pageTitle' => $pageTitle,
                'publishButtonTitle' => $publishButtonTitle,
                'post' => null,
                'categories' => [],
                'tags' => [],
                'is_new_post' => true,
                'categories' => $this->listmodel->getAllCategories(),
                'tags' => $this->listmodel->getAllTags(),
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
                    'edit_create.js',
                    'common.js'
                ]
            ];

            return $this->renderHtml('admin/posts/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("showCreateArticleForm. An unexpected error occurred: ", ['articleType' => $articleType], $e);
            throw new HttpException('Сбой при открытии формы', 500, $e);
        }   
    }

    /**
     * Точка входа в изменение поста/страницы из маршрутизатора
     * 
     * @param int $postId ID поста для редактирования.
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function edit($postId, $articleType): Response
    {
        return $this->showEditArticleForm($postId, $articleType);
    }

    /**
     * Открывает страницу изменения поста/страницы
     *
     * @param int $postId Id статьи
     * @param string $articleType Тип статьи (post/page)
     * @return Response
     */
    private function showEditArticleForm($postId, $articleType): Response
    {
        $adminRoute = $this->getAdminRoute();
        $userName = $this->authService->getUserName();
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
    
            $postData = $this->model->getPostById($postId, $articleType);
            if ($postData === null) {
                throw new HttpException('Запись не найдена.', 404);
            }

            $data = [
                'adminRoute' => $adminRoute,
                'articleType' => $articleType,
                'user_name' => $userName,
                'pageTitle' => $pageTitle,
                'publishButtonTitle' => $publishButtonTitle,
                'active' => "{$articleType}s", // для подсветки в левом меню
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'post' => $postData,
                'categories' => $this->listmodel->getAllCategories(),
                'tags' => $this->listmodel->getAllTags(),
                'is_new_post' => $postData['status'] == PostModelAdmin::STATUS_PENDING,
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
                    'edit_create.js',
                    'common.js'
                ]
            ];

            // Устанавливаем заголовок
            $data['pageTitle'] .= htmlspecialchars($data['post']['title'] ?? '');

            return $this->renderHtml('admin/posts/edit_create.php', $data);
        }
        catch(Throwable $e)
        {
            Logger::error("showEditArticleForm. An unexpected error occurred", ['postId' => $postId, 'articleType' => $articleType], $e);

            if (($e instanceof HttpException) && $e->getCode() === 404)
            {
                throw $e;
            }

            throw new HttpException('Сбой при открытии формы редактирования', 500, $e);
        }
    }
}