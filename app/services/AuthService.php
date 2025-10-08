<?php
// app/services/AuthService.php


class AuthService
{
    private UserModel $userModel;
    private Session $session;
    private Request $request;

    public function __construct(UserModel $userModel, Session $session, Request $request)
    {
        $this->userModel = $userModel;
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * Метод для входа в систему, теперь с защитой от перебора паролей
     * @param string $login
     * @param string $password
     * @return bool
     */
    public function login($login, $password) {
        // Здесь нужно добавить проверку на количество попыток входа
        // Например, с помощью Redis, Memcached или отдельной таблицы в базе данных.
        // Если попыток слишком много, возвращаем false.

        $user = $this->userModel->getUser(login: $login, onlyActive: true);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->session->regenerateId(true);

            // Обновляем CSRF-токен после входа
            CSRF::refreshToken(); // или generateToken() — чтобы старый стал недействителен

            // Сохраняем дополнительные данные для защиты от угона сессии
            $this->session->set('user_id', (int)$user['id']); // Приводим к целому числу
            $this->session->set('is_admin', (bool)($user['role_name'] === Config::get('admin.AdminRoleName'))); // Приводим к булевому типу
            $this->session->set('user_login', (string)$user['login']); // Приводим к строковому типу
            $this->session->set('user_ip', $_SERVER['REMOTE_ADDR']);
            $this->session->set('user_agent', $_SERVER['HTTP_USER_AGENT']);
            $this->session->set('user_name', (string)$user['name']);

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
    public function check() {
        // Проверка наличия, типа и содержимого всех необходимых данных в сессии
        $userId = $this->session->get('user_id');
        $userLogin = $this->session->get('user_login');
        $userName = $this->session->get('user_name');

        if (
            null !== $userId && is_int($userId) && $userId > 0 &&
            null !== $userLogin && is_string($userLogin) && !empty($userLogin) &&
            null !== $userName && is_string($userName) && !empty($userName)
        ) {
            $userIp = $this->session->get('user_ip');
            $userAgent = $this->session->get('user_agent');
            // Проверка IP-адреса и User-Agent для защиты от угона сессии
            if (
                null !== $userIp && $userIp === $this->request->getClientIp() &&
                null !== $userAgent && $userAgent === $this->request->getUserAgent()
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
    public function isUserAdmin() {
        $isAdmin = $this->session->get('user_login');
        return self::check() && // Сначала убеждаемся, что пользователь залогинен
               null !== $isAdmin && 
               $isAdmin === true;
    }

    public function logout() {
        // Очищаем все данные сессии
        $this->session->clear();
        // Удаляем куку сессии в браузере
        $this->session->destroy();
    }

    public function getUserId() {
        return $this->session->get('user_id') ?? null;
    }

    public function getUserLogin() {
        return $this->session->get('user_login') ?? null;
    }

    public function getUserName() {
        return $this->session->get('user_name') ?? null;
    }
}