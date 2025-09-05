<?php
// app/controllers/BaseController.php

abstract class BaseController {
    protected $viewAdmin;
    private $adminRoute;

    public function __construct(ViewAdmin $viewAdmin)
    {
        $this->viewAdmin = $viewAdmin;
        $this->adminRoute = Config::get('admin.AdminRoute');
    }

    protected function getAdminRoute()
    {
        return $this->adminRoute;
    }

    protected function showAdminError($title, $errMsg)
    {
        $data = [
            'adminRoute' => $this->getAdminRoute(),
            'user_name' => Auth::getUserName(),
            'title' => $title,
            'error_message' => $errMsg
        ];
        $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
    }
}
