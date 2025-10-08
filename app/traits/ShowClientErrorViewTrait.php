<?php

// app/traits/ShowClientErrorViewTrait.php

/**
 * Trait для вывода страницы с ошибкой на клиенте.
 *
 */
trait ShowClientErrorViewTrait
{
    abstract protected function getView(): View;

    /**
     * Для прямых вызовов
     */
    private function renderErrorView(View $view, $title, $errMsg, $httpCode = 500)
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
            throw new Exception('View is null');
        }
        $view->renderClient('errors/error_view.php', $data);
        exit();
    }

    /**
     * Для вызовов из методов контроллера
     */
    protected function showErrorView($title, $errMsg, $httpCode = 500)
    {
        $this->renderErrorView($this->getView(), $title, $errMsg, $httpCode);
    }
}