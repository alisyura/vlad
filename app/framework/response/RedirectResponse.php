<?php

// app/framework/response/RedirectResponse.php

class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302, array $headers = [])
    {
        // Убеждаемся, что статус-код подходит для перенаправления
        if (!in_array($statusCode, [301, 302, 303, 307, 308])) {
             $statusCode = 302;
        }

        // Добавляем заголовок Location
        $headers = array_merge(['Location' => $url], $headers); 

        // Тело ответа перенаправления пусто
        parent::__construct('', $statusCode, $headers);
    }

    /**
     * Отправляет только заголовки (включая Location) и статус-код. 
     * Тело ответа при перенаправлении не требуется.
     */
    public function send(): void
    {
        $this->sendHeaders(); 
        
        // Не выводим контент.
    }
}