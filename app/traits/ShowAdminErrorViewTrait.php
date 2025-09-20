<?php

// app/traits/ShowAdminErrorViewTrait.php

/**
 * Trait для вывода страницы с ошибкой в админке.
 *
 */
trait ShowAdminErrorViewTrait
{
    /**
     * Для прямых вызовов
     */
    protected function renderAdminErrorView(ViewAdmin $viewAdmin, $title, $errMsg, $httpCode)
    {
        if (!headers_sent()) {
            header("HTTP/1.0 $httpCode Server Error");
        }
        $data = [
            'adminRoute' => Config::get('admin.AdminRoute'),
            'user_name' => Auth::getUserName(),
            'title' => $title,
            'error_message' => $errMsg
        ];
        if ($viewAdmin === null)
        {
            throw new Exception('ViewAdmin null');
        }
        $viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
        exit();
    }

    /**
     * Для вызовов из методов контроллера
     */
    protected function showAdminErrorView($title, $errMsg, $httpCode = 500)
    {
        $this->renderAdminErrorView($this->viewAdmin, $title, $errMsg, $httpCode);
    }
}