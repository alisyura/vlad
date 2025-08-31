<?php

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



// Админка
$adminRoute = Config::get('admin.AdminRoute');
$viewsRootPath = Config::get('global.ViewsRootPath');
$viewAdmin = new ViewAdmin(
    $viewsRootPath,
    'admin/login.php',
    'admin/admin_layout.php'
);

$router->addRoute("/$adminRoute/login", function() use ($viewAdmin) {
    (new AdminLoginController($viewAdmin))->login();
}, [], ['method' => 'GET, POST']);

$router->addRoute("/$adminRoute/dashboard", function() use ($viewAdmin) {
    (new AdminDashboardController($viewAdmin))->dashboard();
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/logout", function() use ($viewAdmin) {
    (new AdminLoginController($viewAdmin))->logout();
}, ['AdminAuthMiddleware']);

// Список постов/страниц с пагинацией
$router->addRoute("/$adminRoute/posts(?:/p(\d+))?", function($page = 1) use ($viewAdmin) {
    // Передаем номер страницы в контроллер
    (new AdminPostsController($viewAdmin))->postsList($page);
}, ['AdminAuthMiddleware']);

$router->addRoute("/$adminRoute/pages(?:/p(\d+))?", function($page = 1) use ($viewAdmin) {
    // Передаем номер страницы в контроллер
    (new AdminPostsController($viewAdmin))->pagesList($page);
}, ['AdminAuthMiddleware']);


// Создание нового поста
$router->addRoute("/$adminRoute/posts/create", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->createPostGet();
}, ['AdminAuthMiddleware']);

// Вызов api создания нового поста из формы создания нового поста по кнопке "опубликовать"
$router->addRoute("/$adminRoute/posts/api/create", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->createPostPost();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование существующего поста
$router->addRoute("/$adminRoute/posts/edit/(\d+)", function($postId) use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->editPostGet($postId);
}, ['AdminAuthMiddleware']);

// Вызов api изменения поста из формы изменения поста по кнопке "обновить"
$router->addRoute("/$adminRoute/posts/api/edit", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->editPostPut();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);



// Создание новой страницы
$router->addRoute("/$adminRoute/pages/create", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->createPageGet();
}, ['AdminAuthMiddleware']);

// Вызов api создания новой страницы из формы создания новой страницы по кнопке "опубликовать"
$router->addRoute("/$adminRoute/pages/api/create", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->createPagePost();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование существующей страницы
$router->addRoute("/$adminRoute/pages/edit/(\d+)", function($pageId) use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->editPageGet($pageId);
}, ['AdminAuthMiddleware']);

// Вызов api изменения страницы из формы изменения стараницы по кнопке "обновить"
$router->addRoute("/$adminRoute/pages/api/edit", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->editPagePut();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);


// Мягкое удаление поста/страницы. Простановка статуса "удален"
$router->addRoute("/$adminRoute/posts/delete", function() use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->deletePost();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);


$router->addRoute("/$adminRoute/posts/check-url", function() use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->checkUrl();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

$router->addRoute("/$adminRoute/tags/search", function() use ($viewAdmin) {
    (new AdminTagsController($viewAdmin))->searchTags();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);



// Маршруты для работы с медиа изображениями
$router->addRoute("/$adminRoute/media/list", function() use ($viewAdmin) {
    (new AdminMediaController($viewAdmin))->list();
}, ['AdminAuthMiddleware', 'AjaxMiddleware']);

$router->addRoute("/$adminRoute/media/upload", function() use ($viewAdmin) {
    (new AdminMediaController($viewAdmin))->upload();
}, ['AdminAuthMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);