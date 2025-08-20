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
    $controller = new AjaxController();
    $controller->publish();
});

// Лайк/дислайк
$router->addRoute('/api/reaction', function () {
    $controller = new AjaxController();
    $controller->reaction();
});

// Получение лайков/дислайков постов
$router->addRoute('/api/post-votes', function () {
    $controller = new AjaxController();
    $controller->getPostVotes();
});

// Отправка сообщения через форму обратной связи
$router->addRoute('/api/send_msg', function () {
    $controller = new AjaxController();
    $controller->sendMsg();
});

// Получение списка тэгов
$router->addRoute('/api/search_tags', function () {
    $controller = new AjaxController();
    $controller->searchTags();
});



// Sitemap.xml
$router->addRoute('/sitemap\.xml', function () {
    $controller = new SitemapController();
    $controller->generateSitemapIndexXml();
});

$router->addRoute('/sitemap-(posts|pages)-(\d+)\.xml', function ($type, $page) {
    $controller = new SitemapController();
    $controller->generateSitemapPartXml($type, $page);
});


$adminRoute = Config::get('admin.AdminRoute');
// Админ
$router->addRoute("/$adminRoute/login", function() {
    (new AdminController())->login();
});

$router->addRoute("/$adminRoute/dashboard", function() {
    (new AdminController())->dashboard();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/logout", function() {
    (new AdminController())->logout();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/posts(?:/p(\d+))?", function($page = 1) {
    (new AdminController())->postsList($page); // Передаем номер страницы в контроллер
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/pages(?:/p(\d+))?", function($page = 1) {
    (new AdminController())->pagesList($page); // Передаем номер страницы в контроллер
}, ['AdminAuthMiddleware']);

// Маршрут для создания нового поста
$router->addRoute("/$adminRoute/posts/create", function() {
    (new AdminController())->createPostGet();
}, ['AdminAuthMiddleware']);

// Вызов api создания нового поста из формы создания нового поста по кнопке "опубликовать"
$router->addRoute("/$adminRoute/api/posts/create", function() {
    (new AdminController())->createPostPost();
}, ['AdminAuthMiddleware']);

// Маршрут для редактирования существующего поста
$router->addRoute("/$adminRoute/posts/edit/(\d+)", function($postId) {
    (new AdminController())->editPost($postId);
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/posts/check-url", function() {
    (new AdminController())->checkUrl();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/posts/delete", function() {
    (new AdminController())->deletePost();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/tags/search", function() {
    (new AdminController())->searchTags();
}, ['AdminAuthMiddleware']);

// Маршруты для работы с медиа изображениями
$router->addRoute("/$adminRoute/media/list", function() {
    (new AdminMediaController())->list();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/media/upload", function() {
    (new AdminMediaController())->upload();
}, ['AdminAuthMiddleware']);