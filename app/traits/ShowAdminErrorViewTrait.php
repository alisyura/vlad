<?php

// app/traits/ShowAdminErrorViewTrait.php

/**
 * Trait для вывода страницы с ошибкой в админке.
 *
 */
trait ShowAdminErrorViewTrait
{
    abstract protected function getView(): View;

    /**
     * Для прямых вызовов
     */
    protected function renderAdminErrorView(View $view, string $title, 
        string $errMsg, int $httpCode, string $userName): void
    {
        if (!headers_sent()) {
            header("HTTP/1.0 $httpCode Server Error");
        }
        $data = [
            'adminRoute' => Config::get('admin.AdminRoute'),
            'user_name' => $userName,
            'title' => $title,
            'error_message' => $errMsg
        ];
        if ($view === null)
        {
            throw new Exception('View is null');
        }
        $view->renderAdmin('admin/errors/error_view.php', $data);
        exit();
    }

    /**
     * Для вызовов из методов контроллера
     */
    protected function showAdminErrorView(string $title, string $errMsg, 
        string $userName, int $httpCode = 500): void
    {
        $this->renderAdminErrorView($this->getView(), $title, $errMsg, $httpCode, $userName);
    }
}