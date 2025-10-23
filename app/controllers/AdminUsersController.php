<?php
// app/controllers/AdminUsersController.php
class AdminUsersController extends BaseController
{
    use ShowAdminErrorViewTrait;

    private UserService $userService;
    private UserModel $userModel;
    private string $userName;
    private string $isUserAdmin;
    private int $loggedInUserId;

    public function __construct(View $view, UserService $userService,
        AuthService $authService, UserModel $userModel)
    {
        parent::__construct(null, $view);
        $this->userService = $userService;
        $this->userName = $authService->getUserName();
        $this->isUserAdmin = $authService->isUserAdmin();
        $this->loggedInUserId = $authService->getUserId();
        $this->userModel = $userModel;
    }

    public function list()
    {
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $this->userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->isUserAdmin;
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            $this->view->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list", [], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $this->userName);
        }
    }

    public function edit(int $userId)
    {
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $this->userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            // Получаем данные пользователя из формы для редактирования
            $user = $this->userModel->getUser(id: $userId);
            if (empty($user))
            {
                $this->showAdminErrorView('Ошибка', 'Пользователь не найден.', $this->userName);
                return;
            }
            
            // Проверяем, существует ли пользователь и имеет ли текущий админ права на его редактирование
            if (!$this->isUserAdmin && $user['id'] != $this->loggedInUserId) {
                $this->showAdminErrorView('Ошибка', 'Недостаточно прав для редактирования этого пользователя.', $this->userName);
                return;
            }
            
            $data['user_to_edit'] = $user;
            $this->view->renderAdmin('admin/users/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit user (show form): ", ['userId' => $userId], $e);
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $this->userName);
        }
    }
}
