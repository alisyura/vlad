<?php

// Страница карта сайта
$router->addRoute('/page/sitemap\.html', function(Container $container): Response {
    $controller = $container->make(SitemapController::class);
    return $controller->showSitemap();
}, ['PageCacheMiddleware']);

// Sitemap.xml
$router->addRoute('/sitemap\.xml', function (Container $container): Response {
    $controller = $container->make(SitemapController::class);
    return $controller->generateSitemapIndexXml();
}, ['PageCacheMiddleware']);

$router->addRoute('/sitemap-(post|page)s-(\d+)\.xml', 
    function (Container $container, $type, $page): Response  {
        $controller = $container->make(SitemapController::class);
        return $controller->generateSitemapPartXml($type, $page);
}, ['PageCacheMiddleware']);




// Страница контакты
$router->addRoute('/page/kontakty\.html', function(Container $container): Response {
    $controller = $container->make(ContactController::class);
    return $controller->showKontakty();
}, ['PageCacheMiddleware']);

// Отправка сообщения через форму обратной связи
$router->addRoute('/api/send_msg', function (Container $container): Response {
    $controller = $container->make(ContactController::class);
    return $controller->sendMsg();
}, ['AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);




// Главная страница. Или пустая, или номером страницы /p/2
$router->addRoute('/(p(\d+))?', 
    function(Container $container, $fullMatch = null, $page = 1): Response|null {
        $controller = $container->make(PostController::class);
        return $controller->index(max(1, (int)$page)); // защита от нуля и отрицательных
}, ['PageCacheMiddleware']);

// Страница post
$router->addRoute('/([0-9a-zA-Z-_]+)\.html', 
    function(Container $container, $post_url): Response {
        $controller = $container->make(PostController::class);
        return $controller->showPost($post_url);
}, ['PageCacheMiddleware']);

// Страница page
$router->addRoute('/page\/([0-9a-zA-Z-_]+)\.html', 
    function(Container $container, $page_url): Response {
        $controller = $container->make(PostController::class);
        return $controller->showPage($page_url);
}, ['PageCacheMiddleware']);

// Список постов по тэгу
$router->addRoute('/tag\/([0-9a-zA-Z-_]+)(?:\/p(\d+))?', 
    function(Container $container, $tagUrl, $page = 1): Response {
        $controller = $container->make(PostController::class);
        return $controller->showByTag($tagUrl, max(1, (int)$page));
}, ['PageCacheMiddleware']);

// Список постов по разделу
$router->addRoute('/cat\/(anekdoty|veselaya-rifma|citatnik|istorii|kartinki|video|luchshee)(?:\/p(\d+))?', 
    function(Container $container, $cat_url, $page = 1): Response {
        $controller = $container->make(PostController::class);
        return $controller->showBySection($cat_url, $cat_url === 'istorii', max(1, (int)$page));
}, ['PageCacheMiddleware']);



// Страница поиска постов по тэгам
$router->addRoute('/cat\/tegi', 
    function(Container $container): Response {
        $controller = $container->make(TagsController::class);
        return $controller->showTagFilter();
}, ['PageCacheMiddleware']);

// Получение списка тэгов (для seo)
$router->addRoute('/cat\/tegi-results\.html', 
    function (Container $container): Response {
        $controller = $container->make(TagsController::class);
        return $controller->showTagsResults();
});

// Получение списка тэгов
$router->addRoute('/api/search_tags', function (Container $container): Response {
    $controller = $container->make(TagsController::class);
    return $controller->searchTags();
}, ['AjaxMiddleware']);





// Получение CSRF токена для клиента
$router->addRoute('/api/get-csrf-token', function (Container $container): Response {
    $controller = $container->make(AjaxController::class);
    return $controller->getCsrfToken();
});



// Добавление пользователем материала через кнопку Добавить из меню
$router->addRoute('/api/publish', function (Container $container): Response {
    $controller = $container->make(SubmissionController::class);
    return $controller->publish();
}, ['AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);



// Лайк/дислайк
$router->addRoute('/api/reaction', function (Container $container): Response {
    $controller = $container->make(VotingController::class);
    return $controller->reaction();
}, ['AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Получение лайков/дислайков постов
$router->addRoute('/api/get-post-votes', function (Container $container): Response {
    $controller = $container->make(VotingController::class);
    return $controller->getPostVotes();
}, ['AjaxMiddleware'], ['method' => 'POST']);






// Админка

$adminRoute = Config::get('admin.AdminRoute');

$router->addRoute("/$adminRoute/login", function(Container $container): Response {
    $controller = $container->make(AdminLoginController::class);
    return $controller->login();
}, [], ['method' => 'GET, POST']);

$router->addRoute("/$adminRoute/dashboard", function(Container $container): Response {
    $controller = $container->make(AdminDashboardController::class);
    return $controller->dashboard();
}, ['UserAuthenticatedMiddleware']);

$router->addRoute("/$adminRoute/logout", function(Container $container): Response {
    $controller = $container->make(AdminLoginController::class);
    return $controller->logout();
}, ['UserAuthenticatedMiddleware']);


// Формы GET

// Список постов/страниц с пагинацией
$router->addRoute("/$adminRoute/(post|page)s(?:/p(\d+))?", 
    function(Container $container, $articleType, $page = 1): Response {
        // Передаем номер страницы в контроллер
        $controller = $container->make(AdminPostsController::class);
        return $controller->list($articleType, $page);
}, ['UserAuthenticatedMiddleware']);

// Форма создание нового поста/страницы
$router->addRoute("/$adminRoute/(post|page)s/create", 
    function(Container $container, $articleType): Response {
        $controller = $container->make(AdminPostsController::class);
        return $controller->create($articleType);
}, ['UserAuthenticatedMiddleware']);

// Форма редактирования существующего поста/страницы
$router->addRoute("/$adminRoute/(post|page)s/edit/(\d+)", 
    function(Container $container, $articleType, $postId): Response {
        $controller = $container->make(AdminPostsController::class);
        return $controller->edit($postId, $articleType);
}, ['UserAuthenticatedMiddleware']);



// Вызовы API работы с постами/страницами

// Вызов api создания нового поста из формы создания нового поста по кнопке "опубликовать"
$router->addRoute("/$adminRoute/(post|page)s/api/create", 
    function(Container $container, $articleType): Response {
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->create($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Вызов api изменения поста из формы изменения поста по кнопке "обновить"
$router->addRoute("/$adminRoute/(post|page)s/api/edit", 
    function(Container $container, $articleType): Response {
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->edit($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Мягкое удаление поста/страницы. Простановка статуса "удален"
$router->addRoute("/$adminRoute/(post|page)s/api/delete", 
    function(Container $container, $articleType): Response {
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->delete($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Проверка урла при создании поста/страницы
$router->addRoute("/$adminRoute/(post|page)s/api/check-url", 
    function(Container $container, $articleType): Response {
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->checkUrl($articleType);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);




// Маршруты для работы с медиа изображениями

// Получение списка картинок
$router->addRoute("/$adminRoute/media/api/list", 
    function(Container $container): Response {
        $controller = $container->make(AdminMediaApiController::class);
        return $controller->list();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware']);

// Загрузка новой картинки
$router->addRoute("/$adminRoute/media/api/upload", 
    function(Container $container):Response {
        $controller = $container->make(AdminMediaApiController::class);
        return $controller->upload();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);



// Формы для тэгов

// Открыть форму списка тэгов
$router->addRoute("/$adminRoute/tags(?:/p(\d+))?", 
    function(Container $container, $page = 1): Response {
        $controller = $container->make(AdminTagsController::class);
        return $controller->list($page);
}, ['UserAuthenticatedMiddleware']);

// Открыть форму редактирование тэга
$router->addRoute("/$adminRoute/tags/edit/(\d+)", 
    function(Container $container, $tagId): Response {
        $controller = $container->make(AdminTagsController::class);
        return $controller->edit($tagId);
}, ['UserAuthenticatedMiddleware']);


// Операции над тэгами

// Поиск тэгов по имени (автодополнение при создании поста/страницы)
$router->addRoute("/$adminRoute/tags/api/search", 
    function(Container $container): Response {
        $controller = $container->make(AdminTagsApiController::class);
        return $controller->searchTags();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Создание нового тэга
$router->addRoute("/$adminRoute/tags/api/create", 
    function(Container $container): Response {
        $controller = $container->make(AdminTagsApiController::class);
        return $controller->create();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование тэга
$router->addRoute("/$adminRoute/tags/api/edit/(\d+)", 
    function(Container $container, $tagId): Response {
        $controller = $container->make(AdminTagsApiController::class);
        return $controller->edit($tagId);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Удаление тэга
$router->addRoute("/$adminRoute/tags/api/delete/(\d+)", 
    function(Container $container, $tagId): Response {
        $controller = $container->make(AdminTagsApiController::class);
        return $controller->delete($tagId);
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);



// Формы для управления пользователями

// Открыть форму списка пользователей
$router->addRoute("/$adminRoute/users", function(Container $container): Response {
    $controller = $container->make(AdminUsersController::class);
    return $controller->list();
}, ['AdminAuthenticatedMiddleware']);

// Открыть форму редактирование пользователя
$router->addRoute("/$adminRoute/users/edit/(\d+)", 
    function(Container $container, $userId): Response {
        $controller = $container->make(AdminUsersController::class);
        return $controller->edit($userId);
}, ['AdminAuthenticatedMiddleware']);


// Операции над пользователями

// Создание нового пользователя
$router->addRoute("/$adminRoute/users/api/create", 
    function(Container $container): Response {
        $controller = $container->make(AdminUsersApiController::class);
        return $controller->create();
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Редактирование пользователя
$router->addRoute("/$adminRoute/users/api/edit/(\d+)", 
    function(Container $container, $userId): Response {
        $controller = $container->make(AdminUsersApiController::class);
        return $controller->edit($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PUT']);

// Блокирование пользователя
$router->addRoute("/$adminRoute/users/api/block/(\d+)", 
    function(Container $container, $userId): Response {
        $controller = $container->make(AdminUsersApiController::class);
        return $controller->block($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Разблокирование пользователя
$router->addRoute("/$adminRoute/users/api/unblock/(\d+)", 
    function(Container $container, $userId): Response {
        $controller = $container->make(AdminUsersApiController::class);
        return $controller->unblock($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Удаление пользователя
$router->addRoute("/$adminRoute/users/api/delete/(\d+)", 
    function(Container $container, $userId): Response {
        $controller = $container->make(AdminUsersApiController::class);
        return $controller->delete($userId);
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);


// Формы для работы с корзиной удаленных постов/страниц

// Форма списка удаленных постов/страниц с пагинацией
$router->addRoute("/$adminRoute/thrash/(post|page)s(?:/p(\d+))?", 
    function(Container $container, $articleType, $page = 1): Response {
        // Передаем номер страницы в контроллер
        $controller = $container->make(AdminPostsController::class);
        return $controller->list($articleType, $page);
}, ['UserAuthenticatedMiddleware']);


// Операции над корзиной

// Восстановление поста/страницы из корзины. Простановка статуса "черновик"
$router->addRoute("/$adminRoute/thrash/api/restore", 
    function(Container $container): Response {
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->restore();
}, ['UserAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'PATCH']);

// Физическое удаление поста/страницы из БД.
$router->addRoute("/$adminRoute/thrash/api/delete-forever", 
    function(Container $container): Response {
        // (new AdminPostsApiController($request))->hardDelete();
        $controller = $container->make(AdminPostsApiController::class);
        return $controller->hardDelete();
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);


// Настройки

// Форма списка настройек
$router->addRoute("/$adminRoute/settings", 
    function(Container $container): Response {
        $controller = $container->make(AdminSettingsController::class);
        return $controller->list();
}, ['UserAuthenticatedMiddleware']);

// Форма создания настройки
$router->addRoute("/$adminRoute/settings/create", 
    function(Container $container): Response {
        $controller = $container->make(AdminSettingsController::class);
        return $controller->create();
}, ['AdminAuthenticatedMiddleware']);

// Обработчик создания настройки
$router->addRoute("/$adminRoute/settings/create", 
    function(Container $container): Response {
        $controller = $container->make(AdminSettingsController::class);
        return $controller->handleCreate();
}, ['AdminAuthenticatedMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Форма редактирования настройки
$router->addRoute("/$adminRoute/settings/edit/(\d+)", 
    function(Container $container, $id): Response {
        $controller = $container->make(AdminSettingsController::class);
        return $controller->edit($id);
}, ['AdminAuthenticatedMiddleware']);

// Обработчик редактирования настройки
$router->addRoute("/$adminRoute/settings/edit/(\d+)", 
    function(Container $container, $id): Response {
        $controller = $container->make(AdminSettingsController::class);
        return $controller->handleEdit($id);
}, ['AdminAuthenticatedMiddleware', 'CsrfMiddleware'], ['method' => 'POST']);

// Удаление настройки из БД
$router->addRoute("/$adminRoute/settings/api/delete", 
    function(Container $container): Response {
        $controller = $container->make(AdminSettingsApiController::class);
        return $controller->delete();
}, ['AdminAuthenticatedMiddleware', 'AjaxMiddleware', 'CsrfMiddleware'], ['method' => 'DELETE']);