<?php

namespace App\Services;

use App\Framework\Security\SecureClient;

class RestApiClientService
{
    private SecureClient $secureClient;

    public function __construct(SecureClient $secureClient)
    {
        $this->secureClient = $secureClient;
    }

    public function createPayment(float $amount): array
    {
        return $this->secureClient->send([
                'amount' => $amount
            ], 'create_payment');
    }
}