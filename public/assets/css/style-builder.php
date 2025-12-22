<?php
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


// Список твоих файлов в нужном порядке
$files = [
    'fonts.css',
    'styles.css',
    'common.css',
    'menu.css',
    'new_pub.css',
    'react.css'
];

$last_modified_time = 0;
foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        if ($mtime > $last_modified_time) $last_modified_time = $mtime;
    }
}

// Проверка кэша браузера
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time) {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

// Заголовки для браузера
header("Content-type: text/css; charset: UTF-8");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
header("Cache-Control: public, max-age=31536000"); // Кэш на год

// Собираем контент
$output = "";
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if ($shouldMinify) {
            // Минификация: превращаем в «сосиску»
            $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
            $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    '], '', $content);
            $content = str_replace(['{ ', ' }', '; ', ': '], ['{', '}', ';', ':'], $content);
        } else {
            // Режим отладки: просто добавляем заголовок файла для удобства поиска в консоли
            $content = "\n/* --- SRC: $file --- */\n" . $content;
        }
        
        $output .= $content;
    }
}

echo $output;