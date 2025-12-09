<?php

// app/bootstrap.php

$container = new Container();
$container->singleton(ErrorHandler::class, ErrorHandler::class); 
$container->bind(Request::class, fn($c) => RequestFactory::getInstance());
$container->singleton(ResponseFactory::class, ResponseFactory::class); 
$container->singleton(ApplicationResponseFactory::class, ApplicationResponseFactory::class); 
$container->singleton(ErrorResponseFactory::class, ErrorResponseFactory::class); 
$container->bind(View::class, function() {
    $viewsRootPath = Config::get('global.ViewsRootPath');
    $loginLayoutPath = 'admin/login.php';
    $adminLayoutPath = 'admin/admin_layout.php';
    $clientLayoutPath = 'layout.php';
    return new View($viewsRootPath, $loginLayoutPath, $adminLayoutPath, $clientLayoutPath);
});
$container->singleton(PDO::class, function() {
    $host = Config::get('db.DB_HOST');
    $name = Config::get('db.DB_NAME');
    $user = Config::get('db.DB_USER');
    $pass = Config::get('db.DB_PASS');

    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        Logger::error("Ошибка подключения к БД.", ['code' => $e->getCode(), 'message' => $e->getMessage()], $e);
        throw new \RuntimeException("Не удалось подключиться к базе данных. Пожалуйста, попробуйте позже.");
    }
});

$container->bind(PaginationService::class, PaginationService::class);
$container->bind(PostController::class, PostController::class);
$container->bind(PostModelClient::class, PostModelClient::class);
$container->bind(AjaxController::class, AjaxController::class);
$container->bind(TagsModelClient::class, TagsModelClient::class);
$container->bind(TagsController::class, TagsController::class);
$container->bind(ContactController::class, ContactController::class);
$container->bind(ContactFormValidator::class, ContactFormValidator::class);
$container->bind(VotingController::class, VotingController::class);
$container->bind(VotingService::class, VotingService::class);
$container->bind(VotingModel::class, VotingModel::class);
$container->bind(SitemapController::class, SitemapController::class);
$container->bind(SitemapModel::class, SitemapModel::class);
$container->bind(SubmissionController::class, SubmissionController::class);
$container->bind(SubmissionModel::class, SubmissionModel::class);
$container->bind(SubmissionService::class, SubmissionService::class);
$container->bind(LinkValidator::class, LinkValidator::class);
$container->bind(MediaService::class, MediaService::class);


$container->bind(AdminAuthenticatedMiddleware::class, AdminAuthenticatedMiddleware::class);
$container->bind(AjaxMiddleware::class, AjaxMiddleware::class);
$container->bind(CsrfMiddleware::class, CsrfMiddleware::class);
$container->bind(PageCacheMiddleware::class, PageCacheMiddleware::class);
$container->bind(UserAuthenticatedMiddleware::class, UserAuthenticatedMiddleware::class);


$container->bind(AdminLoginController::class, AdminLoginController::class);
$container->bind(AuthService::class, AuthService::class);
$container->bind(UserModel::class, UserModel::class);
$container->bind(Session::class, Session::class);
$container->bind(AdminDashboardController::class, AdminDashboardController::class);
$container->bind(DashboardModel::class, DashboardModel::class);
$container->bind(AdminPostsController::class, AdminPostsController::class);
$container->bind(PostModelAdmin::class, PostModelAdmin::class);
$container->bind(ListModel::class, ListModel::class);
$container->bind(AdminMediaModel::class, AdminMediaModel::class);
$container->bind(AdminPostsApiController::class, AdminPostsApiController::class);
$container->bind(AdminPostsApiService::class, AdminPostsApiService::class);
$container->bind(AdminTagsController::class, AdminTagsController::class);
$container->bind(AdminTagsApiController::class, AdminTagsApiController::class);
$container->bind(AdminUsersController::class, AdminUsersController::class);
$container->bind(AdminUsersApiController::class, AdminUsersApiController::class);
$container->bind(AdminSettingsController::class, AdminSettingsController::class);
$container->bind(SettingsModel::class, SettingsModel::class);
$container->bind(SettingsService::class, SettingsService::class);
$container->bind(SettingsValidator::class, SettingsValidator::class);
$container->bind(AdminSettingsApiController::class, AdminSettingsApiController::class);
$container->bind(AdminCacheApiController::class, AdminCacheApiController::class);