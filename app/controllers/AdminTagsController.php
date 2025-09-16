<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseController
{
    private TagsModel $tagsModel;

    public function __construct(Request $request, ViewAdmin $view)
    {
        parent::__construct($request, $view);
        $this->tagsModel = new TagsModel();
    }

    public function list($currentPage = 1)
    {
        try {
            // Определяем параметры пагинации
            $tagsPerPage = Config::get('admin.TagsPerPage'); // Количество постов на страницу

            // Базовый URL для админки
            $basePageUrl=$this->getBasePageUrl();

            $ps = new PaginationService();
            $paginParams = $ps->calculatePaginationParams($tagsPerPage, $currentPage,
                $this->tagsModel->getTotalTagsCount(), $basePageUrl);
            
            ['totalPages' => $totalPages, 
                'offset' => $offset, 
                'paginationLinks' => $paginationLinks] = $paginParams;

            // Получаем посты для текущей страницы
            $data['tags'] = $this->tagsModel->getTagsWithPostCount($tagsPerPage, $offset);

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = Auth::getUserName();
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['pagination'] = [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ];
            $data['pagination_links'] = $paginationLinks;
            $data['base_page_url'] = $basePageUrl;
            $data['styles'] = ['tags.css'];
            $data['jss'] = ['tags.js'];
            
            $this->viewAdmin->renderAdmin('admin/tags/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in tags list: " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    public function edit(int $tagId)
    {
        try {
            // $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = Auth::getUserName();
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['styles'] = ['tags.css'];
            $data['jss'] = ['tags.js'];
            
            // Получаем данные конкретного пользователя для формы редактирования
            $tag = $this->tagsModel->getTag(id: $tagId);
            if (empty($tag))
            {
                $this->showAdminError('Ошибка', 'Тэг не найден.');
                return;
            }
            
            $data['tag_to_edit'] = $tag;
            $this->viewAdmin->renderAdmin('admin/tags/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit tag (show form): " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }
}