<?php
// 1. Начинаем сессию
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => strtolower($_SERVER['REQUEST_SCHEME']) === 'https',
    'httponly' => true,    // недоступна из JS
    'samesite' => 'Strict' // или 'Lax'
]);
session_start();

require __DIR__ . '/../vendor/autoload.php';

// --- Обработка ошибок ---
// Регистрируем все обработчики ошибок
ErrorHandler::register();

require_once __DIR__ . '/../app/bootstrap.php';

// $request = RequestFactory::createFromGlobals();
// // временно. пока не отрефачена клиентская часть. 
// // впоследствии там тоже будет передавать View
// $uri = $request->getUri();
// $viewAdmin = null;
// $adminRoute = Config::get('admin.AdminRoute');
// if (str_starts_with($uri, "/{$adminRoute}")) {
//     $viewsRootPath = Config::get('global.ViewsRootPath');
//     $viewAdmin = new ViewAdmin(
//         $viewsRootPath,
//         'admin/login.php',
//         'admin/admin_layout.php'
//     );
// }

// --- Роутинг ---
$router = new Router();
require_once __DIR__ . '/../app/routes.php';
// $router->dispatch($request, $viewAdmin);
$router->dispatch($container);