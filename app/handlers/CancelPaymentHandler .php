<?php

namespace App\Handlers;

use App\Framework\Interfaces\ApiActionHandlerInterface;
use App\Framework\Security\SecureRequest;
use RuntimeException;

class CancelPaymentHandler implements ApiActionHandlerInterface
{
    public function supports(string $action): bool
    {
        return $action === 'cancel_payment';
    }
    
    public function handle(SecureRequest $request, array $data): array
    {
        if (empty($data['payment_id'])) {
            throw new RuntimeException('Payment ID is required', 400);
        }
        
        // Логика отмены...
        return ['message' => 'Payment cancelled'];
    }
}