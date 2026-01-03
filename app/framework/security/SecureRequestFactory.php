<?php

// app/Framework/Security/SecureRequestFactory.php

namespace App\Framework\Security;

use App\Framework\Config\Config;
use App\Framework\Http\Request;

class SecureRequestFactory
{
    private NonceStorageInterface $storage;
    private \Request $request;
    private string $secretKey;
    
    public function __construct(
        NonceStorageInterface $storage,
        \Request $request
    ) {
        $this->storage = $storage;
        $this->request = $request;
    }
    
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function create(?string $clientLogin = null): SecureRequest
    {
        // Если логин не передан, пытаемся получить из запроса
        if ($clientLogin === null) {
            $clientLogin = $this->request->server('PHP_AUTH_USER', '');
        }
        
        $maxDrift = \Config::get('security.MaxDriftSeconds', 300);
        $this->secretKey = $this->getSecretKeyByLogin($clientLogin);
        
        return new SecureRequest(
            $this->secretKey, 
            $maxDrift, 
            $this->storage, 
            $this->request
        );
    }
    
    public function createForLogin(string $clientLogin): SecureRequest
    {
        return $this->create($clientLogin);
    }
    
    public function createForCurrentRequest(): SecureRequest
    {
        return $this->create(); // использует логин из запроса
    }
    
    private function getSecretKeyByLogin(string $login): string
    {
        // Базовая реализация - можно расширить при необходимости
        return \Config::get('security.APP_SECRET_KEY');
        
        // Альтернативно - динамические ключи по логину:
        // $keys = $this->config->get('security.API_KEYS', []);
        // return $keys[$login] ?? $this->config->get('security.DEFAULT_KEY');
    }
}