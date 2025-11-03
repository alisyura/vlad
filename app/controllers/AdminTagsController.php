<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseAdminController
{
    private TagsModel $tagsModel;
    private AuthService $authService;
    private PaginationService $paginService;

    public function __construct(Request $request, View $view, AuthService $authService, 
        TagsModel $tagsModel, PaginationService $paginService, ResponseFactory $responseFactory)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->tagsModel = $tagsModel;
        $this->paginService = $paginService;
        $this->authService = $authService;
    }

    public function list($currentPage = 1): Response
    {
        $userName = $this->authService->getUserName();

        try {
            // Определяем параметры пагинации
            $tagsPerPage = Config::get('admin.TagsPerPage'); // Количество постов на страницу

            // Базовый URL для админки
            $basePageUrl=$this->getRequest()->getBasePageUrl();

            $paginParams = $this->paginService->calculatePaginationParams($tagsPerPage, $currentPage,
                $this->tagsModel->getTotalTagsCount(), $basePageUrl);
            
            ['totalPages' => $totalPages, 
                'offset' => $offset, 
                'paginationLinks' => $paginationLinks] = $paginParams;

            // Получаем посты для текущей страницы
            $data['tags'] = $this->tagsModel->getTagsWithPostCount($tagsPerPage, $offset);

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['pagination'] = [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ];
            $data['pagination_links'] = $paginationLinks;
            $data['base_page_url'] = $basePageUrl;
            $data['styles'] = ['tags.css'];
            $data['jss'] = ['tags.js'];

            return $this->renderHtml('admin/tags/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in tags list: ", ['currentPage' => $currentPage], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function edit(int $tagId): Response
    {
        $userName = $this->authService->getUserName();

        try {
            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['styles'] = ['tags.css'];
            $data['jss'] = ['tags.js'];
            
            // Получаем данные конкретного пользователя для формы редактирования
            $tag = $this->tagsModel->getTag(id: $tagId);
            if (empty($tag))
            {
                throw new HttpException('Тэг не найден.', 404);
            }
            
            $data['tag_to_edit'] = $tag;

            return $this->renderHtml('admin/tags/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit tag (show form): ", ['tagId' => $tagId], $e);
            if (($e instanceof HttpException) && $e->getCode() == 404)
            {
                throw $e;
            }

            throw new HttpException('Произошла непредвиденная ошибка.', 500);
        }
    }
}