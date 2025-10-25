<?php

// app/controllers/AdminDashboardController.php

class AdminDashboardController extends BaseAdminController
{
    use ShowAdminErrorViewTrait;

    private DashboardModel $model;
    private AuthService $authService;

    public function __construct(View $view, DashboardModel $model, AuthService $authService)
    {
        parent::__construct(null, $view);
        $this->model = $model;
        $this->authService = $authService;
    }

    public function dashboard() {
        $userName = $this->authService->getUserName();

        try {

            // Получаем данные для dashboard
            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'user_name' => $userName,
                'title' => 'Dashboard',
                'active' => 'dashboard', // для подсветки в меню
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'posts_count' => $this->model->getPostsCount(),
                'pages_count' => $this->model->getPagesCount(),
                'users_count' => $this->model->getUsersCount(),
                'recent_activities' => $this->model->getRecentActivities()
            ];
            
            // Здесь загружаем данные для админ-панели
            $this->view->renderAdmin('admin/dashboard.php', $data);
        } catch(Throwable $e) {
            Logger::error('Ошибка при открытии Dashboard', [], $e);
            $this->showAdminErrorView('Ошибка при открытии Dashboard','Сбой при открытии Dashboard', $userName);
        }
    }
}