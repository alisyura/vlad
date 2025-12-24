<?php

// app/framework/response/TextResponse.php

class TextResponse extends Response
{
    // Не обязательно, но можно переопределить для явной установки HTML
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];
    }
}