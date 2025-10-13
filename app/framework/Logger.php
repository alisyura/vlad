<?php

class Logger
{
    // Возможные уровни логгирования
    private const LEVEL_DEBUG = 'DEBUG';
    private const LEVEL_INFO = 'INFO';
    private const LEVEL_WARNING = 'WARNING';
    private const LEVEL_ERROR = 'ERROR';
    private const LEVEL_CRITICAL = 'CRITICAL';

    // Маппинг уровня лога к имени файла
    private const LOG_FILES = [
        self::LEVEL_DEBUG => 'debug.log',
        self::LEVEL_INFO => 'info.log',
        self::LEVEL_WARNING => 'warning.log',
        self::LEVEL_ERROR => 'error.log',
        self::LEVEL_CRITICAL => 'critical.log',
    ];

    private static function log_message($level, $message, $context = [], ?\Throwable $e = null)
    {
        // Проверяем, существует ли такой уровень логгирования
        if (!array_key_exists($level, self::LOG_FILES)) {
            $level = self::LEVEL_INFO; // Уровень по умолчанию
        }

        if (null !== $e) {
            // Создаем отдельный массив с деталями исключения
            $exceptionDetails = [
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            // Детали исключения записываются под ключом 'exception_info', 
            // чтобы не смешивать их с пользовательским контекстом
            $context['exception_info'] = $exceptionDetails;
        }

        $logMessage = date('[Y-m-d H:i:s]') . " [$level] $message " . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $logPath = rtrim(Config::get('logger.LogPath'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::LOG_FILES[$level];

        @file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
    }

    // Специализированные методы для каждого уровня
    public static function debug($message, $context = [], ?\Throwable $e = null)
    {        
        if (!Config::get('logger.UseDebugLogger')) {
            return;
        }
        self::log_message(self::LEVEL_DEBUG, $message, $context, $e);
    }

    public static function info($message, $context = [], ?\Throwable $e = null)
    {
        if (!Config::get('logger.UseInfoLogger')) {
            return;
        }
        self::log_message(self::LEVEL_INFO, $message, $context, $e);
    }

    public static function warning($message, $context = [], ?\Throwable $e = null)
    {
        if (!Config::get('logger.UseWarningLogger')) {
            return;
        }
        self::log_message(self::LEVEL_WARNING, $message, $context, $e);
    }

    public static function error($message, $context = [], ?\Throwable $e = null)
    {
        if (!Config::get('logger.UseErrorLogger')) {
            return;
        }
        self::log_message(self::LEVEL_ERROR, $message, $context, $e);
    }

    public static function critical($message, $context = [], ?\Throwable $e = null)
    {
        if (!Config::get('logger.UseCriticalLogger')) {
            return;
        }
        self::log_message(self::LEVEL_CRITICAL, $message, $context, $e);
    }
}