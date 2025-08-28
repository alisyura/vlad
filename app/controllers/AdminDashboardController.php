<?php

// app/controllers/AdminDashboardController.php

class AdminDashboardController
{
    use CheckIfUserLoggedInTrait;

    private $viewAdmin;

    public function __construct(ViewAdmin $viewAdmin)
    {
        $this->viewAdmin = $viewAdmin;
    }

    public function dashboard() {
        $this->checkIfUserLoggedIn();

        $dm = new DashboardModel();

        $adminRoute = Config::get('admin.AdminRoute');
        $user_name = Auth::getUserName();

        // Получаем данные для dashboard
        $data = [
            'adminRoute' => $adminRoute,
            'title' => 'Dashboard',
            'active' => 'dashboard', // для подсветки в меню
            'posts_count' => $dm->getPostsCount(),
            'pages_count' => $dm->getPagesCount(),
            'users_count' => $dm->getUsersCount(),
            'recent_activities' => $dm->getRecentActivities()
        ];
        
        // Здесь загружаем данные для админ-панели
        $this->viewAdmin->renderAdmin('admin/dashboard.php', $data);
    }
}