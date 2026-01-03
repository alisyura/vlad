<?php

namespace App\Services;

use App\Framework\Security\SecureRequestFactory;
use RuntimeException;
use App\Exception\RestApiException;
use App\Framework\Interfaces\ApiActionHandlerInterface;

class RestApiServerProcessorService
{
    public const SECRET_KET_COLUMN = 'secretKey';

    private SecureRequestFactory $secureRequestFactory;
    private string $secretKey;

    /** @var ApiActionHandlerInterface[] */
    private array $handlers = [];

    public function __construct(SecureRequestFactory $secureRequestFactory,
        Iterable $handlers = [] // Автоматическая инъекция всех обработчиков
        )
    {
        $this->secureRequestFactory = $secureRequestFactory;

        // Регистрируем обработчики
        foreach ($handlers as $handler) {
            if ($handler instanceof ApiActionHandlerInterface) {
                $this->handlers[] = $handler;
            }
        }
    }

    public function addHandler(ApiActionHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function processApiRequest(string $clientLogin): array
    {
        if (empty($clientLogin)) {
            throw new RuntimeException('Authentication required', 401);
        }

        $secRequest = $this->secureRequestFactory->createForLogin($clientLogin);
        $action = $secRequest->getAction();
        $this->secretKey = $secRequest->getSecretKey();
        $incomingData = $secRequest->getData();

        // Ищем подходящий обработчик
        foreach ($this->handlers as $handler) {
            if ($handler->supports($action)) {
                $result = $handler->handle($secRequest, $incomingData);
                $result[self::SECRET_KET_COLUMN] = $this->secretKey;
                return $result;
            }
        }

        // if ($action == 'create_payment')
        // {
        //     $incomingData = $secRequest->getData();          
        //     // Валидация обязательных полей
        //     if (empty($incomingData['amount'])) {
        //         throw new RuntimeException(
        //             'Amount is required', 
        //             400 // HTTP 400 Bad Request
        //         );
        //     }

        //     $amount = (float)$incomingData['amount'];
        //     $result = $this->processCreatePayment($amount);
        //     $result[RestApiServerProcessorService::SECRET_KET_COLUMN] = $secRequest->getSecretKey();

        //     return $result;
        // }
        
        throw new RestApiException("Unknown action: {$action}");
    }

    private function processCreatePayment(float $amount): array
    {
        // бизнес-логика здесь
        // Например:
        // 1. Проверка существования заказа
        // 2. Обновление статуса
        // 3. Логирование
        // 4. etc.
        
        $orderId = 123;

        return [
            'message' => "Заказ на сумму {$amount} создан. OrderId #{$orderId}.",
            'received' => ['amount' => $amount],
            'data' => [
                'processedAt' => date('Y-m-d H:i:s'),
                'orderId' => $orderId,
                'status' => 'processed'
            ]
        ];
    }
    
}