<?php
// 1. Начинаем сессию
session_start();

// 2. Генерируем CSRF-токен, если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(50));
}

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