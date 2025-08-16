<?php
// 1. Начинаем сессию
session_start();

require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Config.php';
require_once __DIR__ . '/../app/Handlers/ErrorHandler.php';
require_once __DIR__ . '/../app/core/CheckVisitor.php';

// --- Обработка ошибок ---
// Регистрируем все обработчики ошибок
ErrorHandler::register();

// --- Автозагрузка классов ---
spl_autoload_register(function ($class) {
    $smartPaths = [
        'Controller'  => __DIR__ . '/../app/controllers/',
        'Model'       => __DIR__ . '/../app/models/',
        'Middleware'  => __DIR__ . '/../app/middleware/',
        'Interface'   => __DIR__ . '/../app/interfaces/',
        'Exception'   => __DIR__ . '/../app/exceptions/',
    ];

    foreach ($smartPaths as $keyword => $path) {
        if (strpos($class, $keyword) === (strlen($class) - strlen($keyword))) {
            $file = $path . $class . '.php';
            $realFile = realpath($file);
            Logger::info("Попытка загрузить: " . $file . " (realpath: " . ($realFile ?: 'не найден') . ")\n");
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    $defaultPaths = [
        __DIR__ . '/../app/',
        __DIR__ . '/../app/core/',
        __DIR__ . '/../app/config/',
    ];

    foreach ($defaultPaths as $path) {
        $file = $path . $class . '.php';
        $realFile = realpath($file);
        Logger::info("Попытка загрузить: " . $file . " (realpath: " . ($realFile ?: 'не найден') . ")\n");
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    throw new Exception("Class {$class} not found.");
});


// --- Роутинг ---
$request = $_SERVER['REQUEST_URI'];
$router = new Router();
require_once __DIR__ . '/../app/routes.php';
$router->dispatch($request);