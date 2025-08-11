<?php

function validateEmail($email) {
    $email = strtolower($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function getVisitorCookie()
{
    return $_COOKIE['visitor_uid'];
}

/**
 * Генерирует массив ссылок для постраничного отображения
 * 
 */
function generateSmartPaginationLinks($currentPage, $totalPages, $baseUrl = '/', $maxVisible = 5) {
    if ($totalPages <= 1) return [];

    $links = [];
    $start = max(1, min($currentPage - floor($maxVisible / 2), $totalPages - $maxVisible + 1));
    $end = min($totalPages, $start + $maxVisible - 1);

    // Убедимся, что baseUrl заканчивается на /
    $baseUrl = rtrim($baseUrl, '/');

    // Добавляем начало
    if ($start > 1) {
        $links[1] = "$baseUrl/p1";
        if ($start > 2) {
            $links['...left'] = '…';
        }
    }

    // Основные страницы
    for ($i = $start; $i <= $end; $i++) {
        $links[$i] = "$baseUrl/p$i";
    }

    // Конец
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $links['...right'] = '…';
        }
        $links[$totalPages] = "$baseUrl/p{$totalPages}";
    }

    return $links;
}

function transliterate($string) {
    $converter = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
    ];
    
    // Транслитерация
    $string = strtr($string, $converter);
    
    // Приводим к нижнему регистру и оставляем только разрешённые символы
    $string = preg_replace('/[^a-z0-9\s_-]/', '', mb_strtolower($string, 'UTF-8'));
    
    // Заменяем пробелы на дефисы
    $string = str_replace(' ', '-', $string);
    
    // Убираем множественные дефисы
    $string = preg_replace('/-+/', '-', $string);
    
    // Убираем дефисы в начале и конце
    $string = trim($string, '-');
    
    // Если пусто — используем заглушку
    return $string ?: 'image';
}

/**
 * Выводит дату в формате dd.MM.yyyy. 21 января 2015
 * 
 */
function stringDate($date)
{
    // Текущая дата или заданная дата
    $date = new DateTime($date); // Или укажите дату: new DateTime('2023-10-05')

    // Массив с названиями месяцев
    $months = [
        1 =>  'января',  2 =>  'февраля', 3 =>  'марта',
        4 =>  'апреля',  5 =>  'мая',     6 =>  'июня',
        7 =>  'июля',    8 =>  'августа', 9 =>  'сентября',
        10 => 'октября', 11 => 'ноября',  12 => 'декабря'
    ];

    // Формируем строку: день + месяц словами + год
    $formattedDate = $date->format('j') . ' ' . $months[(int)$date->format('n')] . ' ' . $date->format('Y');

    return $formattedDate;
}

function DateYYYYmmdd($value): string
{
    // Если значение NULL или пустая строка — возвращаем пустую строку
    if ($value === null || trim($value) === '') {
        return '';
    }

    // Пытаемся создать дату
    $date = date_create($value);

    // Возвращаем форматированную дату или пустую строку, если формат был неверен
    return $date ? $date->format('Y-m-d') : '';
}

function create_excerpt($content)
{
    $max_length = Config::getPostsCfg('exerpt_len');
    if (empty($content) || mb_strlen($content) <= $max_length) {
        return $content;
    }

    $truncated = mb_substr($content, 0, $max_length);
    $last_space = mb_strrpos($truncated, ' ');

    if ($last_space !== false) {
        return mb_substr($truncated, 0, $last_space);
    }

    return $truncated;
}

// Генерация UUIDv4 (без использования PECL)
function generate_uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // версия 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // вариация 1
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    return $uuid;
}

/**
 * Создает описание микроразметки
 * 
 * @param $data['page_type'] string 'home', 'post', 'category', 'tag'
 * @param $data array Входные данные
 * 
 * @return string Созданная микроразметка
 */
