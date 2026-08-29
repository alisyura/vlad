<?php

// Логирование ошибок
function logError(string $message, ?Throwable $e = null) {
    $logFile = dirname(__DIR__, 2) . '/logs/cron_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $errMsg = "[$timestamp] ERROR: $message ";
    if (null !== $e)
    {
        $exceptionDetails = [
            'exception_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        $errMsg .= json_encode($exceptionDetails, JSON_UNESCAPED_UNICODE);
    }
    file_put_contents(
        $logFile,
        $errMsg . PHP_EOL,
        FILE_APPEND
    );
}

$rootPath = realpath(dirname(__DIR__, 2));

if ($rootPath === false) {
    $error = 'Не удалось определить корневую директорию проекта';
    logError($error);
    echo $error; // Для отладки, если вывод перенаправлен
    exit(1); // Код 1 = ошибка
}

define('ROOT_PATH', $rootPath);

require ROOT_PATH . '/vendor/autoload.php';
// Инициализируем и загружаем переменные окружения
try {
    // Получаем абсолютный путь к папке, где находится .env
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load(); 

    Config::initialize($_ENV);

    // Dotenv загружает все в $_ENV и getenv().
    // Сравниваем со строкой 'true', чтобы получить булево значение.
    $isDebug = $_ENV['APP_DEBUG'] ?? 'false'; // Используем 'false' как безопасное значение по умолчанию
    define('APP_DEBUG', $isDebug === 'true' || $isDebug === '1');
	
    // Проверить, что критические переменные установлены
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])->notEmpty();
} catch (Throwable $e) {
    logError('Ошибка загрузки .env: ', $e);
    exit(1);
}

require_once ROOT_PATH . '/app/bootstrap.php';

$llmsDescriptionLength = Config::get('global.LlmsDescriptionLength');

try
{
    $llmsService = $container->make(LlmsService::class);
    $llmsTxtResult = $llmsService->generateLlmsTxt($llmsDescriptionLength);

    if (!$llmsTxtResult['success'])
    {
        throw new FilesystemException('При вызове $llmsService->generateLlmsTxt получен результат false');
    }
} catch (Throwable $e) {
    logError('Ошибка вызова $llmsService->generateLlmsTxt. llmsDescriptionLength=' . $llmsDescriptionLength .': ', $e);
    exit(1);
}

exit(0);