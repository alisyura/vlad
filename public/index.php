<?php
// 1. Начинаем сессию
session_start();

// В начале index.php или bootstrap файла
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/',
        __DIR__ . '/../app/middleware/',
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/core/',
        __DIR__ . '/../app/config/',
        __DIR__ . '/../app/exceptions/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../app/core/Helpers.php';

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error($errstr, ['file' => $errfile, 'line' => $errline]);
});

$request = $_SERVER['REQUEST_URI'];
$router = new Router();
require_once __DIR__ . '/../app/routes.php'; // Подключаем маршруты
$router->dispatch($request);