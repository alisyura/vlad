<?php

class Session
{
    /**
     * Запускает сессию, если она еще не запущена.
     * Это безопасный способ вызова session_start().
     */
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Проверяем, что заголовки еще не были отправлены
            if (headers_sent()) {
                // В реальном приложении здесь лучше бросить исключение или логировать ошибку
                // trigger_error('Cannot start session. Headers already sent.', E_USER_WARNING);
                throw new SessionException("Cannot start session. Headers already sent.", E_USER_WARNING);
                return;
            }
            // Запускаем сессию. Можно настроить параметры, например, session_set_cookie_params()
            session_start();
        }
    }

    /**
     * Получает значение из сессии по ключу.
     *
     * @param string $key Ключ.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Устанавливает значение в сессии по ключу.
     *
     * @param string $key Ключ.
     * @param mixed $value Значение для установки.
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Проверяет наличие ключа в сессии.
     *
     * @param string $key Ключ.
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Удаляет ключ из сессии.
     *
     * @param string $key Ключ.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Очищает все данные сессии, но оставляет саму сессию активной.
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Генерирует новый ID сессии и (опционально) удаляет старую сессию.
     * Рекомендуется после успешного логина/смены прав.
     *
     * @param bool $deleteOld Удалить ли старые данные сессии.
     */
    public function regenerateId(bool $deleteOld = true): void
    {
        session_regenerate_id($deleteOld);
    }

    /**
     * Полностью уничтожает сессию (удаляет данные и куку).
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->clear(); // Очищаем данные

            // Удаляем куку сессии
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
        }
    }
}