<?php
// app/controllers/AdminUsersController.php
class AdminUsersController extends BaseController
{
    use ShowAdminErrorViewTrait;

    private UserService $userService;
    private UserModel $userModel;

    public function __construct(Request $request, View $view)
    {
        parent::__construct($request, $view);
        $this->userService = new UserService();
        $pdo = Database::getConnection();
        $this->userModel = new UserModel($pdo);
    }

    public function list()
    {
        $userName = Auth::getUserName();
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            $this->view->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list: " . $e->getTraceAsString());
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }

    public function edit(int $userId)
    {
        $userName = Auth::getUserName();

        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            // Получаем данные конкретного пользователя для формы редактирования
            $user = $this->userModel->getUser(id: $userId);
            if (empty($user))
            {
                $this->showAdminErrorView('Ошибка', 'Пользователь не найден.', $userName);
                return;
            }
            
            // Проверяем, существует ли пользователь и имеет ли текущий админ права на его редактирование
            if (!$data['isUserAdmin'] && $user['id'] != Auth::getUserId()) {
                $this->showAdminErrorView('Ошибка', 'Недостаточно прав для редактирования этого пользователя.', $userName);
                return;
            }
            
            $data['user_to_edit'] = $user;
            $this->view->renderAdmin('admin/users/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit user (show form): " . $e->getTraceAsString());
            $this->showAdminErrorView('Ошибка', 'Произошла непредвиденная ошибка.', $userName);
        }
    }
}
