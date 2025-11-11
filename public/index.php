<?php
// Начинаем сессию
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
require_once __DIR__ . '/../app/bootstrap.php';

// --- Получаем зависимости, необходимые для роутинга и ошибок ---
$respFact = $container->make(ResponseFactory::class);
$errorHandler = $container->make(ErrorHandler::class);

// --- Обработка ошибок ---
// Регистрируем все обработчики ошибок
ErrorHandler::register();

// --- Роутинг ---
$router = new Router($respFact, $errorHandler);
require_once __DIR__ . '/../app/routes.php';
$router->dispatch($container);