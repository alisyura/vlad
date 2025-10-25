<?php

// app/framework/response/HtmlResponse.php

class HtmlResponse extends Response
{
    // Не обязательно, но можно переопределить для явной установки HTML
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
    }
}