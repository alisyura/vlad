<?php
// app/controllers/AdminUsersController.php
class AdminUsersController extends BaseController
{
    private UserService $userService;
    private UserModel $userModel;

    public function __construct(ViewAdmin $view)
    {
        parent::__construct($view);
        $this->userService = new UserService();
        $this->userModel = new UserModel();
    }
    
    public function list()
    {
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = Auth::getUserName();
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            $this->viewAdmin->renderAdmin('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list: " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    public function edit(int $userId)
    {
        try {
            $data = $this->userService->getUsersAndRolesData();

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = Auth::getUserName();
            $data['active'] = "users"; // подсветка вкладки левого меню
            $data['styles'] = ['users.css'];
            $data['jss'] = ['users.js'];
            
            // Получаем данные конкретного пользователя для формы редактирования
            $user = $this->userModel->getUser(id: $userId);
            if (empty($user))
            {
                $this->showAdminError('Ошибка', 'Пользователь не найден.');
                return;
            }
            
            // Проверяем, существует ли пользователь и имеет ли текущий админ права на его редактирование
            if (!$data['isUserAdmin'] && $user['id'] != Auth::getUserId()) {
                $this->showAdminError('Ошибка', 'Недостаточно прав для редактирования этого пользователя.');
                return;
            }
            
            $data['user_to_edit'] = $user;
            $this->viewAdmin->renderAdmin('admin/users/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit user (show form): " . $e->getTraceAsString());
            $this->showAdminError('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }
}
