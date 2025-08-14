<?php
// app/core/Database.php

class Database {
    private static $pdo = null;

    /**
     * Возвращает единственное соединение с БД
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            // Получаем настройки из Config
            $host = Config::getDbHost('DB_HOST');
            $name = Config::getDbHost('DB_NAME');
            $user = Config::getDbHost('DB_USER');
            $pass = Config::getDbHost('DB_PASS');

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

        return self::$pdo;
    }
}