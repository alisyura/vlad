<?php

namespace App\Framework\Security;

class NonceStorageFactory
{
    public function __construct(
        private ?\Redis $redis = null, 
        private ?\PDO $pdo = null,
        private ?string $nonceFileDir = null
    ) {}

    public function create(): NonceStorageInterface
    {
        $driver = \Config::get('security.NonceDriver');
        
        switch ($driver) {
            case 'redis':
                return new RedisNonceStorage($this->redis); // ← Используем $this->redis
                
            case 'mysql':
            case 'mariadb':
                return new MySqlNonceStorage($this->pdo); // ← Используем $this->pdo
                
            case 'file':
                return new FileNonceStorage($this->nonceFileDir);

            default:
                // Если передали что-то совсем странное:
                throw new \InvalidArgumentException("Неизвестный драйвер nonce-хранилища: [$driver]");
        }
    }
}