<?php

// app/framework/response/RedirectResponse.php

class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302, array $headers = [])
    {
        // Добавляем заголовок Location
        $headers = array_merge(['Location' => $url], $headers); 

        // Тело ответа перенаправления пусто
        parent::__construct('', $statusCode, $headers);
    }
}