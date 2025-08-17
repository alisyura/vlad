<?php
class Config
{
    private static $config = [
        'db' => [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'vlad',
            'DB_USER' => 'vlad',
            'DB_PASS' => 'vlad'
        ],
        'posts' => [
            'exerpt_len' => 200,
            'posts_per_page' => 3,
            'max_urls_in_sitemap' => 50000
        ],
        'global' => [
            'SITE_NAME' => 'Мой блог',
            'SITE_KEYWORDS' => 'Ключевые слова...',
            'SITE_DESCRIPTION' => 'Описание...'
        ],
        'logger' => [
            'UseLogger' => true
        ],
        'upload' => [
            'UploadDir' => 'uploads',
            'UploadedMaxFilesize' => 2 * 1024 * 1024,
            'UploadedMaxHeight' => 600,
            'UploadedMaxWidth' => 840,
            'UploadedMinHeight' => 300,
            'UploadedMinWidth' => 400
        ],
        'cache' => [
            'CacheDir' => 'W:\\domains\\vlad.local\\cache\\pages/',
            'CacheLifetime' => 3600,
            'UseCache' => false
        ],
        'admin' => [
            'AdminEmail' => 'admin@admin.ru',
            'AdminRoute' => 'adm',
            'posts_per_page' => 3,
            'EnableCreateCategory' => false,
            'EnableEditCategory' => false,
            'AdminRoleName' => 'Administrator'
        ]
    ];

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

    public static function isDev()
    {
        return true;
    }
}