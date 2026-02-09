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
    private string $cacheDir;

    /** @var int Время жизни кэша в секундах. */
    private int $cacheLifetime;

    /** @var bool Флаг, указывающий, использовать ли кэш. */
    private bool $useCache;

    /**
     * Класс, представляющий HTTP-запрос.
     *
     * Предоставляет удобный интерфейс для доступа к данным запроса,
     * таким как заголовки, параметры, тело запроса и файлы.
     */
    private Request $request;

    private SettingsService $settingsService;

    private AuthService $authService;

    /**
     * Конструктор.
     * Инициализирует свойства из конфигурации.
     */
    public function __construct(Request $request, SettingsService $settingsService,
        AuthService $authService)
    {
        $this->request = $request;
        $this->settingsService = $settingsService;
        $this->authService = $authService;
        ['cacheDir' => $this->cacheDir, 
         'cacheEnabled' => $this->useCache,
         'cacheLifetime' => $this->cacheLifetime] = $this->fillCacheSettings();
    }

    /**
     * Извлекает настройки кэширования (включение/выключение, время жизни)
     * и директорию кэша, нормализуя их типы.
     *
     * Настройки 'cache_enabled' и 'cache_lifetime' извлекаются из сервиса настроек 
     * и приводятся к булеву и целочисленному типу соответственно, 
     * используя значения по умолчанию в случае отсутствия.
     *
     * @return array{cacheDir: string, cacheEnabled: bool, cacheLifetime: int}
     * Массив, содержащий нормализованные настройки кэша:
     * - 'cacheDir' (string): Путь к директории кэша.
     * - 'cacheEnabled' (bool): Флаг включения/выключения кэша.
     * - 'cacheLifetime' (int): Время жизни кэша в секундах (по умолчанию 3600).
     */
    private function fillCacheSettings(): array
    {
        $cacheDir = self::getCacheDir();
        $settings = $this->settingsService->getMassSeoSettings([
            'cache_enabled',
            'cache_lifetime'
        ]);

        $cacheEnabled = (bool) Config::getConfigValue($settings, 'cache_enabled', false);
        $cacheLifetime = (int) Config::getConfigValue($settings, 'cache_lifetime', 3600);

        return ['cacheDir' => $cacheDir, 
                'cacheEnabled' => $cacheEnabled,
                'cacheLifetime' => $cacheLifetime];
    }

    private static function getCacheDir(): string
    {
        return Config::get('cache.CacheDir');
    }

    private static function getCacheFilename($cacheDir, $cacheKey): string
    {
        return $cacheDir . $cacheKey . '.html';
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
        $method = $this->request->server('REQUEST_METHOD');
        if (!$this->useCache || !in_array($method, ['GET', 'HEAD'])) {
            return true;
        }

        $cacheKey = $this->getCacheKey(); // Уникальный ключ для текущего запроса
        $cacheFile = self::getCacheFilename($this->cacheDir, $cacheKey);

        // Проверяем, существует ли кэш и не истек ли срок его жизни
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheLifetime) {
            // Кэш найден и актуален, отдаем его
            // Устанавливаем заголовки (опционально, для лучшего UX)
            
            if ($this->authService->isUserAdmin())
            {
                // для админа кэш не используем. продолжаем выполнять контроллер
                return true;
            }

            if ($method === 'HEAD') {
                // Для HEAD запроса: только заголовки, без тела
                header_remove(); 
                header('Cache-Control: public, max-age='.$this->cacheLifetime);
                header('Content-Type: text/html; charset=utf-8');
                header('X-Cache: HIT-GZ');
                exit; // Выходим, тело не отправляем
            }

            // Удаляем всё, что PHP наставил по умолчанию (включая no-cache и версию PHP)
            header_remove(); 
            header('Cache-Control: public, max-age='.$this->cacheLifetime); // Разрешаем браузеру хранить. Время из конфига
            header('Content-Type: text/html; charset=utf-8');
            header('X-Cache: HIT-GZ'); // Пометим, что это сжатый хит

            $this->setContentType($cacheFile);

            $supportsGzip = str_contains($this->request->server('HTTP_ACCEPT_ENCODING', ''), 'gzip');

            if ($supportsGzip) {
                header('Content-Encoding: gzip');
                // Выводим содержимое кэша
                readfile($cacheFile);
            } else {
                // Если вдруг не поддерживает - распаковываем обратно перед отдачей
                echo gzdecode(file_get_contents($cacheFile));
            }

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

    private function setContentType(string $cacheFile): void
    {
        // Читаем первые 10 символов файла, чтобы узнать тип
        $handle = fopen($cacheFile, 'r');
        $start = fread($handle, 10);
        fclose($handle);

        if (str_contains($start, '<?xml')) {
            header('Content-Type: application/xml; charset=utf-8');
        } else {
            header('Content-Type: text/html; charset=utf-8');
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
        $uri = $this->request->server('REQUEST_URI');
        return self::encryptUri($uri);
    }

    private static function encryptUri($uri): string
    {
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

        $content = ob_get_contents(); // Получаем весь сгенерированный HTML
        if (!headers_sent()) {
            header('X-Cache: MISS');
        }
        ob_end_flush(); // Отправляем его в браузер как обычно

        if ($content !== false && trim($content) !== '') {
            $minifyHtml = Config::get('cache.MinificateHtml') ?? true;

            if ($minifyHtml) {
                // Теперь в кэше будет лежать сжатая версия
                $content = $this->minifyHtml($content);
            }

            // Сохраняем содержимое в файл кэша
            // Убедитесь, что директория существует
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $compressedContent = gzencode($content, 9);

            file_put_contents($cacheFile, $compressedContent);
        }
    }

    private function minifyHtml(string $html): string 
    {
        $search = [
            '/(?<=>)\s+(?=<)/', // Удаляем пробелы между тегами
            '//s',  // Удаляем HTML-комментарии
        ];
        $replace = ['', '']; // Между тегами лучше заменять на пустоту
        
        return trim(preg_replace($search, $replace, $html));
    }

    /**
     * Удаляет файл кэша для конкретного URI.
     * @param string $uri Например, '/p3' или '/blog/my-post'
     */
    public static function invalidate(string $uri): bool
    {
        $cacheDir = self::getCacheDir();
        $cacheKey = self::encryptUri($uri);
        $cacheFile = self::getCacheFilename($cacheDir, $cacheKey);

        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return false;
    }
}
