<?php

class UserService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Получает список пользователей и ролей в зависимости от прав текущего пользователя.
     * @return array
     */
    public function getUsersAndRolesData(): array
    {
        $isUserAdmin = Auth::isUserAdmin();
        $currentUserId = Auth::getUserId();

        // Получаем список пользователей в зависимости от роли
        $users = $isUserAdmin 
            ? $this->userModel->getAllUsersList() 
            : [$this->userModel->getUser(id: $currentUserId)];
        
        // ВАЖНО: getUserById возвращает один массив, getAllUsersList - массив массивов.
        // Чтобы унифицировать, оборачиваем результат getUserById в массив.
        if (!$isUserAdmin && $users[0] === false) {
             throw new \Exception('Не удалось получить данные о вашем профиле.');
        }

        return [
            'isUserAdmin' => $isUserAdmin,
            'users' => $users,
            'roles' => $this->userModel->getRolesList(),
        ];
    }
}
