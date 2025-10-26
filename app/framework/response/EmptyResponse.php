<?php

// app/framework/response/EmptyResponse.php

class EmptyResponse extends Response
{
    // Для ответов 204 (No Content) или 304 (Not Modified)
    public function __construct(int $statusCode = 204, array $headers = [])
    {
        // Контент принудительно пуст
        parent::__construct('', $statusCode, $headers);
    }

    /**
     * Отправляет только заголовки и статус-код. Тело ответа остается пустым.
     */
    public function send(): void
    {
        $this->sendHeaders(); 
        
        // Не выводим контент, так как для 204/304 он запрещен.
    }
}