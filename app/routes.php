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
}, ['AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Получение лайков/дислайков постов
$router->addRoute('/api/post-votes', function () {
    $controller = new AjaxController();
    $controller->getPostVotes();
}, ['AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

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

// Получение CSRF токена
$router->addRoute('/api/get-csrf-token', function () {
    $controller = new AjaxController();
    $controller->getCsrfToken();
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
}, ['UserAuthenticatedMiddleware']);

$router->addRoute("/$adminRoute/logout", function() use ($viewAdmin) {
    (new AdminLoginController($viewAdmin))->logout();
}, ['UserAuthenticatedMiddleware']);


// Формы GET

// Список постов/страниц с пагинацией
$router->addRoute("/$adminRoute/(post|page)s(?:/p(\d+))?", function($articleType, $page = 1) use ($viewAdmin) {
    // Передаем номер страницы в контроллер
    (new AdminPostsController($viewAdmin))->list($page, $articleType);
}, ['UserAuthenticatedMiddleware', 'ArticleTypeMiddleware:post,page']);

// Форма создание нового поста/страницы
$router->addRoute("/$adminRoute/(post|page)s/create", function($articleType) use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->create($articleType);
}, ['UserAuthenticatedMiddleware', 'ArticleTypeMiddleware:post,page']);

// Форма редактирования существующего поста/страницы
$router->addRoute("/$adminRoute/(post|page)s/edit/(\d+)", function($articleType, $postId) use ($viewAdmin) {
    (new AdminPostsController($viewAdmin))->edit($postId, $articleType);
}, ['UserAuthenticatedMiddleware', 'ArticleTypeMiddleware:post,page']);



// Вызовы API работы с постами/страницами

// Вызов api создания нового поста из формы создания нового поста по кнопке "опубликовать"
$router->addRoute("/$adminRoute/(post|page)s/api/create", function($articleType) use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->create($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Вызов api изменения поста из формы изменения поста по кнопке "обновить"
$router->addRoute("/$adminRoute/(post|page)s/api/edit", function($articleType) use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->edit($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Мягкое удаление поста/страницы. Простановка статуса "удален"
$router->addRoute("/$adminRoute/posts/api/delete", function() use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->deletePost();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Проверка урла при создании поста/страницы
$router->addRoute("/$adminRoute/posts/api/check-url", function() use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->checkUrl();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);




// Маршруты для работы с медиа изображениями

// Получение списка картинок
$router->addRoute("/$adminRoute/media/api/list", function() use ($viewAdmin) {
    (new AdminMediaApiController($viewAdmin))->list();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware']);

// Загрузка новой картинки
$router->addRoute("/$adminRoute/media/api/upload", function() use ($viewAdmin) {
    (new AdminMediaApiController($viewAdmin))->upload();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);



// Формы для тэгов

// Открыть форму списка тэгов
$router->addRoute("/$adminRoute/tags(?:/p(\d+))?", function($page = 1) use ($viewAdmin) {
    (new AdminTagsController($viewAdmin))->list($page);
}, ['UserAuthenticatedMiddleware']);

// Открыть форму редактирование тэга
$router->addRoute("/$adminRoute/tags/edit/(\d+)", function($tagId) use ($viewAdmin) {
    (new AdminTagsController($viewAdmin))->edit($tagId);
}, ['UserAuthenticatedMiddleware']);


// Операции над тэгами

// Поиск тэгов по имени (автодополнение при создании поста/страницы)
$router->addRoute("/$adminRoute/tags/api/search", function() use ($viewAdmin) {
    (new AdminTagsApiController($viewAdmin))->searchTags();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Создание нового тэга
$router->addRoute("/$adminRoute/tags/api/create", function() use ($viewAdmin) {
    (new AdminTagsApiController($viewAdmin))->create();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование тэга
$router->addRoute("/$adminRoute/tags/api/edit/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminTagsApiController($viewAdmin))->edit($userId);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Удаление тэга
$router->addRoute("/$adminRoute/tags/api/delete/(\d+)", function($tagId) use ($viewAdmin) {
    (new AdminTagsApiController($viewAdmin))->delete($tagId);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);



// Формы для управления пользователями

// Открыть форму списка пользователей
$router->addRoute("/$adminRoute/users", function() use ($viewAdmin) {
    (new AdminUsersController($viewAdmin))->list();
}, ['AdminAuthenticatedMiddleware']);

// Открыть форму редактирование пользователя
$router->addRoute("/$adminRoute/users/edit/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminUsersController($viewAdmin))->edit($userId);
}, ['AdminAuthenticatedMiddleware']);


// Операции над пользователями

// Создание нового пользователя
$router->addRoute("/$adminRoute/users/api/create", function() use ($viewAdmin) {
    (new AdminUsersApiController($viewAdmin))->create();
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование пользователя
$router->addRoute("/$adminRoute/users/api/edit/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminUsersApiController($viewAdmin))->edit($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Блокирование пользователя
$router->addRoute("/$adminRoute/users/api/block/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminUsersApiController($viewAdmin))->block($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Разблокирование пользователя
$router->addRoute("/$adminRoute/users/api/unblock/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminUsersApiController($viewAdmin))->unblock($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Удаление пользователя
$router->addRoute("/$adminRoute/users/api/delete/(\d+)", function($userId) use ($viewAdmin) {
    (new AdminUsersApiController($viewAdmin))->delete($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);


// Формы для работы с корзиной удаленных постов/страниц

// Форма списка удаленных постов/страниц с пагинацией
$router->addRoute("/$adminRoute/thrash/(post|page)s(?:/p(\d+))?", function($articleType, $page = 1) use ($viewAdmin) {
    // Передаем номер страницы в контроллер
    (new AdminPostsController($viewAdmin))->list($page, $articleType);
}, ['UserAuthenticatedMiddleware', 'ArticleTypeMiddleware:post,page']);


// Операции над корзиной

// Восстановление поста/страницы из корзины. Простановка статуса "черновик"
$router->addRoute("/$adminRoute/thrash/api/restore", function() use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->restore();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Физическое удаление поста/страницы из БД.
$router->addRoute("/$adminRoute/thrash/api/delete-forever", function() use ($viewAdmin) {
    (new AdminPostsApiController($viewAdmin))->hardDelete();
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);