<?php

namespace App\Framework\Interfaces;

use App\Framework\Security\SecureRequest;

// Интерфейс для всех обработчиков
interface ApiActionHandlerInterface
{
    public function supports(string $action): bool;
    public function handle(SecureRequest $request, array $data): array;
}