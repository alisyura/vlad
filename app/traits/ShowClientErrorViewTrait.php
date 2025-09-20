<?php

// app/traits/ShowClientErrorViewTrait.php

/**
 * Trait для вывода страницы с ошибкой на клиенте.
 *
 */
trait ShowClientErrorViewTrait
{
    /**
     * Для прямых вызовов
     */
    protected function renderErrorView(ViewAdmin $view, $title, $errMsg, $httpCode = 500)
    {
        if (!headers_sent()) {
            header("HTTP/1.0 $httpCode Server Error");
        }
        $data = [
            'title' => $title,
            'error_message' => $errMsg,
            'export' => [
                'styles' => [
                    'list.css'
                ],
                'jss' => [
                ]
            ]
        ];
        if ($view === null)
        {
            throw new Exception('ViewAdmin null');
        }
        $view->renderClient('errors/error_view.php', $data);
        exit();
    }

    /**
     * Для вызовов из методов контроллера
     */
    protected function showErrorView($title, $errMsg, $httpCode = 500)
    {
        $this->renderErrorView($this->view, $title, $errMsg, $httpCode);
    }
}