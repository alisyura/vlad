<?php
// app/controllers/AdminUsersController.php

class AdminUsersController extends BaseController
{
    public function list()
    {
        try{
            $um = new UserModel();

            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'user_name' => Auth::getUserName(),
                // 'title' => 'Список ' . ($articleType === 'post' ? 'постов' : 'страниц'),
                'active' => "users", // для подсветки в левом меню
                'roles' => $um->getRolesList(),
                'users' => $um->getUsersList(),
                'styles' => [
                    'users.css'
                ],
                'jss' => [  
                    'users.js'                
                ]
            ];

            $this->viewAdmin->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list: " . $e->getTraceAsString());
            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'user_name' => Auth::getUserName(),
                'title' => 'Ошибка',
                'error_message' => 'Произошла непредвиденная ошибка.'
            ];
            $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
        }
    }
}