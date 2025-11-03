<?php

class UserService
{
    private UserModel $userModel;
    private bool $isUserAdmin;
    private bool $loggedInUserId;

    public function __construct(AuthService $authService, UserModel $userModel)
    {
        $this->userModel = $userModel;
        $this->isUserAdmin = $authService->isUserAdmin();
        $this->loggedInUserId = $authService->getUserId();
    }

    public function updateUser(int $userId, array $inputData): void
    {
        // ПРОВЕРКА СУЩЕСТВОВАНИЯ ПОЛЬЗОВАТЕЛЯ
        $user = $this->userModel->getUser(id: $userId);
        if (!$user) {
            throw new \UserDataException('Пользователь не найден.', [], 404);
        }

        // ВАЛИДАЦИЯ И БИЗНЕС-ЛОГИКА
        
        // Обязательные поля
        $requiredFields = ['name', 'email'];
        if ($user['built_in'] === 0) {
            $requiredFields[] = 'role_id';
        }

        // Проверка заполненности обязательных полей
        foreach ($requiredFields as $field) {
            if (empty($inputData[$field])) {
                throw new \InvalidArgumentException("Поле '{$field}' обязательно для заполнения.");
            }
        }

        // Валидация паролей
        $hasNewPassword = !empty($inputData['password']);
        if ($hasNewPassword && $inputData['password'] !== $inputData['confirm_password']) {
            throw new \InvalidArgumentException('Пароли не совпадают.');
        }
        
        // Проверка уникальности email (если email меняется)
        if ($inputData['email'] !== $user['email'] && $this->userModel->isEmailExists($inputData['email'])) {
            throw new \UserDataException('Email уже зарегистрирован другим пользователем.');
        }

        // Проверка существования роли (только если поле role_id присутствует и меняется)
        if (isset($inputData['role_id']) && $inputData['role_id'] != $user['role_id']) {
            if (!$this->userModel->isRoleExists($inputData['role_id'])) {
                throw new \UserDataException('Несуществующая роль.');
            }
        }
        
        // ПОДГОТОВКА ДАННЫХ ДЛЯ ОБНОВЛЕНИЯ
        $updateData = [
            'name' => $inputData['name'],
            'email' => $inputData['email']
        ];

        // Добавляем роль, если это не системный пользователь
        if ($user['built_in'] === 0) {
            $updateData['role_id'] = $inputData['role_id'];
        }

        // Хешируем и добавляем пароль, если он был предоставлен
        if ($hasNewPassword) {
            $updateData['password'] = password_hash($inputData['password'], PASSWORD_DEFAULT);
        }

        $this->userModel->updateUser($userId, $updateData);
    }


    /**
     * Блокирует пользователя (устанавливает active = 0).
     * @param int $userId ID пользователя для блокировки.
     * @throws \UserDataException Если пользователь не найден.
     * @throws \InvalidArgumentException Если блокировка невозможна (например, системный пользователь).
     * @throws \Exception При ошибке базы данных.
     */
    public function blockUser(int $userId): void
    {
        $user = $this->userModel->getUser($userId);
        if (!$user) {
            throw new \UserDataException('Пользователь не найден.', [], 404);
        }
        
        // БИЗНЕС-ОГРАНИЧЕНИЯ
        
        // Нельзя блокировать системного/встроенного пользователя
        if ($user['built_in'] === 1) {
            throw new \InvalidArgumentException('Нельзя заблокировать системного пользователя.');
        }
        
        // Нельзя блокировать самого себя
        if ($userId === $this->loggedInUserId) {
            throw new \InvalidArgumentException('Нельзя заблокировать себя.');
        }
        
        // Устанавливаем статус 0 (заблокирован)
        $this->userModel->updateUserStatus($userId, 0);
    }

