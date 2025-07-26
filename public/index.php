<?php
// 1. Начинаем сессию
session_start();

require_once __DIR__ . '/../app/core/CSRF.php';

require_once __DIR__ . '/../app/core/Config.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/routes.php';
require_once __DIR__ . '/../app/controllers/PostController.php';

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error($errstr, ['file' => $errfile, 'line' => $errline]);
});

$request = $_SERVER['REQUEST_URI'];
$router->dispatch($request);