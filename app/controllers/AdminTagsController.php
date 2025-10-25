<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseAdminController
{
    use ShowAdminErrorViewTrait;

    private TagsModel $tagsModel;
    private AuthService $authService;
    private PaginationService $paginService;

    public function __construct(Request $request, View $view, AuthService $authService, 
        TagsModel $tagsModel, PaginationService $paginService)
    {
        parent::__construct($request, $view);
        $this->tagsModel = $tagsModel;
        $this->paginService = $paginService;
        $this->authService = $authService;
    }

    public function list($currentPage = 1)
    {
        $userName = $this->authService->getUserName();

        try {
            // Определяем параметры пагинации
            $tagsPerPage = Config::get('admin.TagsPerPage'); // Количество постов на страницу

            // Базовый URL для админки
            $basePageUrl=$this->request->getBasePageUrl();

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

            $this->view->renderAdmin('admin/tags/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in tags list: ", ['currentPage' => $currentPage], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }

    public function edit(int $tagId)
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
                $this->showAdminErrorView('Ошибка', 'Тэг не найден.',$userName);
                return;
            }
            
            $data['tag_to_edit'] = $tag;
            $this->view->renderAdmin('admin/tags/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit tag (show form): ", ['tagId' => $tagId], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }
}