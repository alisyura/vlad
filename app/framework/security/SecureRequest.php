<?php

namespace App\Framework\Security;

class SecureRequest
{
    private array $data = [];
    private string $secretKey;
    private int $maxDrift;
    private NonceStorageInterface $nonceStorage;

    public function __construct(string $secretKey, int $maxDrift, NonceStorageInterface $nonceStorage)
    {
        $this->secretKey = $secretKey;
        $this->maxDrift = $maxDrift;
        $this->nonceStorage = $nonceStorage;
        $this->parseIncomingRequest();
    }

    private function parseIncomingRequest(): void
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!$input || !isset($input['payload'], $input['signature'])) {
            $this->logSecurityIncident('Malformed Request', ['raw_body' => mb_substr($rawInput, 0, 500)]);
            throw new \Exception("Неверная структура запроса", 400);
        }

        $jsonToVerify = json_encode($input['payload'], JSON_UNESCAPED_UNICODE);
        $expectedSig = hash_hmac('sha256', $jsonToVerify, $this->secretKey);

        // Проверка подписи
        if (!hash_equals($expectedSig, $input['signature'])) {
            $this->logSecurityIncident('Invalid Signature', [
                'payload' => $input['payload'],
                'received_sig' => $input['signature']
            ]);
            throw new \Exception("Ошибка безопасности: подпись не совпадает", 403);
        }

        // Проверка времени
        if (abs(time() - ($input['payload']['timestamp'] ?? 0)) > $this->maxDrift) {
            $this->logSecurityIncident('Request Expired', ['timestamp' => $input['payload']['timestamp'] ?? 0]);
            throw new \Exception("Запрос просрочен", 403);
        }

        $nonce = $input['payload']['nonce'] ?? '';

        if (!$this->nonceStorage->validateAndStore($nonce, 60)) {
            throw new \Exception("Nonce уже использован. Повторный запрос отклонен.", 403);
        }

        // Если всё ок, сохраняем чистые данные
        $this->data = $input['payload']['data'] ?? [];
    }

    private function logSecurityIncident(string $reason, array $context): void
    {
        // Путь к папке логов (на уровень выше public, например в /logs)
        $logDir = \Config::get('logger.LogPath');
        $logFile = $logDir . DIRECTORY_SEPARATOR . \Config::get('security.LogFilename');

        // Создаем папку, если её нет
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logData = [
            'date'   => date('Y-m-d H:i:s'),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'reason' => $reason,
            'context'=> $context,
            'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? 'none'
        ];

        // Записываем в файл
        file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public function getData(): array
    {
        return $this->data;
    }
}