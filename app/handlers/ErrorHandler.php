<?php
// /app/core/ErrorHandler.php

class ErrorHandler
{
    public static function register()
    {
        // Устанавливаем обработчики
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Обработка нефатальных ошибок (E_WARNING, E_NOTICE и т.д.)
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Не обрабатываем подавленные ошибки (@)
        if (error_reporting() === 0) {
            return;
        }

        $message = "PHP Error [$errno]: $errstr in $errfile:$errline";

        // Логируем всегда
        Logger::error($message);

        // В dev-режиме можно показать, но не прерываем выполнение
        if (self::isDev()) {
            // Можно добавить отладочную информацию, если нужно
        }
    }

    /**
     * Обработка необработанных исключений
     */
    public static function handleException($exception)
    {
        $message = "Uncaught Exception: " . $exception->getMessage();
        Logger::critical($message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        self::renderError(500, 'Произошла ошибка при обработке запроса.', $exception);
    }

    /**
     * Обработка фатальных ошибок (E_ERROR, E_PARSE и т.д.)
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            return;
        }

        $message = "Fatal Error [{$error['type']}]: {$error['message']} in {$error['file']}:{$error['line']}";
        Logger::critical($message);

        self::renderError(500, 'Внутренняя ошибка сервера.', null, $error);
    }

    /**
     * Формирует ответ в зависимости от типа запроса
     */
    private static function renderError($code, $userMessage, $exception = null, $fatalError = null)
    {
        // Убедимся, что заголовки не отправлены
        if (headers_sent() || PHP_SAPI === 'cli') {
            return;
        }

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        http_response_code($code);

        if ($isAjax) {
            header('Content-Type: application/json');

            $response = ['success' => false, 'error' => $userMessage];

            // Только в dev — показываем детали
            if (self::isDev()) {
                $response['debug'] = [
                    'type' => $exception ? get_class($exception) : 'Fatal Error',
                    'message' => $exception ? $exception->getMessage() : $fatalError['message'],
                    'file' => $exception ? $exception->getFile() : $fatalError['file'],
                    'line' => $exception ? $exception->getLine() : $fatalError['line'],
                ];
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            // Обычный HTML-ответ
            header('Content-Type: text/html; charset=utf-8');
            if (self::isDev()) {
                $details = $exception
                    ? "<pre><strong>File:</strong> {$exception->getFile()}:{$exception->getLine()}</pre><pre>{$exception->getTraceAsString()}</pre>"
                    : "<pre><strong>Fatal:</strong> {$fatalError['message']} in {$fatalError['file']}:{$fatalError['line']}</pre>";
            } else {
                $details = '';
            }

            echo "<h1>Ошибка {$code}</h1>
                  <p>{$userMessage}</p>
                  {$details}";
        }

        exit;
    }

    /**
     * Проверка режима: dev или prod
     */
    private static function isDev()
    {
        return Config::isDev();
    }
}