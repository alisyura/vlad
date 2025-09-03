<?php
// app/controllers/AdminUsersController.php

class AdminUsersController extends BaseController
{
    public function list()
    {
        try {
            $um = new UserModel();

            // Проверяем, является ли текущий пользователь администратором
            $isUserAdmin = Auth::isUserAdmin();
            $currentUserId = Auth::getUserId(); // Получаем ID текущего пользователя
            
            // Получаем список пользователей в зависимости от роли
            $users = $isUserAdmin 
                ? $um->getAllUsersList() 
                : $um->getUserById($currentUserId);
            
            // Если не админ и не нашли свою запись, выводим ошибку (не должно произойти)
            if (!$isUserAdmin && empty($users)) {
                 $data = [
                    'adminRoute' => $this->getAdminRoute(),
                    'user_name' => Auth::getUserName(),
                    'title' => 'Ошибка',
                    'error_message' => 'Не удалось получить данные о вашем профиле.'
                ];
                $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
                return;
            }

            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'user_name' => Auth::getUserName(),
                'active' => "users",
                'roles' => $um->getRolesList(),
                'users' => $users,
                'isUserAdmin' => $isUserAdmin, // Передаем флаг в представление
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