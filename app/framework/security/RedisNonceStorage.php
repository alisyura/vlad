<?php

namespace App\Framework\Security;

class RedisNonceStorage implements NonceStorageInterface
{
    private \Redis $redis;
    private string $prefix = 'api_nonce:';

    /**
     * @param \Redis $redis Передаем уже подключенный объект Redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function validateAndStore(string $nonce, int $ttl): bool
    {
        $key = $this->prefix . $nonce;

        // Команда SET с параметрами NX (set if Not eXists) и EX (expire)
        // Она атомарно проверяет существование и записывает, если ключа нет.
        // Возвращает true, если запись прошла успешно (ключа не было).
        $result = $this->redis->set($key, '1', ['nx', 'ex' => $ttl]);

        // Если результат false, значит такой nonce в базе уже есть (Replay Attack!)
        return (bool)$result;
    }

    public function invalidate(string $nonce): void
    {
        $key = $this->prefix . $nonce;
        $this->redis->del($key);
    }
}