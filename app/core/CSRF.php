<?php
// app/core/CSRF.php (упрощённая версия)

class CSRF {
    private static $sessionKey = 'csrf_token';

    private function __construct() {}

    /**
     * Генерирует и сохраняет CSRF-токен в сессии, если его ещё нет.
     */
    public static function generateToken(): string {
        if (empty($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$sessionKey];
    }

    /**
     * Получает текущий токен. Если его нет - генерирует.
     */
    public static function getToken(): string {
        // Если токен уже есть в сессии, возвращаем его
        if (!empty($_SESSION[self::$sessionKey])) {
            return $_SESSION[self::$sessionKey];
        }
        // Если токена в сессии нет, генерируем новый
        return self::generateToken();
    }

    /**
     * Проверяет CSRF-токен
     */
    public static function validateToken(?string $token): bool {
        // Для отладки можно оставить, но лучше убрать или сделать условным
        // echo "$token token<br>";
        // echo ($_SESSION[self::$sessionKey] ?? 'NOT SET')." sess <br>";

        if (empty($token) || empty($_SESSION[self::$sessionKey])) {
            return false;
        }

        // Сравниваем токен из запроса с токеном из сессии
        return hash_equals($_SESSION[self::$sessionKey], $token);
    }

    /**
     * Обновляет токен (например, после успешной проверки)
     */
    public static function refreshToken(): void {
        unset($_SESSION[self::$sessionKey]);
        // Генерируем новый при следующем вызове getToken() или generateToken()
    }
}
