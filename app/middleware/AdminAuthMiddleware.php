<?php

// app/middleware/AdminAuthMiddleware.php

// if (!interface_exists('MiddlewareInterface')) {
//     require_once __DIR__ . '/../middleware/MiddlewareInterface.php';
// }
// if (!class_exists('Auth')) {
//     require_once __DIR__ . '/../core/Auth.php';
// }

class AdminAuthMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет авторизацию администратора.
     * Если не авторизован - перенаправляет на страницу логина.
     * Если авторизован - продолжает выполнение.
     *
     * @return bool True если авторизован, иначе выполнение скрипта прерывается
     */
    public function handle(): bool
    {
        if (!\Auth::check()) { // Используем полное имя класса или убедитесь, что он в глобальном пространстве
            $adminRoute = Config::getAdminCfg('AdminRoute');
            header("Location: /$adminRoute/login");
            //exit; // Прерываем выполнение, как и в контроллере
            return false; // Альтернатива, если роутер обрабатывает false
        }
        return true; // Продолжаем выполнение
    }
}
