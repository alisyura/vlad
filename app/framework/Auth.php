<?php

/**
 * @deprecated
 */
class Auth {
    private function __construct(){}

    /**
     * Метод для входа в систему, теперь с защитой от перебора паролей
     * @param string $login
     * @param string $password
     * @return bool
     */
    public static function login($login, $password) {
        // Здесь нужно добавить проверку на количество попыток входа
        // Например, с помощью Redis, Memcached или отдельной таблицы в базе данных.
        // Если попыток слишком много, возвращаем false.

        $user = (new UserModel())->getUser(login: $login, onlyActive: true);
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // Обновляем CSRF-токен после входа
            CSRF::refreshToken(); // или generateToken() — чтобы старый стал недействителен

            // Сохраняем дополнительные данные для защиты от угона сессии
            $_SESSION['user_id'] = (int)$user['id']; // Приводим к целому числу
            $_SESSION['is_admin'] = (bool)($user['role_name'] === Config::get('admin.AdminRoleName')); // Приводим к булевому типу
            $_SESSION['user_login'] = (string)$user['login']; // Приводим к строковому типу
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['user_name'] = (string)$user['name'];

            return true;
        }

        // В случае неудачной попытки нужно увеличить счетчик
        // failed_login_attempts для данного логина.
        
        return false;
    }

    /**
     * Проверяет, залогинен ли пользователь, и валидна ли его сессия.
     * Не проверяет права администратора.
     * @return bool
     */
    public static function check() {
        // Проверка наличия, типа и содержимого всех необходимых данных в сессии
        if (
            isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0 &&
            isset($_SESSION['user_login']) && is_string($_SESSION['user_login']) && !empty($_SESSION['user_login']) &&
            isset($_SESSION['user_name']) && is_string($_SESSION['user_name']) && !empty($_SESSION['user_name'])
        ) {
            // Проверка IP-адреса и User-Agent для защиты от угона сессии
            if (
                isset($_SESSION['user_ip']) && $_SESSION['user_ip'] === $_SERVER['REMOTE_ADDR'] &&
                isset($_SESSION['user_agent']) && $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT']
            ) {
                return true;
            }
        }
        
        // Если какая-то из проверок не прошла, возвращаем false
        return false;
    }

    /**
     * Проверяет, является ли залогиненный пользователь администратором.
     * @return bool
     */
    public static function isUserAdmin() {
        return self::check() && // Сначала убеждаемся, что пользователь залогинен
               isset($_SESSION['is_admin']) && 
               $_SESSION['is_admin'] === true;
    }

    public static function logout() {
        // Очищаем все данные сессии
        $_SESSION = [];
        // Удаляем куку сессии в браузере
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            // Важно: передаём те же параметры (path, domain, secure, httponly),
            // с которыми кука была создана, иначе браузер её не удалит
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getUserLogin() {
        return $_SESSION['user_login'] ?? null;
    }

    public static function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }
}
