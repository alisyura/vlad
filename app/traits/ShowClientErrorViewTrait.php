<?php

// app/traits/ShowClientErrorViewTrait.php

/**
 * Trait для вспомогательных операций.
 *
 */
trait ShowClientErrorViewTrait
{
    protected function showErrorView($title, $errMsg, $httpCode = 500)
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
        if ($this->view === null)
        {
            throw new Exception('ViewAdmin null');
        }
        $this->view->renderClient('errors/error_view.php', $data);
        exit();
    }
}