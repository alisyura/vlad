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

define('ROOT_PATH', dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

// Инициализируем и загружаем переменные окружения
try {
    // Получаем абсолютный путь к папке, где находится .env (обычно это __DIR__)
    $projectRoot = dirname(__DIR__); 
    $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
    $dotenv->load(); 

    Config::initialize($_ENV);

    // Dotenv загружает все в $_ENV и getenv().
    // Сравниваем со строкой 'true', чтобы получить булево значение.
    $isDebug = $_ENV['APP_DEBUG'] ?? 'false'; // Используем 'false' как безопасное значение по умолчанию
    define('APP_DEBUG', $isDebug === 'true' || $isDebug === '1');
	
    // Проверить, что критические переменные установлены
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])->notEmpty();
} catch (\Exception $e) {
    Logger::critical('Ошибка загрузки .env: ', [], $e);

    http_response_code(500);

    $errorTemplatePath = dirname(__DIR__) . '/app/views/errors/general_failure.html';
    
    if (file_exists($errorTemplatePath)) {
        // Подключаем файл. В нем будет доступна переменная $e
        include $errorTemplatePath;
    } else {
        // Если даже шаблон не найден, выводим голую ошибку
        die("Critical Server Error. Template file missing.");
    }
    exit();
}

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