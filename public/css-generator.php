<?php
// Узнаем кто запрашивает
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isCrawlerBot = (stripos($ua, 'YandexBot') !== false) || 
                (stripos($ua, 'Googlebot') !== false);

if ($isCrawlerBot) {
    header("Content-type: text/plain; charset: UTF-8");
}
else
{
    // Для всех остальных - CSS
    header("Content-Type: text/css");
}


$rootPath = dirname($_SERVER['DOCUMENT_ROOT']);

if (!class_exists('Config'))
{
    $configPath = $rootPath . '/app/core/Config.php';
    if (file_exists($configPath))
    {
        require_once($configPath);
    }
    else
    {
        die('Error: Config not found');
    }
}


$shouldMinify=Config::get('cache.MinificateCSS');

// Абсолютный путь к CSS файлам
$cssDir = $rootPath . '/public/assets/css/';


// Список твоих файлов в нужном порядке
$files = [
    'fonts.css',
    'styles.css',
    'common.css',
    'menu.css',
    'new_pub.css',
    'react.css'
];

$file_timestamps = [];
$last_modified_time = 0;
foreach ($files as $file) {
    $filePath = $cssDir . $file;
    if (file_exists($filePath)) {
        $mtime = filemtime($filePath);
        $file_timestamps[$file] = $mtime;
        if ($mtime > $last_modified_time) $last_modified_time = $mtime;
    }
}

// Проверка кэша браузера
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($if_modified_since >= $last_modified_time) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

// Заголовки для браузера
// header("Content-type: text/plain; charset: UTF-8");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
header("Cache-Control: public, max-age=31536000"); // Кэш на год


// Собираем контент
$output = "";
foreach ($files as $file) {
    $filePath = $cssDir . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        if ($shouldMinify) {
            // Более агрессивная минификация
            $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = preg_replace('/\s*([{}:;,])\s*/', '$1', $content);
            $content = trim($content);
        } else {
            // Режим отладки с комментариями о времени изменения
            $mtime = $file_timestamps[$file] ?? time();
            $date = date('Y-m-d H:i:s', $mtime);
            $content = "\n/* --- BEGIN: $file ($date) --- */\n" . $content . "\n/* --- END: $file --- */\n";
        }
        
        $output .= $content;
    }
}

if ($shouldMinify && extension_loaded('zlib') && !ob_start("ob_gzhandler")) {
    ob_start();
}

echo $output;

// echo "Time = ".$last_modified_time;