function generateStructuredData($data)
{
    $type = $data['page_type'] ?? 'home';
    $site_name = htmlspecialchars($data['site_name'] ?? 'Юморной сайт');
    $description = htmlspecialchars($data['description'] ?? '');
    $url = htmlspecialchars($data['url'] ?? '/');
    $image = htmlspecialchars($data['image'] ?? '/assets/pic/logo.png');

    // === Open Graph мета-теги ===
    echo '<meta property="og:title" content="' . $site_name . '">' . "\n";
    echo '<meta property="og:description" content="' . $description . '">' . "\n";
    echo '<meta property="og:type" content="' . ($type === 'post' ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:url" content="' . $url . '">' . "\n";
    echo '<meta property="og:image" content="' . $image . '">' . "\n";
    echo '<meta property="og:site_name" content="' . $site_name . '">' . "\n";
    echo '<meta property="og:locale" content="ru_RU">' . "\n";

    // === JSON-LD Schema.org ===
    if ($type === 'post') {
        // Если это страница поста
        $structured_data = [
            '@context' => 'https://schema.org', 
            '@type' => 'NewsArticle',
            'headline' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'datePublished' => $data['datePublished'] ?? date('c'),
            'dateModified' => $data['dateModified'] ?? date('c'),
            'author' => [
                '@type' => 'Person',
                'name' => $data['author'] ?? 'Автор не указан'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $site_name,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => '/pic/logo.png'
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url
            ]
        ];

        if (!empty($data['tags'])) {
            $structured_data['keywords'] = is_array($data['tags']) 
                ? implode(',', $data['tags']) 
                : $data['tags'];
        }

        if (!empty($data['category'])) {
            $structured_data['articleSection'] = $data['category'];
        }

        if (!empty($data['image'])) {
            $structured_data['image'] = [
                '@type' => 'ImageObject',
                'url' => $data['image'],
                'width' => 800,
                'height' => 600
            ];
        }

    } else {
        // Для главной, категорий, тегов — ItemList
        $items = [];
        if (!empty($data['posts'])) {
            foreach ($data['posts'] as $i => $post) {
                $item = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => rtrim($data['url'], '/') . '/' . $post['url'] . '.html',
                    'name' => $post['title'],
                    'description' => create_excerpt($post['content']),
                ];

                if (!empty($post['image'])) {
                    $item['image'] = [
                        '@type' => 'ImageObject',
                        'url' => rtrim($data['url'], '/') . $post['image']
                    ];
                }

                $items[] = $item;
            }
        }

        $structured_data = [
            '@context' => 'https://schema.org', 
            '@type' => 'ItemList',
            'name' => $data['listName'] ?? 'Список постов',
            'description' => $description,
            'url' => $url,
            'numberOfItems' => count($items),
            'itemListElement' => $items
        ];
    }

    // === Выводим JSON-LD ===
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";

    // === prev / next для пагинации ===
    if (in_array($type, ['home', 'category', 'tag']) && !empty($data['current_page'])) {
        $current_page = (int)$data['current_page'];
        $total_pages = (int)$data['total_pages'];

        if ($current_page > 1) {
            $prev_page = $current_page - 1;
            $prev_url = $data['baseUrl'] . ($prev_page > 1 ? "/page/$prev_page" : "");
            echo "<link rel=\"prev\" href=\"$prev_url\">\n";
        }

        if ($current_page < $total_pages) {
            $next_page = $current_page + 1;
            $next_url = $data['baseUrl'] . "/page/$next_page";
            echo "<link rel=\"next\" href=\"$next_url\">\n";
        }
    }
}

function debugPDO($sql, $params) {
    foreach ($params as $key => $val) {
        if (is_string($val)) {
            $val = "'$val'";
        } elseif (is_null($val)) {
            $val = "NULL";
        } elseif (is_array($val)) {
            $val = "'" . implode("','", $val) . "'";
        }

        // Замена :param или ?
        $sql = preg_replace('/:'.$key.'\b/', $val, $sql);
    }

    return $sql;
}

function asset(string $path): string {
    // Абсолютный путь к файлу на сервере
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/' . ltrim($path, '/');
    
    // Базовый URL (без версии)
    $url = '/assets/' . ltrim($path, '/');
    
    // Добавляем версию только если файл существует
    if (file_exists($absolutePath)) {
        $url .= '?v=' . filemtime($absolutePath);
    } else {
        // Логируем ошибку, если файл не найден
        error_log("Asset not found: " . $absolutePath);
    }
    
    return $url;
}