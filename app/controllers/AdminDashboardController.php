<?php

// app/controllers/AdminDashboardController.php

class AdminDashboardController extends BaseController
{
    public function dashboard() {
        $dm = new DashboardModel();

        $user_name = Auth::getUserName();

        // Получаем данные для dashboard
        $data = [
            'adminRoute' => $this->getAdminRoute(),
            'user_name' => $user_name,
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