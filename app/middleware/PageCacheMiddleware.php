<?php
// app/Middleware/PageCacheMiddleware.php

class PageCacheMiddleware
{
    private static $cacheDir = __DIR__ . '/../../cache/pages/'; // Убедитесь, что папка существует и доступна для записи
    private static $cacheLifetime = 3600; // Время жизни кэша в секундах (1 час)

    public function handle(): bool
    {
        // Убедимся, что это GET-запрос
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return true; // Продолжаем выполнение для POST и других
        }

        $cacheKey = $this->getCacheKey(); // Уникальный ключ для текущего запроса
        $cacheFile = self::$cacheDir . $cacheKey . '.html';

        // Проверяем, существует ли кэш и не истек ли срок его жизни
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::$cacheLifetime) {
            // Кэш найден и актуален, отдаем его
            // Устанавливаем заголовки (опционально, для лучшего UX)
            header('X-Cache: HIT');
            header('Content-Type: text/html; charset=utf-8');
            // Выводим содержимое кэша
            readfile($cacheFile);
            // Останавливаем дальнейшее выполнение
            exit;
        } else {
             // Кэш отсутствует или устарел
             // Регистрируем функцию для сохранения вывода в кэш после генерации страницы
             ob_start(); // Начинаем буферизацию вывода
            
             // Продолжаем выполнение, чтобы контроллер мог сгенерировать страницу
             // Мы сохраним результат в кэш в конце запроса (см. ниже)
             register_shutdown_function([$this, 'saveCache'], $cacheFile);
            
             // Сообщаем роутеру, что нужно продолжить выполнение
             return true;
        }
    }

    private function getCacheKey(): string
    {
        // Создаем уникальный ключ кэша на основе URI запроса
        // Можно также учитывать query parameters, если они важны
        $uri = $_SERVER['REQUEST_URI'];
        // Убираем query string для простоты, если она не влияет на содержимое
        $uri = strtok($uri, '?'); 
        // Хешируем, чтобы получить безопасное имя файла
        return md5($uri);
    }
    
    public function saveCache($cacheFile) {
         // Эта функция будет вызвана автоматически в конце скрипта
         // благодаря register_shutdown_function
         $content = ob_get_contents(); // Получаем весь сгенерированный HTML
         ob_end_flush(); // Отправляем его в браузер как обычно
         
         if ($content !== false) {
              // Сохраняем содержимое в файл кэша
              // Убедитесь, что директория существует
              $dir = dirname($cacheFile);
              if (!is_dir($dir)) {
                  mkdir($dir, 0755, true);
              }
              file_put_contents($cacheFile, $content);
              // Опционально: логируем, что кэш был сохранен
              // error_log("Page cache saved: " . $cacheFile);
         }
    }
}
