<?php

namespace App\Framework\Security;

use Config;
use PDO;

class NonceStorageFactory
{
    public static function create(): NonceStorageInterface
    {
        $driver = \Config::get('security.NonceDriver');

        switch ($driver) {
            case 'redis':
                $redis = new \Redis();
                $redis->connect(
                    \Config::get('redis.host'), 
                    \Config::get('redis.port')
                );
                return new RedisNonceStorage($redis);
            case 'mysql':
            case 'mariadb':
                // временно инициализация тут, пока не перевели на DI
                $dbHost=Config::get('db.DB_HOST');
                $dbName=Config::get('db.DB_NAME');
                $dbUser=Config::get('db.DB_USER');
                $dbPass=Config::get('db.DB_PASS');
                $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ];
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

                return new MySqlNonceStorage($pdo);
            case 'file':
                return new FileNonceStorage(\Config::get('logger.LogPath') . '/nonces');
            default:
                // Если передали что-то совсем странное:
                throw new \InvalidArgumentException("Неизвестный драйвер nonce-хранилища: [$driver]");
        }        
    }
}