    /**
     * Полностью удаляет пользователя, если нет связанных данных.
     * @param int $userId ID пользователя.
     * @throws \UserDataException (404) Если пользователь не найден.
     * @throws \InvalidArgumentException (400) Если удаление невозможно из-за связанных данных.
     * @throws \PDOException (500) При ошибке базы данных.
     */
    public function deleteUser(int $userId): void
    {
        // 1. ПРОВЕРКА СУЩЕСТВОВАНИЯ ПОЛЬЗОВАТЕЛЯ
        $user = $this->userModel->getUser($userId); 
        if (!$user) {
            throw new \UserDataException('Пользователь не найден.', [], 404);
        }
        
        // Проверяем есть ли у пользователя посты и медиа
        if ($this->userModel->hasPosts($userId) || $this->userModel->hasMedia($userId)) {
            throw new \InvalidArgumentException(
                'Удаление невозможно. У пользователя есть посты и/или медиафайлы.'
            );
        }

        $this->userModel->deleteUser($userId);
    }

    /**
     * Разблокирует пользователя (устанавливает active = 1).
     * @param int $userId ID пользователя для разблокировки.
     * @throws \RuntimeException Если пользователь не найден.
     * @throws \PDOException При ошибке базы данных.
     */
    public function unblockUser(int $userId): void
    {
        $user = $this->userModel->getUser($userId); 
        if (!$user) {
            throw new \UserDataException('Пользователь не найден.', [], 404);
        }
        
        // Устанавливаем статус 1 (активен)
        $this->userModel->updateUserStatus($userId, 1); 
    }

    /**
     * Создает пользователя, выполняя всю необходимую валидацию и обработку данных.
     * @param array $inputData Входные данные от пользователя.
     * @return int ID созданного пользователя.
     * @throws \InvalidArgumentException Если данные невалидны (отсутствует поле, пароли не совпадают).
     * @throws \UserDataException Если логин/email уже заняты или роль не существует.
     */
    public function createUser(array $inputData): int
    {
        // 1. Проверка наличия и чистка данных
        $requiredFields = ['name', 'login', 'email', 'password', 'confirm_password', 'role_id'];
        $userData = [];

        foreach ($requiredFields as $field) {
            if (empty($inputData[$field])) {
                // Используем InvalidArgumentException для ошибок, связанных с входными данными
                throw new \InvalidArgumentException('Все поля обязательны для заполнения.');
            }
            // Копируем только необходимые поля
            $userData[$field] = $inputData[$field];
        }

        // 2. Дополнительная валидация
        if ($userData['password'] !== $userData['confirm_password']) {
            throw new \InvalidArgumentException('Пароли не совпадают.');
        }
        // (Здесь можно добавить проверку форматов email, длины пароля и т.п.)

        if (!validateEmail($userData['email'])) {
            throw new \InvalidArgumentException('Е-майл неверного формата.');
        }


        // 3. Бизнес-логика - Проверка уникальности и существования
        if ($this->userModel->isLoginExists($userData['login'])) {
            // Используем RuntimeException для ошибок, связанных с состоянием приложения/БД
            throw new \UserDataException('Логин уже занят.');
        }
        if ($this->userModel->isEmailExists($userData['email'])) {
            throw new \UserDataException('Email уже зарегистрирован.');
        }
        if (!$this->userModel->isRoleExists($userData['role_id'])) {
            throw new \UserDataException('Несуществующая роль.');
        }

        // 4. Подготовка данных для сохранения
        // Хеширование пароля (в сервисе или в модели - тут оставляем в сервисе, как часть подготовки)
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Удаляем подтверждение пароля перед сохранением
        unset($userData['confirm_password']);

        // 5. Вызов модели для сохранения
        return $this->userModel->createUser($userData);
    }

    /**
     * Получает список пользователей и ролей в зависимости от прав текущего пользователя.
     * @return array
     */
    public function getUsersAndRolesData(): array
    {
        // Получаем список пользователей в зависимости от роли
        // Если залогинен админ, то получаем всех пользователей,
        // иначе получаем только свои данные (того пользователя, кто залогинен)
        $users = $this->isUserAdmin 
            ? $this->userModel->getAllUsersList() 
            : [$this->userModel->getUser(id: $this->loggedInUserId)];
        
        // ВАЖНО: getUserById возвращает один массив, getAllUsersList - массив массивов.
        // Чтобы унифицировать, оборачиваем результат getUserById в массив.
        if (!$this->isUserAdmin && $users[0] === false) {
             throw new \UserDataException('Не удалось получить данные о вашем профиле.');
        }

        return [
            'users' => $users,
            'roles' => $this->userModel->getRolesList(),
        ];
    }
}
