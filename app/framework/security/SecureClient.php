<?php

namespace App\Framework\Security;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Logger;

class SecureClient
{
    private const HASH_ALGO = 'sha256';
    private const TIMEOUT = 120;
    private const NONCE_LENGTH = 16;

    private ClientInterface $httpClient;
    private string $apiUrl;
    private string $username;
    private string $password;
    private string $signatureKey;

    public function __construct(
        ClientInterface $httpClient,
        string $apiUrl,
        string $username,
        string $password,
        string $signatureKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->username = $username;
        $this->password = $password;
        $this->signatureKey = $signatureKey;
    }

    public function send(array $data, string $action = ''): array
    {
        // 1. Генерируем метаданные
        $nonce = bin2hex(random_bytes(self::NONCE_LENGTH));
        $timestamp = time();
        $username = $this->username;

        // 2. Готовим тело запроса (теперь тут только чистые данные)
        $bodyJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if ($bodyJson === false) {
            throw new \RuntimeException('Ошибка кодирования JSON: ' . json_last_error_msg());
        }

        /** * 3. Формируем строку для подписи. 
         * Важно, чтобы сервер собирал её в таком же порядке:
         * подпись = hmac(body + nonce + timestamp + username + action)
         */
        $stringToSign = $bodyJson . $nonce . $timestamp . $username . $action;
        $signature = hash_hmac(self::HASH_ALGO, $stringToSign, $this->signatureKey);

        // 4. Формируем заголовки
        $headers = [
            SecureResponse::HEADER_USERNAME  => $username,
            SecureResponse::HEADER_SIGNATURE => $signature,
            SecureResponse::HEADER_NONCE     => $nonce,
            SecureResponse::HEADER_TIMESTAMP => $timestamp,
            SecureResponse::HEADER_ACTION    => $action, // Если action важен для маршрутизации
            'Accept'      => 'application/json',
        ];

        return $this->executeRequest($data, $headers);
    }

    /**
     * Выполняет HTTP-запрос, используя Guzzle.
     *
     * @param array $data Данные для отправки.
     * @param array $headers Заголовки (X-Signature, X-Nonce, X-Timestamp, X-Username).
     * @return array
     * @throws GuzzleException
     * @throws \RuntimeException
     */
    private function executeRequest(array $data, array $headers): array
    {
        try {
            // 1. Отправляем запрос
            // 'json' автоматически установит Content-Type: application/json
            // 'auth' добавит стандартный заголовок Authorization: Basic ...
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => $headers,
                'json'    => $data, 
                'auth'    => [$this->username, $this->password, 'basic'],
                'timeout' => self::TIMEOUT,
            ]);

            // 2. Получаем сырое тело ответа для проверки подписи
            $rawBody = $response->getBody()->getContents();
            
            // 3. Извлекаем подпись сервера из заголовков
            $serverSignature = $response->getHeaderLine(SecureResponse::HEADER_RESPONSE_SIG);

            if (empty($serverSignature)) {
                throw new \RuntimeException('Ответ сервера не содержит подписи в заголовках');
            }

            // 4. Проверяем подпись сервера (простая схема: подпись только от тела)
            $expectedSignature = hash_hmac(
                self::HASH_ALGO, 
                $rawBody, 
                $this->signatureKey
            );

            if (!hash_equals($expectedSignature, $serverSignature)) {
                // Логируем инцидент безопасности (без данных в целях безопасности, или аккуратно)
                Logger::error('Критическая ошибка: Подпись сервера не совпадает!', [
                    'expected' => $expectedSignature,
                    'received' => $serverSignature
                ]);
                throw new \RuntimeException('Критическая ошибка: Подпись сервера не совпадает!');
            }

            // 5. Декодируем ответ для возврата в бизнес-логику
            $decoded = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            return [
                'status'   => $response->getStatusCode(),
                'success'  => $decoded['success'] ?? true, // если сервер возвращает флаг успеха
                'response' => $decoded
            ];

        } catch (GuzzleException $e) {
            // Логируем сетевые ошибки или 4xx/5xx ответы
            Logger::error('Ошибка при вызове внешнего API', [
                'url'    => $this->apiUrl,
                'user'   => $this->username,
                'error'  => $e->getMessage()
            ]);
            throw $e; 
        }
    }
}