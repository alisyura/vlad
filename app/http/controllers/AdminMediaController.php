<?php

// app/controllers/AdminMediaController.php

class AdminMediaController extends BaseAdminController
{
    private DashboardModel $model;
    private AuthService $authService;

    public function __construct(View $view, DashboardModel $model, 
        AuthService $authService, ResponseFactory $responseFactory)
    {
        parent::__construct(null, $view, $responseFactory);
        $this->model = $model;
        $this->authService = $authService;
    }

    public function show(): Response {
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
                'recent_activities' => $this->model->getRecentActivities(),
                'styles' => [
                    'mediateka.css'
                ],
                'jss' => [
                    'medialibrary.js',
                    'mediateka_init.js'
                ]
            ];
            
            // Здесь загружаем данные для админ-панели
            return $this->renderHtml('admin/media/mediateka.php', $data);
        } catch(Throwable $e) {
            Logger::error('Ошибка при открытии Dashboard', [], $e);
            throw new HttpException('Сбой при открытии Dashboard', 500, $e);
        }
    }
}