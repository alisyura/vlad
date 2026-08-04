<?php
class Config
{
    // Изначально делаем массив $config пустым
    private static $config = []; 
    
    // Флаг, чтобы знать, инициализировали ли мы уже настройки
    private static $isInitialized = false;

    /**
     * Выполняет инициализацию настроек.
     * Теперь принимает массив данных (обычно $_ENV или $_SERVER) напрямую.
     *
     * @param array|null $data Данные окружения
     */
    public static function initialize(?array $data = null)
    {
        // Если конфиг уже инициализирован, не пересобираем его
        if (self::$isInitialized && $data === null) {
            return;
        }

        // Если данные не переданы явно, пытаемся взять их из глобальных массивов
        // Но приоритет отдаем тому, что пришло в аргументе.
        $source = $data ?? $_ENV;

        // На всякий случай проверяем $_SERVER, если в $source пусто
        if (empty($source['DB_HOST']) && isset($_SERVER['DB_HOST'])) {
            $source = $_SERVER;
        }

        self::$config = [
            'db' => [
                'DB_HOST' => $source['DB_HOST'] ?? 'localhost',
                'DB_NAME' => $source['DB_NAME'] ?? '',
                'DB_USER' => $source['DB_USER'] ?? '',
                'DB_PASS' => $source['DB_PASS'] ?? ''
            ],
            'posts' => [
                'exerpt_len' => 200,
                'ExcerptCategories' => ['istorii'],
                'posts_per_page' => 20,
                'max_urls_in_sitemap' => 50000,
                'allowed_tags' => '<p><b><i><strong><em><a><img><br><span><s><ul><li><ol><div><iframe>',
                'maxTagsInResult' => 0, // кол-во отображаемых тэгов на странице поиска по тэгам. <=0 - показать все
                'LikesCountLuchshee' => 2 // кол-во лайков у поста для вывода его в категории Лучшее
            ],
            'global' => [
                'ViewsRootPath' => defined('ROOT_PATH') ? ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' : '',
                'LlmsDescriptionLength' => 250
            ],
            'logger' => [
                'UseDebugLogger' => true,
                'UseInfoLogger' => true,
                'UseWarningLogger' => true,
                'UseErrorLogger' => true,
                'UseCriticalLogger' => true,
                'LogPath' => defined('ROOT_PATH') ? ROOT_PATH . DIRECTORY_SEPARATOR . 'logs' : 'logs'
            ],
            'upload' => [
                'UploadDir' => 'uploads',
                'UploadedMaxFilesize' => 5 * 1024 * 1024,
                'UploadedMaxHeight' => 600,
                'UploadedMaxWidth' => 840,
                'UploadedMinHeight' => 300,
                'UploadedMinWidth' => 400
            ],
            'media' => [
                // Кол-во картинок на странице в медиатеке
                'MediaPageSize' => 25
            ],
            'cache' => [
                'CacheDir' => defined('ROOT_PATH') ? ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR : '',
                'MinificateCSS' => true,
                'MinificateJS' => true,
                'MinificateHtml' => true
            ],
            'admin' => [
                'AdminRoute' => 'eryfbh',
                'PostsPerPage' => 10,
                'EnableCreateCategory' => false,
                'EnableEditCategory' => false,
                'AdminRoleName' => 'Administrator',
                'PagesToExclude' => ['sitemap', 'kontakty'],
                'PostsToExclude' => [],
                'TaxonomiesPerPage' => 10,
                'LoginAttempts' => 5,
                'LoginBlockMinutes' => 120,
                'AutoLogoutMinutes' => 30
            ],
            'mail' => [
                'AdminEmail' => $source['MAIL_ADMIN_USERNAME'] ?? 'admin@admin.ru',
                'MailFrom' => $source['MAIL_FROM_USERNAME'] ?? 'noreply@admin.ru',
                'pw' => $source['MAIL_PASSWORD'] ?? 'default_password',
                'SMTPServer' => $source['MAIL_HOST'] ?? 'localhost',
                'SMTPPort' => (int)($source['MAIL_PORT'] ?? 465)
            ],
            'security' => [
                'APP_SECRET_KEY' => $source['APP_SECRET_KEY'] ?? null, 
                'LogFilename' => 'security_alerts.log',
                'NonceFilesDir' => defined('ROOT_PATH') ? ROOT_PATH . DIRECTORY_SEPARATOR . 'nonces' : 'nonces',
                'NonceDriver' => 'mysql', // redis, file, mysql
                'MaxDriftSeconds' => 60
            ],
            'remoterestapi' => [
                'EnableRestApi' => false,
                'Url' => 'http://vlad.local/api/endpoint',
                'Login' => 'vladlogin',
                'Pw' => ''
            ]
        ];

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