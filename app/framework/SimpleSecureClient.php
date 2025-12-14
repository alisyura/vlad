<?php

namespace App\Framework; // Рекомендуется использовать namespace

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Клиент для безопасного взаимодействия с внешним API.
 * Использует Basic Auth для аутентификации и HMAC-SHA256 для подписи/целостности данных (включая Nonce и Timestamp).
 */
class SimpleSecureClient
{
    private const HASH_ALGO = 'sha256';
    private const TIMEOUT = 30;
    private const NONCE_LENGTH = 16; // 16 байт = 32 символа в hex

    private ClientInterface $httpClient;
    private string $apiUrl;
    private string $username;
    private string $password;
    private string $signatureKey;

    /**
     * @param ClientInterface $httpClient Должен быть экземпляром GuzzleHttp\Client или его mock-ом.
     */
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

    /**
     * Отправляет подписанные данные на внешний API.
     *
     * @param array $data Основные данные для отправки.
     * @param string $action Имя вызываемого действия.
     * @return array Результат запроса (статус и декодированный ответ).
     * @throws \RuntimeException В случае ошибок кодирования или генерации Nonce.
     * @throws \Exception В случае сетевых или HTTP-ошибок.
     */
    public function send(array $data, string $action = ''): array
    {
        // 1. Подготовка данных: Nonce, Timestamp и Payload
        try {
            $nonce = $this->generateNonce(self::NONCE_LENGTH);
        } catch (\Exception $e) {
            throw new \RuntimeException('Не удалось сгенерировать Nonce: ' . $e->getMessage(), 0, $e);
        }

        $payload = [
            'data'      => $data,
            'timestamp' => time(),
            'action'    => $action,
            'nonce'     => $nonce,
        ];
        
        // 2. Кодирование и Подпись (HMAC)
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Ошибка кодирования JSON для подписи: ' . json_last_error_msg());
        }

        $signature = hash_hmac(self::HASH_ALGO, $json, $this->signatureKey);

        $requestData = [
            'payload'   => $payload,
            'signature' => $signature
        ];

        // 3. Отправка через Guzzle и Обработка
        return $this->executeRequest($requestData);
    }

    /**
     * Генерирует криптографически стойкий nonce.
     * @param int $length Длина Nonce в байтах.
     * @return string
     * @throws \Exception
     */
    private function generateNonce(int $length): string
    {
        // Используем random_bytes для криптографически стойкой генерации
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Выполняет HTTP-запрос, используя Guzzle.
     *
     * @param array $requestData Данные для отправки (payload и signature).
     * @return array
     * @throws GuzzleException
     * @throws \RuntimeException
     */
    private function executeRequest(array $requestData): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'json' => $requestData,
                'auth' => [$this->username, $this->password, 'basic'],
                'timeout' => self::TIMEOUT,
                // Если не нужно выбрасывать исключения для 4xx/5xx: 'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // Проверка тела ответа
            $decodedResponse = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                 // Выбрасываем исключение, если тело не является валидным JSON
                 throw new \RuntimeException(
                     'Ошибка декодирования JSON ответа: ' . json_last_error_msg() . 
                     '. Статус: ' . $statusCode
                 );
            }

            return [
                'status'   => $statusCode,
                'response' => $decodedResponse
            ];

        } catch (GuzzleException $e) {
            // GuzzleException обрабатывает все сетевые ошибки, тайм-ауты, 
            // а также ошибки 4xx (ClientException) и 5xx (ServerException) по умолчанию.
            // Мы перебрасываем его для обработки в вызывающем коде.
            throw $e; 
        }
    }
}



// composer require guzzlehttp/guzzle

// использование

// use GuzzleHttp\Client;
// use App\Framework\SimpleSecureClient;

// // 1. Инициализация HTTP-клиента
// $guzzleClient = new Client([
//     // Дополнительные настройки Guzzle, если нужны
// ]); 

// // 2. Инициализация вашего Secure Client
// $client = new SimpleSecureClient(
//     $guzzleClient,
//     'https://server/hs/api',
//     'логин',
//     'пароль',
//     'ключ-для-подписи_32_символа_abcdefg'
// );

// // 3. Отправка запроса с обработкой ошибок
// try {
//     $result = $client->send([
//         'order_id' => 123,
//         'amount' => 500.00
//     ], 'create_payment');

//     echo "Статус HTTP: " . $result['status'] . "\n";
//     print_r($result['response']);

// } catch (\GuzzleHttp\Exception\ClientException $e) {
//     // Ошибка 4xx (например, 401 Unauthorized, 404 Not Found)
//     echo "Ошибка клиента API: " . $e->getResponse()->getStatusCode() . " - " . $e->getMessage() . "\n";
// } catch (\Exception $e) {
//     // Другие ошибки (сеть, Nonce, JSON-кодирование/декодирование)
//     echo "Произошла критическая ошибка: " . $e->getMessage() . "\n";
// }