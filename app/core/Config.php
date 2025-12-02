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
            'posts_per_page' => 5,
            'max_urls_in_sitemap' => 50000,
            'allowed_tags' => '<p><b><i><strong><em><a><img><br><span><s><ul><li><ol><div>',
            // кол-во тэгов на странице поиска тэгов, когда ее тока открыли. без поиска
            'count_tags_without_query' => 10
        ],
        'global' => [
            'ViewsRootPath' => 'C:\\Users\\kriya\\Projects\\web\\vlad.local\\app\\views'
        ],
        'logger' => [
            'UseDebugLogger' => true,
            'UseInfoLogger' => true,
            'UseWarningLogger' => true,
            'UseErrorLogger' => true,
            'UseCriticalLogger' => true,
            'LogPath' => 'C:\\Users\\kriya\\Projects\\web\\vlad.local\\logs'
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
            'CacheDir' => 'C:\\Users\\kriya\\Projects\\web\\vlad.local\\cache\\pages/',
            'CacheLifetime' => 3600,
            'UseCache' => false
        ],
        'admin' => [
            'AdminEmail' => 'admin@admin.ru',
            'AdminRoute' => 'adm',
            'PostsPerPage' => 10,
            'EnableCreateCategory' => false,
            'EnableEditCategory' => false,
            'AdminRoleName' => 'Administrator',
            // урлы страниц, которые не будут показаны в списке страниц в админке
            'PagesToExclude' => ['sitemap', 'kontakty'],
            'PostsToExclude' => [],
            'TagsPerPage' => 10,
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