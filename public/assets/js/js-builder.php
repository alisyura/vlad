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

$shouldMinify = Config::get('cache.MinificateJS');

$files = [
    'menu.js',
    'common.js',
    'textarea_charcounter.js',
    'drag_n_drop_file.js',
    'new_pub.js',
    'vote_share.js'
];

$last_modified_time = 0;
foreach ($files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        if ($mtime > $last_modified_time) $last_modified_time = $mtime;
    }
}

// Заголовки (Критически важно: application/javascript)
header("Content-type: application/javascript; charset: UTF-8");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
header("Cache-Control: public, max-age=31536000");

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time) {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

$output = "";
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if ($shouldMinify) {
            // Удаляем только многострочные комментарии /* ... */
            $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
            // Удаляем однострочные комментарии // ... , но сохраняем переносы строк!
            $content = preg_replace('/^\s*\/\/.*$/m', '', $content);
            // Убираем лишние пустые строки
            $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);
        }
        
        // Добавляем принудительную точку с запятой и перенос между файлами
        $output .= "\n/* --- File: $file --- */\n" . rtrim($content) . ";\n";
    }
}

echo $output;