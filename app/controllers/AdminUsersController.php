<?php
// app/controllers/AdminUsersController.php
class AdminUsersController extends BaseAdminController
{
    use ShowAdminErrorViewTrait;

    private UserService $userService;
    private UserModel $userModel;
    private AuthService $authService;

    public function __construct(View $view, UserService $userService,
        AuthService $authService, UserModel $userModel)
    {
        parent::__construct(null, $view);
        $this->userService = $userService;
        $this->authService = $authService;
        $this->userModel = $userModel;
    }

    public function list()
    {
        $userName = $this->authService->getUserName();

        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            $this->view->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list", [], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }

    public function edit(int $userId)
    {
        $userName = $this->authService->getUserName();

        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            // Получаем данные пользователя из формы для редактирования
            $user = $this->userModel->getUser(id: $userId);
            if (empty($user))
            {
                $this->showAdminErrorView('Ошибка', 'Пользователь не найден.', $userName);
                return;
            }
            
            $isUserAdmin = $this->authService->isUserAdmin();
            $loggedInUserId = $this->authService->getUserId();

            // Проверяем, существует ли пользователь и имеет ли текущий админ права на его редактирование
            if (!$isUserAdmin && $user['id'] != $loggedInUserId) {
                $this->showAdminErrorView('Ошибка', 'Недостаточно прав для редактирования этого пользователя.', $userName);
                return;
            }
            
            $data['user_to_edit'] = $user;
            $this->view->renderAdmin('admin/users/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit user (show form): ", ['userId' => $userId], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }
}
