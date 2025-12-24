<?php

namespace App\Framework\Security;

/**
 * Реализация хранилища nonce для MySQL/MariaDB через PDO.
 */
class MySqlNonceStorage implements NonceStorageInterface
{
    private \PDO $db;
    private string $tableName = 'api_nonces';

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function validateAndStore(string $nonce, int $ttl): bool
    {
        $hash = md5($nonce);
        $expiresAt = time() + $ttl;

        try {
            // Пытаемся вставить. Если первичный ключ (hash) уже есть — вылетит исключение.
            $stmt = $this->db->prepare("INSERT INTO {$this->tableName} (nonce_hash, expires_at) VALUES (?, ?)");
            $stmt->execute([$hash, $expiresAt]);

            // Вероятностная очистка (Lottery Garbage Collection)
            if (mt_rand(1, 100) === 1) {
                $this->cleanOldNonces();
            }

            return true;
        } catch (\PDOException $e) {
            // Код 23000 — нарушение уникальности (Integrity constraint violation)
            if ($e->getCode() == '23000') {
                return $this->isExpired($hash);
            }
            // Если какая-то другая ошибка БД — пробрасываем выше
            throw $e;
        }
    }

    private function isExpired(string $hash): bool
    {
        $stmt = $this->db->prepare("SELECT expires_at FROM {$this->tableName} WHERE nonce_hash = ? LIMIT 1");
        $stmt->execute([$hash]);
        $expiresAt = $stmt->fetchColumn();

        // Если запись нашли, и время истекло — значит, теоретически можно разрешить,
        // но по правилам безопасности nonce должен быть уникальным ВООБЩЕ в рамках окна TTL.
        // Поэтому, если запись есть, мы просто возвращаем false.
        return false; 
    }

    private function cleanOldNonces(): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE expires_at < ?");
        $stmt->execute([time()]);
    }
}