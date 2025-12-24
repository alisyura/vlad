<?php

namespace App\Framework\Security;

interface NonceStorageInterface
{
    /**
     * Проверяет, использовался ли этот nonce ранее.
     * Если нет — сохраняет его и возвращает true.
     * Если да — возвращает false.
     */
    public function validateAndStore(string $nonce, int $ttl): bool;
}