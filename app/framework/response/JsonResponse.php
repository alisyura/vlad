<?php

// app/framework/response/JsonResponse.php

class JsonResponse extends Response
{
    /**
     * Конструктор JsonResponse.
     * @param array $data Данные для кодирования в JSON.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки, которые переопределят значения по умолчанию.
     */
    public function __construct(array $data, int $statusCode = 200, array $headers = [])
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR); 
        
        parent::__construct($content, $statusCode, $headers);
    }

    /**
     * Переопределяет заголовки по умолчанию, устанавливая Content-Type для JSON.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json; charset=UTF-8',
        ];
    }
}