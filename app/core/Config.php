<?php
class Config
{
    // Изначально делаем массив $config пустым
    private static $config = []; 
    
    // Флаг, чтобы знать, инициализировали ли мы уже настройки
    private static $isInitialized = false;

    /**
     * Выполняет инициализацию настроек, считывая их из $_ENV.
     * Вызывается только один раз.
     */
    private static function initialize()
    {
        if (self::$isInitialized) {
            return;
        }

        // --- НАЧАЛО КОНФИГА ---
        self::$config = [
            'db' => [
                'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
                'DB_NAME' => $_ENV['DB_NAME'] ?? 'vlad',
                'DB_USER' => $_ENV['DB_USER'] ?? 'vlad',
                'DB_PASS' => $_ENV['DB_PASS'] ?? 'vlad'
            ],
            'posts' => [
                'exerpt_len' => 200,
                'posts_per_page' => 5,
                'max_urls_in_sitemap' => 50000,
                'allowed_tags' => '<p><b><i><strong><em><a><img><br><span><s><ul><li><ol><div>',
                // кол-во тэгов на странице поиска тэгов, когда ее тока открыли. без поиска
                'count_tags_without_query' => 10
            ],
            'global' => [
                'ViewsRootPath' => ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
                // Секретный ключ приложения, используется для шифрования/сессий
                'APP_SECRET_KEY' => $_ENV['APP_SECRET_KEY'] ?? null, 
            ],
            'logger' => [
                'UseDebugLogger' => true,
                'UseInfoLogger' => true,
                'UseWarningLogger' => true,
                'UseErrorLogger' => true,
                'UseCriticalLogger' => true,
                'LogPath' => ROOT_PATH . DIRECTORY_SEPARATOR . 'logs'
            ],
            'upload' => [
                'UploadDir' => 'uploads',
                'UploadedMaxFilesize' => 5 * 1024 * 1024,
                'UploadedMaxHeight' => 600,
                'UploadedMaxWidth' => 840,
                'UploadedMinHeight' => 300,
                'UploadedMinWidth' => 400
            ],
            'cache' => [
                'CacheDir' => ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR
            ],
            'admin' => [
                'AdminRoute' => 'eryfbh',
                'PostsPerPage' => 10,
                'EnableCreateCategory' => false,
                'EnableEditCategory' => false,
                'AdminRoleName' => 'Administrator',
                // урлы страниц, которые не будут показаны в списке страниц в админке
                'PagesToExclude' => ['sitemap', 'kontakty'],
                'PostsToExclude' => [],
                'TagsPerPage' => 10,
            ],
            'mail' => [
                'AdminEmail' => $_ENV['MAIL_ADMIN_USERNAME'] ?? 'admin@admin.ru',
                'MailFrom' => $_ENV['MAIL_FROM_USERNAME'] ?? 'noreply@admin.ru',
                'pw' => $_ENV['MAIL_PASSWORD'] ?? 'default_password',
                'SMTPServer' => $_ENV['MAIL_HOST'] ?? 'localhost',
                'SMTPPort' => (int)($_ENV['MAIL_PORT'] ?? 465)
            ]
        ];
        // --- КОНЕЦ КОНФИГА ---

        self::$isInitialized = true;
    }

    /**
     * Получает значение из конфигурации по ключу.
     *
     * Поддерживает вложенные ключи в формате 'section.property'.
     * Если ключ не найден, возвращает значение по умолчанию.
     *
     * @param string $key Ключ конфигурации, например 'posts.exerpt_len'.
     * @param mixed $default Значение, которое будет возвращено, если ключ не найден.
     * @return mixed Значение конфигурации или значение по умолчанию.
     */
    public static function get(string $key, $default = null)
    {
        self::initialize();

        // Проверяем, содержит ли ключ точку для доступа к вложенным элементам.
        if (strpos($key, '.') === false) {
            return self::$config[$key] ?? $default;
        }

        // Разбиваем ключ на секцию и свойство.
        [$section, $property] = explode('.', $key, 2);

        // Возвращаем значение, используя оператор объединения с null.
        // Если секция или свойство не существуют, возвращается значение по умолчанию.
        return self::$config[$section][$property] ?? $default;
    }

    public static function isDev(): bool
    {
        // Константа APP_DEBUG определена в index.php 
        // после загрузки .env и ДО инициализации Config.
        if (!defined('APP_DEBUG')) {
            // Аварийный отказ: Если константа не определена, 
            // предполагаем, что это продакшн, чтобы не раскрыть ошибки.
            return false;
        }

        return APP_DEBUG;
    }

    public static function getConfigValue(array $settings, string $key, $default)
    {
        // Проверяем, существует ли ключ в массиве $settings
        if (!isset($settings[$key])) {
            return $default;
        }
        
        $configItem = $settings[$key];
        
        // Если это массив и в нём есть ключ 'value', возвращаем его
        if (is_array($configItem) && isset($configItem['value'])) {
            return $configItem['value'];
        }
        
        // Иначе возвращаем сам элемент (для обратной совместимости)
        return $configItem;
    }
}