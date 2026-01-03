<?php

namespace App\Handlers;

use App\Framework\Interfaces\ApiActionHandlerInterface;

class RefundHandler implements ApiActionHandlerInterface
{
    public function supports(string $action): bool
    {
        return $action === 'refund_payment';
    }
    
    public function handle($request, array $data): array
    {
        // Твоя логика возврата платежа
        return ['message' => 'Payment refunded'];
    }
}