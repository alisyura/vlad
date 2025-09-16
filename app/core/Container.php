<?php

class Container
{
    protected array $bindings = [];

    /**
     * Зарегистрировать "связь" между интерфейсом и его реализацией.
     * Или зарегистрировать класс с замыканием для его создания.
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Разрешить (создать) экземпляр класса.
     *
     * @param string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // Проверяем, есть ли зарегистрированный "бинд"
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if ($concrete instanceof Closure) {
                return $concrete($this);
            }
            $abstract = $concrete;
        }

        // Используем Reflection для анализа класса и его зависимостей
        $reflector = new ReflectionClass($abstract);

        // Если класс не имеет конструктора или он не имеет зависимостей, просто создаём его
        if (!$reflector->isInstantiable() || ($constructor = $reflector->getConstructor()) === null) {
            return new $abstract;
        }

        // Получаем зависимости из конструктора
        $dependencies = $this->getDependencies($constructor->getParameters());

        // Создаём новый экземпляр класса, передавая зависимости
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Рекурсивно получаем все зависимости класса.
     *
     * @param array $parameters
     * @return array
     * @throws ReflectionException
     */
    protected function getDependencies(array $parameters): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } else {
                // Если зависимость не является классом (например, строка или число),
                // и у нее есть значение по умолчанию, используем его.
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // Если нет значения по умолчанию, выбрасываем исключение
                    throw new Exception("Невозможно разрешить зависимость '{$parameter->getName()}'");
                }
            }
        }
        return $dependencies;
    }
}


// Как использовать
// 1. В index.php (или bootstrap.php)
// Создайте экземпляр контейнера и зарегистрируйте в нём ключевые зависимости.

// PHP

// // Создаём экземпляр контейнера
// $container = new Container();

// // Регистрируем объект Request
// $container->bind('request', fn($c) => RequestFactory::createFromGlobals());

// // Регистрируем, что если просят 'View', нужно использовать 'ViewAdmin' 
// // только в админке
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
//     return new View();
// });




//  В роутере
// Теперь роутер не должен принимать зависимости вручную. Вместо этого, он будет использовать контейнер для создания контроллера и внедрения в него всего необходимого.

// PHP

// // Вместо:
// // $router->dispatch($request, $viewAdmin);
// $router->dispatch($request, $container); // Теперь роутер получает контейнер

// // ... в методе dispatch ...

// // Вместо:
// // (new AdminPostsController($request, $viewAdmin))->list(...)
// // Теперь вы можете использовать контейнер для создания контроллера
// $controller = $container->make(AdminPostsController::class);

// // Если вам нужно передать параметры из роута:
// $controller->list($page, $articleType);




// В контроллере
// Теперь ваш контроллер может запрашивать свои зависимости в конструкторе, и контейнер автоматически их предоставит.

// PHP

// // Раньше:
// // class AdminPostsController {
// //     public function __construct(Request $request, ViewAdmin $viewAdmin) { ... }
// // }

// // Теперь:
// class AdminPostsController
// {
//     protected Request $request;
//     protected View $view;

//     public function __construct(Request $request, View $view) // Контейнер автоматически внедрит их
//     {
//         $this->request = $request;
//         $this->view = $view;
//     }

//     public function list($page, $articleType)
//     {
//         // ... используем $this->request и $this->view
//     }
// }