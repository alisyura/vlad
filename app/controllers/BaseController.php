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
}
