<?php

// Не обрабатываем. Отдаём файл напрямую (если существует)
$router->addRoute('^/assets/.*\.(jpg|jpeg|png|gif|css|js|webp|svg|ico|mp4)$', function() {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
    
    if (file_exists($filePath)) {
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'mp4'  => 'video/mp4'
        ];
        
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($filePath);
        exit;
    }
    
    header("HTTP/1.0 404 Not Found");
    exit;
});

// Главная страница. Или пустая, или номером страницы /p/2
$router->addRoute('/(p(\d+))?', function($fullMatch = null, $page = 1) {
    $controller = new PostController();
    $controller->index(max(1, (int)$page)); // защита от нуля и отрицательных
}, ['PageCacheMiddleware']);

// Страница post
$router->addRoute('/([0-9a-zA-Z-_]+)\.html', function($post_url) {
    $controller = new PostController();
    $controller->showPost($post_url);
}, ['PageCacheMiddleware']);

// Страница контакты
$router->addRoute('/page/kontakty\.html', function() {
    $controller = new PostController();
    $controller->showKontakty();
}, ['PageCacheMiddleware']);

// Страница карта сайта
$router->addRoute('/page/sitemap\.html', function() {
    $controller = new PostController();
    $controller->showSitemap();
}, ['PageCacheMiddleware']);

// Страница page
$router->addRoute('/page\/([0-9a-zA-Z-_]+)\.html', function($page_url) {
    $controller = new PostController();
    $controller->showPage($page_url);
}, ['PageCacheMiddleware']);

// Список постов по тэгу
$router->addRoute('/tag\/([0-9a-zA-Z-_]+)(?:\/p(\d+))?', function($tagUrl, $page = 1) {
    $controller = new PostController();
    $controller->showTag($tagUrl, max(1, (int)$page));
}, ['PageCacheMiddleware']);

// Список постов по разделу
$router->addRoute('/cat\/(anekdoty|veselaya-rifma|citatnik|istorii|kartinki|video|tegi|luchshee)(?:\/p(\d+))?', function($cat_url, $page = 1) {
    $controller = new PostController();
    if ($cat_url === 'tegi') {
        $controller->showTagFilter();
    }
    else {
        $controller->showSection($cat_url, $cat_url === 'istorii', max(1, (int)$page));
    }
}, ['PageCacheMiddleware']);



// Вызовы Ajax

// Добавление пользователем материала через кнопку Добавить из меню
$router->addRoute('/api/publish', function () {
    require_once __DIR__ . '/../app/controllers/AjaxController.php';
    $controller = new AjaxController();
    $controller->publish();
});

// Лайк/дислайк
$router->addRoute('/api/reaction', function () {
    require_once __DIR__ . '/../app/controllers/AjaxController.php';
    $controller = new AjaxController();
    $controller->reaction();
});

// Получение лайков/дислайков постов
$router->addRoute('/api/post-votes', function () {
    require_once __DIR__ . '/../app/controllers/AjaxController.php';
    $controller = new AjaxController();
    $controller->getPostVotes();
});

// Отправка сообщения через форму обратной связи
$router->addRoute('/api/send_msg', function () {
    require_once __DIR__ . '/../app/controllers/AjaxController.php';
    $controller = new AjaxController();
    $controller->sendMsg();
});

// Отправка сообщения через форму обратной связи
$router->addRoute('/api/search_tags', function () {
    require_once __DIR__ . '/../app/controllers/AjaxController.php';
    $controller = new AjaxController();
    $controller->searchTags();
});



// Sitemap.xml
$router->addRoute('/sitemap\.xml', function () {
    require_once __DIR__ . '/../app/controllers/SitemapController.php';
    $controller = new SitemapController();
    $controller->generateSitemapIndexXml();
});

$router->addRoute('/sitemap-(posts|pages)-(\d+)\.xml', function ($type, $page) {
    require_once __DIR__ . '/../app/controllers/SitemapController.php';
    $controller = new SitemapController();
    $controller->generateSitemapPartXml($type, $page);
});


$adminRoute = Config::getAdminCfg('AdminRoute');
// Админ
$router->addRoute("/$adminRoute/login", function() {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController())->login();
});

$router->addRoute("/$adminRoute/dashboard", function() {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController())->dashboard();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/logout", function() {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController())->logout();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/posts(?:/p(\d+))?", function($page = 1) {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController())->postsList($page); // Передаем номер страницы в контроллер
}, ['AdminAuthMiddleware']);