<?php

namespace App\Handlers;

use App\Framework\Interfaces\ApiActionHandlerInterface;
use App\Framework\Security\SecureRequest;
use RuntimeException;

class CreatePaymentHandler implements ApiActionHandlerInterface
{
    public function supports(string $action): bool
    {
        return $action === 'create_payment';
    }
    
    public function handle(SecureRequest $request, array $data): array
    {
        // Валидация
        if (empty($data['amount'])) {
            throw new RuntimeException('Amount is required', 400);
        }
        
        $amount = (float)$data['amount'];
        
        // Бизнес-логика
        $orderId = $this->createPaymentInDatabase($amount);
        
        return [
            'message' => "Заказ на сумму {$amount} создан. OrderId #{$orderId}.",
            'data' => [
                'processedAt' => date('Y-m-d H:i:s'),
                'orderId' => $orderId,
                'status' => 'processed'
            ]
        ];
    }
    
    private function createPaymentInDatabase(float $amount): int
    {
        // Твоя логика работы с БД
        return 123; // заглушка
    }
}