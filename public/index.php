<?php
// 1. Начинаем сессию
session_start();

require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Config.php';

spl_autoload_register(function ($class) {
    // 1. Создаем ассоциативный массив для сопоставления части имени класса с папкой
    $smartPaths = [
        'Controller'  => __DIR__ . '/../app/controllers/',
        'Model'       => __DIR__ . '/../app/models/',
        'Middleware'  => __DIR__ . '/../app/middleware/',
        'Interface'   => __DIR__ . '/../app/interfaces/',
        'Exception'   => __DIR__ . '/../app/exceptions/',
        // Добавьте другие правила по необходимости
    ];

    // 2. Ищем совпадение в имени класса и пытаемся загрузить файл
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
    
    // 3. Если "умный" поиск не дал результата, запускаем полный перебор
    $defaultPaths = [
        __DIR__ . '/../app/',
        __DIR__ . '/../app/core/',
        __DIR__ . '/../app/config/',
        // Добавьте другие папки, где могут быть файлы без специальных признаков
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

    // 4. Если ничего не найдено, выводим ошибку
    throw new Exception("Class {$class} not found.");
});


// В начале index.php или bootstrap файла
// spl_autoload_register(function ($class) {
//     $paths = [
//         __DIR__ . '/../app/',
//         __DIR__ . '/../app/middleware/',
//         __DIR__ . '/../app/controllers/',
//         __DIR__ . '/../app/models/',
//         __DIR__ . '/../app/core/',
//         __DIR__ . '/../app/config/',
//         __DIR__ . '/../app/exceptions/'
//     ];
    
//     foreach ($paths as $path) {
//         $file = $path . $class . '.php';
//         $realFile = realpath($file);
//         Logger::info("Попытка загрузить: " . $file . " (realpath: " . ($realFile ?: 'не найден') . ")\n");
//         if (file_exists($realFile)) {
//             require_once $realFile;
//             return;
//         }
//     }

//     Logger::error("Class {$class} not found. Searched in the following directories:\n".implode("\n", $paths));
// });

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error($errstr, ['file' => $errfile, 'line' => $errline]);
});

$request = $_SERVER['REQUEST_URI'];
$router = new Router();
require_once __DIR__ . '/../app/routes.php'; // Подключаем маршруты
$router->dispatch($request);