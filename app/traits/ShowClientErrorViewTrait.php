<?php

// app/traits/ShowClientErrorViewTrait.php

/**
 * Trait для вывода страницы с ошибкой на клиенте.
 * @deprecated Перенесено в ErrorResponseFactory
 *
 */
trait ShowClientErrorViewTrait
{
    abstract protected function getView(): View;

    /**
     * Для прямых вызовов
     * @deprecated Использовать вместо него ErrorResponseFactory::createClientError
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
     * @deprecated Использовать вместо него ErrorResponseFactory::createClientError
     */
    protected function showErrorView($title, $errMsg, $httpCode = 500)
    {
        $this->renderErrorView($this->getView(), $title, $errMsg, $httpCode);
    }
}