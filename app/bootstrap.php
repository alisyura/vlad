<?php

// app/bootstrap.php

// Создаём объект Request
$request = RequestFactory::createFromGlobals();
// Определяем URI ЗДЕСЬ, перед тем как он понадобится в контейнере
$uri = $request->getUri(); 

$container = new Container();
$container->bind(Request::class, fn($c) => $request);
// Регистрируем, что если просят 'View', нужно использовать 'ViewAdmin' 
// только в админке
// $container->bind('View', function($c) use ($uri) {
//     $adminRoute = Config::get('admin.AdminRoute');
//     if (str_starts_with($uri, "/{$adminRoute}")) {
//         $viewsRootPath = Config::get('global.ViewsRootPath');
//         return new ViewAdmin(
//             $viewsRootPath,
//             'admin/login.php',
//             'admin/admin_layout.php'
//         );
//     }

//     // Здесь мы должны вернуть объект, который принимает параметры
//     $viewsRootPath = Config::get('global.ViewsRootPath');
//     return new View($viewsRootPath);
// });

// Теперь всегда регистрируем один и тот же класс View
$container->bind(ViewAdmin::class, function() {
    $viewsRootPath = Config::get('global.ViewsRootPath');
    $loginLayoutPath = 'admin/login.php';
    $adminLayoutPath = 'admin/admin_layout.php';
    $clientLayoutPath = 'layout.php';
    return new ViewAdmin($viewsRootPath, $loginLayoutPath, $adminLayoutPath, $clientLayoutPath);
});
$container->bind(PDO::class, function() {
    $host = Config::get('db.DB_HOST');
    $name = Config::get('db.DB_NAME');
    $user = Config::get('db.DB_USER');
    $pass = Config::get('db.DB_PASS');

    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    }
});
$container->bind(PostModel::class, PostModel::class);