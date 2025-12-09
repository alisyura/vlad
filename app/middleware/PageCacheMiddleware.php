<?php
// app/Middleware/PageCacheMiddleware.php

/**
 * Посредник (Middleware) для кэширования полных HTML-страниц.
 *
 * Проверяет наличие актуальной кэшированной версии страницы для GET-запросов.
 * Если кэш найден, он немедленно отправляется клиенту.
 * Если кэш отсутствует или устарел, буферизирует вывод и сохраняет его
 * в файл кэша в конце выполнения скрипта.
 */
class PageCacheMiddleware implements MiddlewareInterface
{
    /** @var string Директория для хранения файлов кэша. */
    private $cacheDir;

    /** @var int Время жизни кэша в секундах. */
    private $cacheLifetime;

    /** @var bool Флаг, указывающий, использовать ли кэш. */
    private $useCache;

    /**
     * Класс, представляющий HTTP-запрос.
     *
     * Предоставляет удобный интерфейс для доступа к данным запроса,
     * таким как заголовки, параметры, тело запроса и файлы.
     */
    private Request $request;

    private SettingsService $settingsService;

    /**
     * Конструктор.
     * Инициализирует свойства из конфигурации.
     */
    public function __construct(Request $request, SettingsService $settingsService)
    {
        $this->cacheDir = Config::get('cache.CacheDir');
        $this->cacheLifetime = Config::get('cache.CacheLifetime');
        $this->useCache = Config::get('cache.UseCache');
        $this->request = $request;
        $this->settingsService = $settingsService;
    }

    /**
     * Обрабатывает входящий HTTP-запрос.
     *
     * @param array|null $param Необязательные параметры, передаваемые в middleware.
     * @return bool Возвращает true для продолжения выполнения, если кэш не используется
     * или отсутствует. Если кэш найден, выполнение останавливается.
     */
    public function handle(?array $param = null): bool
    {
        // Убедимся, что это GET-запрос
        if (!$this->useCache || $this->request->server('REQUEST_METHOD') !== 'GET') {
            return true; // Продолжаем выполнение для POST и других
        }

        $cacheKey = $this->getCacheKey(); // Уникальный ключ для текущего запроса
        $cacheFile = $this->cacheDir . $cacheKey . '.html';

        // Проверяем, существует ли кэш и не истек ли срок его жизни
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheLifetime) {
            // Кэш найден и актуален, отдаем его
            // Устанавливаем заголовки (опционально, для лучшего UX)
            header('X-Cache: HIT');
            header('Content-Type: text/html; charset=utf-8');
            // Выводим содержимое кэша
            readfile($cacheFile);
            // Останавливаем дальнейшее выполнение
            exit; //так как выводим из кэша, return не нужен
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

    /**
     * Генерирует уникальный ключ для файла кэша на основе URL запроса.
     *
     * @return string Уникальный MD5-хеш для URL.
     */
    private function getCacheKey(): string
    {
        // Создаем уникальный ключ кэша на основе URI запроса
        // Можно также учитывать query parameters, если они важны
        $uri = $$this->request->server('REQUEST_URI');
        // Убираем query string для простоты, если она не влияет на содержимое
        $uri = strtok($uri, '?'); 
        // Хешируем, чтобы получить безопасное имя файла
        return md5($uri);
    }

    /**
     * Сохраняет буферизированный вывод в файл кэша.
     *
     * Эта функция вызывается автоматически в конце выполнения скрипта
     * с помощью `register_shutdown_function`. Она не сохраняет кэш,
     * если произошла фатальная ошибка.
     *
     * @param string $cacheFile Путь к файлу кэша.
     */
    public function saveCache($cacheFile)
    {
        // Не сохраняем кэш, если была фатальная ошибка
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            ob_end_flush();
            return;
        }

        // Эта функция будет вызвана автоматически в конце скрипта
         // благодаря register_shutdown_function
        $content = ob_get_contents(); // Получаем весь сгенерированный HTML
        ob_end_flush(); // Отправляем его в браузер как обычно

        if ($content !== false && trim($content) !== '') {
            // Сохраняем содержимое в файл кэша
            // Убедитесь, что директория существует
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($cacheFile, $content);
            header('X-Cache: MISS');
            // Опционально: логируем, что кэш был сохранен
            // Logger::info("Page cache saved: " . $cacheFile);
        }
    }
}
