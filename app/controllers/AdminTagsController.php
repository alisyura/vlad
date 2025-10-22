<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseController
{
    use ShowAdminErrorViewTrait;

    private TagsModel $tagsModel;
    private string $userName;
    private PaginationService $paginService;

    public function __construct(Request $request, View $view, AuthService $authService, 
        TagsModel $tagsModel, PaginationService $paginService)
    {
        parent::__construct($request, $view);
        $this->tagsModel = $tagsModel;
        $this->paginService = $paginService;
        $this->userName = $authService->getUserName();
    }

    public function list($currentPage = 1)
    {
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
            $data['user_name'] = $this->userName;
            $data['active'] = "tags"; // подсветка вкладки левого меню
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
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $this->userName);
        }
    }

    public function edit(int $tagId)
    {
        try {
            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $this->userName;
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['styles'] = ['tags.css'];
            $data['jss'] = ['tags.js'];
            
            // Получаем данные конкретного пользователя для формы редактирования
            $tag = $this->tagsModel->getTag(id: $tagId);
            if (empty($tag))
            {
                $this->showAdminErrorView('Ошибка', 'Тэг не найден.',$this->userName);
                return;
            }
            
            $data['tag_to_edit'] = $tag;
            $this->view->renderAdmin('admin/tags/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit tag (show form): " . $e->getTraceAsString());
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $this->userName);
        }
    }
}