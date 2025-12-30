<?php

namespace App\Framework\Security;

class SecureResponse extends \JsonResponse
{
    // Имена заголовков
    public const HEADER_USERNAME = 'X-API-Username';
    public const HEADER_SIGNATURE = 'X-API-Signature';
    public const HEADER_TIMESTAMP = 'X-API-Timestamp';
    public const HEADER_NONCE = 'X-API-Nonce';
    public const HEADER_ACTION = 'X-API-Action';
    public const HEADER_RESPONSE_SIG = 'X-Response-Signature';

    public function __construct(string $signatureKey, array $data, 
        int $statusCode = 200, array $headers = [])
    {
        // 1. Сначала готовим чистое тело ответа (JSON)
        // Обычно в $data у тебя ['payload' => [...], 'success' => true]
        $jsonContent = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        // 2. Создаем подпись именно от этого JSON
        $signature = hash_hmac('sha256', $jsonContent, $signatureKey);

        // 3. Добавляем подпись в массив заголовков
        $headers[self::HEADER_RESPONSE_SIG] = $signature;

        // 4. Передаем всё в родительский класс Response
        parent::__construct($data, $statusCode, $headers);
    }
}