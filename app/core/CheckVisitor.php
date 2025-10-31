<?php

if (!function_exists('generate_uuid_v4')) {
    require_once __DIR__ . '/Helpers.php';
}

if (!isset($_COOKIE['visitor_uid'])) {
    $uid = 'v_' . generate_uuid_v4();
    // На будущее. добавить подпись UUID. при чтении проверять
    // $signature = hash_hmac('sha256', $uid, Config::get('app.secret'));
    // setcookie('visitor_uid', "$uid|$signature", [ ... ]);
    Logger::info("New visitor UID created", ['uid' => $uid]);
    setcookie('visitor_uid', $uid, [
        'expires' => time() + 3600 * 24 * 730, // ставим на 2 года. 730 кол-во дней
        'path' => '/',
        'secure' => strtolower($_SERVER['REQUEST_SCHEME']) === 'https',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// если $_SERVER['REQUEST_SCHEME'] не заполняется, использовать это
//$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;