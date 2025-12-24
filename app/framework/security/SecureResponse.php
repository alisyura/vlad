<?php

namespace App\Framework\Security;

use Response;

class SecureResponse extends Response
{
    public function __construct(string $signatureKey, array $data, 
        int $statusCode = 200, array $headers = [])
    {
        $signedData = $this->signContent($data, $signatureKey);
        $signedContent = json_encode($signedData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); 
        parent::__construct($signedContent, $statusCode, $headers);
    }

    /**
     * Формирует и отправляет подписанный JSON-ответ.
     */
    private function signContent(array $payload, string $signatureKey): array
    {
        // Добавляем технические данные в ответ
        $payload['server_timestamp'] = time();

        // Кодируем данные для подписи
        $jsonPayload = json_encode($payload['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        
        // Создаем подпись
        $signature = hash_hmac('sha256', $jsonPayload, $signatureKey);

        $payload['signature'] = $signature;

        return $payload;
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