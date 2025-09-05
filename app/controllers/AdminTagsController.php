<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseController
{
    private TagsModel $tagsModel;

    public function __construct(ViewAdmin $view)
    {
        parent::__construct($view);
        $this->tagsModel = new TagsModel();
    }

    public function list()
    {
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = Auth::getUserName();
            $data['active'] = "tags"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            $this->viewAdmin->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list: " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }
}