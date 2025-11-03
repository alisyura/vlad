<?php
// app/controllers/AdminUsersController.php
class AdminUsersController extends BaseAdminController
{
    private UserService $userService;
    private UserModel $userModel;
    private AuthService $authService;

    public function __construct(View $view, UserService $userService,
        AuthService $authService, UserModel $userModel, ResponseFactory $responseFactory)
    {
        parent::__construct(null, $view, $responseFactory);
        $this->userService = $userService;
        $this->authService = $authService;
        $this->userModel = $userModel;
    }

    public function list(): Response
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

            return $this->renderHtml('admin/users/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in users list", [], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function edit(int $userId): Response
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
                throw new HttpException('Пользователь не найден.', 404);
            }
            
            $isUserAdmin = $this->authService->isUserAdmin();
            $loggedInUserId = $this->authService->getUserId();

            // Проверяем, существует ли пользователь и имеет ли текущий админ права на его редактирование
            if (!$isUserAdmin && $user['id'] != $loggedInUserId) {
                throw new HttpException('Недостаточно прав для редактирования этого пользователя.', 403);
            }
            
            $data['user_to_edit'] = $user;

            return $this->renderHtml('admin/users/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit user (show form): ", ['userId' => $userId], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }
}
