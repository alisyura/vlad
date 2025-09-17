<?php
// app/core/Database.php

// final class Database {
//     private static $pdo = null;

//     /**
//      * Возвращает единственное соединение с БД
//      */
//     public static function getConnection(): PDO {
//         if (self::$pdo === null) {
//             // Получаем настройки из Config
//             $host = Config::get('db.DB_HOST');
//             $name = Config::get('db.DB_NAME');
//             $user = Config::get('db.DB_USER');
//             $pass = Config::get('db.DB_PASS');

//             $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";

//             $options = [
//                 PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//                 PDO::ATTR_EMULATE_PREPARES   => false,
//             ];

//             try {
//                 self::$pdo = new PDO($dsn, $user, $pass, $options);
//             } catch (PDOException $e) {
//                 die("Ошибка подключения к базе данных: " . $e->getMessage());
//             }
//         }

//         return self::$pdo;
//     }
// }

final class Database {
    private PDO $pdo;

    public function __construct(string $host, string $name, string $user, string $pass)
    {
        if ($this->pdo === null) {
            // Получаем настройки из Config
            $host = Config::get('db.DB_HOST');
            $name = Config::get('db.DB_NAME');
            $user = Config::get('db.DB_USER');
            $pass = Config::get('db.DB_PASS');

            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    /**
     * Возвращает единственное соединение с БД
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }
}