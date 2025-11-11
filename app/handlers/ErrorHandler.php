<?php
// /app/core/ErrorHandler.php

class ErrorHandler
{
    private ErrorResponseFactory $errorFactory;
    private Request $request;

    // Свойство для хранения инстанса (для статического доступа к handleException)
    private static ?ErrorHandler $instance = null;

    public function __construct(ErrorResponseFactory $errorFactory, Request $request)
    {
        $this->errorFactory = $errorFactory;
        $this->request = $request;
        self::$instance = $this;
    }

    public static function register()
    {
        // Устанавливаем обработчики
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleUncaughtException']);
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
     * Обработка необработанных исключений (регистрируется в set_exception_handler)
     */
    public static function handleUncaughtException($exception)
    {
        // Делегируем работу инстансу, если он создан, или используем старый механизм 
        // для случаев, когда DI еще не сработал.
        if (self::$instance) {
            self::$instance->handleSystemError($exception, 500);
            return;
        }
        // Если DI еще не прошел, падаем на статический renderError
        self::handleExceptionFallback($exception);
    }

    /**
     * Fallback-логика для необработанных исключений в случае, когда DI не сработал.
     * ЭТО ВАША ОРИГИНАЛЬНАЯ ЛОГИКА ИЗ handleException.
     */
    public static function handleExceptionFallback($exception)
    {
        $message = "Uncaught Exception (Fallback): " . $exception->getMessage();
        Logger::critical($message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Используем статический метод renderError, который не требует зависимостей
        self::renderError(500, 'Произошла критическая ошибка при обработке запроса.', $exception);
    }

    /**
     * Формирует ответ на основе HttpException (логика, перенесенная из Router)
     */
    private function handleHttpExceptionResponse(HttpException $e): void
    {
        

        // 1. HTML_RESPONSE (обычно для 404/405/500 для страниц)
        if ($e->getResponseType() === HttpException::HTML_RESPONSE)
        {
            $this->errorFactory->createClientError(
                $e->getMessage(), 
                'Произошла непредвиденная ошибка.', 
                $e->getCode(),
                $this->isAdminArea()
            )->send();
            return;
        }
        
        // 2. JSON_RESPONSE (для API)
        if ($e->getResponseType() === HttpException::JSON_RESPONSE)
        {
            $prevException = $e->getPrevious();
            $errors = [];
            $statusCode = $e->getCode();
            $message = $e->getMessage();
            
            // Проверка на UserDataException (ошибки валидации)
            if ($prevException !== null && ($prevException instanceof UserDataException))
            {
                $errors = $prevException->getValidationErrors();
                // Используем код из HttpException ($e->getCode())
            }

            $this->errorFactory->createJsonError(
                $message,
                $statusCode,
                $errors
            )->send();
            return;
        }
    }

    /**
     * Обработка системных ошибок (Throwable) с логированием
     */
    private function handleSystemError(Throwable $e, int $code): void
    {
        Logger::critical("SYSTEM CRITICAL ERROR: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], $e);

        $this->errorFactory->createClientError(
            'Ошибка сервера 500', 
            'Произошла внутренняя ошибка сервера.', 
            $code,
            $this->isAdminArea()
        )->send();
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
     * Обработка исключений, пойманных роутером (HttpException, RouteException, Throwable)
     */
    public function handleCaughtException(Throwable $e): void
    {
        // Очищаем буфер перед отправкой нового ответа
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 1. HttpException: Клиентские ошибки (4xx) и Server Errors (5xx), 
        // брошенные контроллерами.
        if ($e instanceof HttpException) {
            $this->handleHttpExceptionResponse($e);
            return;
        }

        // 2. RouteException: Ошибка в роутере. Логируем, отправляем 500.
        if ($e instanceof RouteException) {
            Logger::error("RouteException: Ошибка в маршрутизаторе.", [], $e);
            $this->errorFactory->createClientError(
                'Проблема маршрутизации', 
                'Произошла непредвиденная ошибка в обработчике маршрута.', 
                500,
                $this->isAdminArea()
            )->send();
            return;
        }

        // 3. Throwable: Непредвиденный сбой (системная ошибка). Логируем, отправляем 500.
        $this->handleSystemError($e, 500);
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

            $response = ['success' => false, 'message' => $userMessage];

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
        return class_exists('Config') && Config::isDev();
    }

    private function isAdminArea(): bool
    {
        $requestUri=$this->request->getUri();
        return  str_starts_with($requestUri, '/'.Config::get('admin.AdminRoute'));
    }
}