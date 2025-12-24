<?php

namespace App\Framework\Security;

class FileNonceStorage implements NonceStorageInterface
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function validateAndStore(string $nonce, int $ttl): bool
    {
        $nonceFile = $this->storageDir . DIRECTORY_SEPARATOR . md5($nonce);

        // Если файл существует, значит nonce уже использовался
        if (file_exists($nonceFile)) {
            // Но проверим, не протух ли он?
            if (filemtime($nonceFile) + $ttl > time()) {
                return false; // Еще живой и уже был — это атака повтора!
            }
        }

        // Сохраняем nonce (просто создаем пустой файл)
        file_put_contents($nonceFile, '');

        // Маленькая уборка: удаляем старые файлы nonce раз в 100 запросов
        if (mt_rand(1, 100) === 1) { 
            $this->cleanOldNonces($ttl); 
        }

        return true;
    }

    private function cleanOldNonces(int $ttl): void 
    {
        // Сканируем папку с файлами nonce
        $files = glob($this->storageDir . DIRECTORY_SEPARATOR . '*');
        $now = time();

        foreach ($files as $file) {
            $mtime = filemtime($file);
            if (is_file($file)) {
                // Если файл старше, чем текущее время минус TTL, удаляем его
                if ($mtime + $ttl < $now) {
                    @unlink($file);
                }
            }
        }
    }
}