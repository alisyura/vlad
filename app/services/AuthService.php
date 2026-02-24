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
    public function login($login, $password):bool {
        // Здесь нужно добавить проверку на количество попыток входа
        // Например, с помощью Redis, Memcached или отдельной таблицы в базе данных.
        // Если попыток слишком много, возвращаем false.

        $user = $this->userModel->getUser(login: $login, onlyActive: true);
        
        if (!$user) {
            return false;
        }

        // ПРОВЕРКА БЛОКИРОВКИ
        // Если дата разблокировки установлена и она ещё в будущем — не пускаем.
        if (!empty($user['lockout_until']) && strtotime($user['lockout_until']) > time()) {
            return false; 
        }

        if (password_verify($password, $user['password'])) {
            // --- ПАРОЛЬ ВЕРНЫЙ ---

            // Сбрасываем счетчик ошибок в базе
            $this->userModel->resetFailedAttempts((int)$user['id']);

            $this->session->regenerateId(true);

            // Обновляем CSRF-токен после входа
            CSRF::refreshToken(); // или generateToken() — чтобы старый стал недействителен

            // Сохраняем дополнительные данные для защиты от угона сессии
            $this->session->set('user_id', (int)$user['id']); // Приводим к целому числу
            $this->session->set('is_admin', (bool)($user['role_name'] === Config::get('admin.AdminRoleName'))); // Приводим к булевому типу
            $this->session->set('user_login', (string)$user['login']); // Приводим к строковому типу
            $this->session->set('user_ip', $this->request->getClientIp());
            $this->session->set('user_agent', $this->request->getUserAgent());
            $this->session->set('user_name', (string)$user['name']);

            // Запоминаем время входа для автовыхода через 30 мин
            $this->session->set('last_activity', time());

            return true;
        }

        // --- ПАРОЛЬ НЕВЕРНЫЙ ---
        // Увеличиваем счетчик ошибок в базе (создадим этот метод в модели следующим шагом)
        $this->userModel->registerFailedAttempt(
            (int)$user['id'], 
            (int)$user['failed_attempts'],
            (int)Config::get('admin.LoginAttempts'),
            (int)Config::get('admin.LoginBlockMinutes')
        );

        // В случае неудачной попытки нужно увеличить счетчик
        // failed_login_attempts для данного логина.
        
        return false;
    }

    /**
     * Проверяет, залогинен ли пользователь, и валидна ли его сессия.
     * Не проверяет права администратора.
     * @return bool
     */
    public function check(): bool {
        // Проверка наличия, типа и содержимого всех необходимых данных в сессии
        $userId = $this->session->get('user_id');
        $userLogin = $this->session->get('user_login');
        $userName = $this->session->get('user_name');

        if (
            null !== $userId && is_int($userId) && $userId > 0 &&
            null !== $userLogin && is_string($userLogin) && !empty($userLogin) &&
            null !== $userName && is_string($userName) && !empty($userName)
        ) {
            // ПРОВЕРКА ВРЕМЕНИ АКТИВНОСТИ
            $lastActivity = $this->session->get('last_activity');
            $timeoutSeconds = (int)Config::get('admin.AutoLogoutMinutes') * 60;

            if ($lastActivity && (time() - $lastActivity > $timeoutSeconds)) {
                $this->logout(); 
                return false;
            }
            
            $userIp = $this->session->get('user_ip');
            $userAgent = $this->session->get('user_agent');
            // Проверка IP-адреса и User-Agent для защиты от угона сессии
            $getClientIp = $this->request->getClientIp();
            $getUserAgent = $this->request->getUserAgent();
            if (
                null !== $userIp && $userIp === $getClientIp &&
                null !== $userAgent && $userAgent === $getUserAgent
            ) {
                // Если всё прошло — обновляем время активности
                $this->session->set('last_activity', time());
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
    public function isUserAdmin(): bool {
        $isAdmin = $this->session->get('is_admin');
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

    public function getUserId(): int|null {
        return $this->session->get('user_id') ?? null;
    }

    public function getUserLogin(): string|null {
        return $this->session->get('user_login') ?? null;
    }

    public function getUserName(): string|null {
        return $this->session->get('user_name') ?? null;
    }
}