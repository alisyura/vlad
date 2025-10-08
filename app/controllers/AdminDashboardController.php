<?php

// app/controllers/AdminDashboardController.php

class AdminDashboardController extends BaseController
{
    private DashboardModel $model;
    private AuthService $authService;

    public function __construct(View $view, DashboardModel $model, AuthService $authService)
    {
        parent::__construct(null, $view);
        $this->model = $model;
        $this->authService = $authService;
    }

    public function dashboard() {
        $user_name = $this->authService->getUserName();

        // Получаем данные для dashboard
        $data = [
            'adminRoute' => $this->getAdminRoute(),
            'user_name' => $user_name,
            'title' => 'Dashboard',
            'active' => 'dashboard', // для подсветки в меню
            'posts_count' => $this->model->getPostsCount(),
            'pages_count' => $this->model->getPagesCount(),
            'users_count' => $this->model->getUsersCount(),
            'recent_activities' => $this->model->getRecentActivities()
        ];
        
        // Здесь загружаем данные для админ-панели
        $this->view->renderAdmin('admin/dashboard.php', $data);
    }
}