<?php

class UserService
{
    private UserModel $userModel;
    private bool $isUserAdmin;
    private bool $currentUserId;

    public function __construct(AuthService $authService, UserModel $userModel)
    {
        $this->userModel = $userModel;
        $this->isUserAdmin = $authService->isUserAdmin();
        $this->currentUserId = $authService->getUserId();
    }

    /**
     * Получает список пользователей и ролей в зависимости от прав текущего пользователя.
     * @return array
     */
    public function getUsersAndRolesData(): array
    {
        // $isUserAdmin = Auth::isUserAdmin();
        // $currentUserId = Auth::getUserId();

        // Получаем список пользователей в зависимости от роли
        $users = $this->isUserAdmin 
            ? $this->userModel->getAllUsersList() 
            : [$this->userModel->getUser(id: $this->currentUserId)];
        
        // ВАЖНО: getUserById возвращает один массив, getAllUsersList - массив массивов.
        // Чтобы унифицировать, оборачиваем результат getUserById в массив.
        if (!$this->isUserAdmin && $users[0] === false) {
             throw new \UserDataException('Не удалось получить данные о вашем профиле.');
        }

        return [
            'isUserAdmin' => $this->isUserAdmin,
            'users' => $users,
            'roles' => $this->userModel->getRolesList(),
        ];
    }
}
