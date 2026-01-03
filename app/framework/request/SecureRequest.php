<?php

namespace App\Framework\Security;

class SecureRequest
{
    private array $data = [];
    private string $secretKey;
    private int $maxDrift;
    private NonceStorageInterface $nonceStorage;
    private \Request $request;

    public function __construct(string $secretKey, int $maxDrift, 
        NonceStorageInterface $nonceStorage, \Request $request)
    {
        $this->secretKey = $secretKey;
        $this->maxDrift = $maxDrift;
        $this->nonceStorage = $nonceStorage;
        $this->request = $request;
        $this->parseIncomingRequest();
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }
    
    private function getHttpHeader(string $originalHeaderName, string $Prefix): string
    {
        $fullName = rtrim($Prefix, '_') . '_' . str_replace('-', '_', $originalHeaderName);
        return $this->request->server(mb_strtoupper($fullName), '');
    }

    private function parseIncomingRequest(): void
    {
        // 1. Получаем заголовки (учитываем, что в PHP они обычно в $_SERVER с префиксом HTTP_)
        $signature = $this->getHttpHeader(SecureResponse::HEADER_SIGNATURE, 'Http');
        $nonce     = $this->getHttpHeader(SecureResponse::HEADER_NONCE, 'Http');
        $timestamp = $this->getHttpHeader(SecureResponse::HEADER_TIMESTAMP, 'Http');
        $username  = $this->getHttpHeader(SecureResponse::HEADER_USERNAME, 'Http');

        // 2. Получаем сырое тело запроса
        $rawInput = $this->request->getBody(); //file_get_contents('php://input');
        
        if (empty($signature) || empty($nonce) || empty($timestamp)) {
            $allHeaders = $this->request->allHeaders();
            $this->logSecurityIncident('Missing Security Headers', [
                'user' => $username,
                'headers' => array_filter($allHeaders, fn($key) => str_starts_with($key, 'X-'), ARRAY_FILTER_USE_KEY)
            ]);
            throw new \Exception("Отсутствуют необходимые заголовки безопасности", 400);
        }

        // 3. Проверка подписи (Важно: порядок склейки должен совпадать с клиентом!)
        // Строка: [JSON тело][Nonce][Timestamp]
        $stringToVerify = $rawInput . $nonce . $timestamp;
        $expectedSig = hash_hmac('sha256', $stringToVerify, $this->secretKey);

        if (!hash_equals($expectedSig, $signature)) {
            $this->logSecurityIncident('Invalid Signature', [
                'user' => $username,
                'expected' => $expectedSig,
                'received' => $signature
            ]);
            throw new \Exception("Ошибка безопасности: подпись не совпадает", 403);
        }

        // 4. Проверка временного окна (защита от устаревших запросов)
        if (abs(time() - $timestamp) > $this->maxDrift) {
            $this->logSecurityIncident('Request Expired', ['timestamp' => $timestamp]);
            throw new \Exception("Запрос просрочен", 403);
        }

        // 5. Проверка Nonce (защита от повторов)
        if (!$this->nonceStorage->validateAndStore($nonce, 60)) {
            $this->logSecurityIncident('Replay Attack Detected', ['nonce' => $nonce]);
            throw new \Exception("Повторный запрос отклонен (Nonce уже использован)", 403);
        }

        // 6. Декодируем чистые бизнес-данные
        $decoded = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Некорректный JSON в теле запроса", 400);
        }

        $this->data = $decoded ?? [];
    }

    private function logSecurityIncident(string $reason, array $context): void
    {
        // Логика логирования остается прежней, она у тебя написана хорошо
        $logDir = \Config::get('logger.LogPath');
        $logFile = $logDir . DIRECTORY_SEPARATOR . \Config::get('security.LogFilename');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logData = [
            'date'    => date('Y-m-d H:i:s'),
            'ip'      => $this->request->server('REMOTE_ADDR', 'unknown'),
            'reason'  => $reason,
            'context' => $context,
            'ua'      => $this->request->server('HTTP_USER_AGENT', 'none')
        ];

        file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    public function getData(): array
    {
        return $this->data;
    }
}