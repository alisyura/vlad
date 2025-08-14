<?php

class Config_new
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
            'posts_per_page' => 8,
            'max_urls_in_sitemap' => 50000
        ],
        'global' => [
            'SITE_NAME' => 'Мой блог',
            'SITE_KEYWORDS' => 'Ключевые слова...',
            'SITE_DESCRIPTION' => 'Описание...',
            'UseLogger' => true,
            'UploadDir' => 'uploads',
            'UploadedMaxFilesize' => 2 * 1024 * 1024,
            'UploadedMaxHeight' => 600,
            'UploadedMaxWidth' => 840,
            'UploadedMinHeight' => 300,
            'UploadedMinWidth' => 400,
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

    public static function get($key, $default = null)
    {
        // Поддержка формата: 'admin.AdminRoute'
        $parts = explode('.', $key);
        $section = $parts[0];
        $property = $parts[1] ?? null;

        if (!isset(self::$config[$section])) {
            return $default;
        }

        if ($property === null) {
            return self::$config[$section];
        }

        return self::$config[$section][$property] ?? $default;
    }

    public static function isDev(): bool
    {
        return true; // или можно из конфига: self::get('app.env') === 'dev'
    }
}


class Config
{
    private function __construct()
    {}

    public static function getDbHost($propertyName)
    {
        $db = [
            // Настройки БД
            'DB_HOST'=>'localhost',
            'DB_NAME'=>'vlad',
            'DB_USER'=>'vlad',
            'DB_PASS'=>'vlad'
        ];

        return $db[$propertyName];
    }

    public static function getPostsCfg($propertyName)
    {
        $posts = [
            'exerpt_len' => 200,
            'posts_per_page' => 8,
            'max_urls_in_sitemap' => 50000
        ];

        return $posts[$propertyName];
    }

    public static function getGlobalCfg($propertyName)
    {
        $global = [
            // Настройки сайта
            'SITE_NAME'=>'Мой блог',
            'SITE_KEYWORDS'=>'Ключевые слова. мета тег, meta, метаданные, keywords, description',
            'SITE_DESCRIPTION'=>'Описание. Описание содержимого на данной странице',
            'UseLogger' => true,
            'UploadDir' => 'uploads',
            'UploadedMaxFilesize' => 2*1024*1024, // 2 MB
            'UploadedMaxHeight' => 600,
            'UploadedMaxWidth' => 840,
            'UploadedMinHeight' => 300,
            'UploadedMinWidth' => 400,
            'CacheDir' => 'W:\\domains\\vlad.local\\cache\\pages/',
            'CacheLifetime' => 3600, // Время жизни кэша в секундах
            'UseCache' => false
        ];

        return $global[$propertyName];
    }

    public static function getAdminCfg($propertyName)
    {
        $global = [
            // Настройки сайта
            'AdminEmail'=>'admin@admin.ru',
            'AdminRoute'=>'adm',
            'posts_per_page' => 3,
            'EnableCreateCategory' => false, // включает/выключает возможность создавать категории
            'EnableEditCategory' => false, // включает/выключает возможность изменять категории
            'AdminRoleName' => 'Administrator'
        ];

        return $global[$propertyName];
    }

    public static function isDev()
    {
        return true;
    }
}