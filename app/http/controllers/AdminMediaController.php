<?php

// app/controllers/AdminMediaController.php

class AdminMediaController extends BaseAdminController
{
    private AuthService $authService;

    public function __construct(View $view, AuthService $authService, 
        ResponseFactory $responseFactory)
    {
        parent::__construct(null, $view, $responseFactory);
        $this->authService = $authService;
    }

    public function show(): Response {
        $userName = $this->authService->getUserName();

        try {

            // Получаем данные для dashboard
            $data = [
                'adminRoute' => $this->getAdminRoute(),
                // для вывода кнопки раздела пользователи в левом меню
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'user_name' => $userName,
                'pageTitle' => 'Управление медиафайлами',
                'active' => AdminLeftMenuItems::MENU_MEDIATEKA, // для подсветки в меню
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
            Logger::error('Ошибка при открытии Медиатеки', [], $e);
            throw new HttpException('Сбой при открытии Медиатеки', 500, $e);
        }
    }
}