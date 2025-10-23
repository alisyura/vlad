<?php

class UserModel extends BaseModel {
    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }
    /**
     * Получает данные пользователя по ID или логину.
     *
     * @param int|null $id       ID пользователя.
     * @param string|null $login Логин пользователя.
     * @param bool $onlyActive   Флаг, указывающий, нужно ли искать только среди активных пользователей.
     *
     * @return array|false Ассоциативный массив с данными пользователя или false, если пользователь не найден.
     */
    public function getUser(?int $id = null, ?string $login = null, bool $onlyActive = false): array|false
    {
        // Проверяем, что хотя бы один из параметров передан
        if ($id === null && $login === null) {
            return false;
        }

        $sql = "
            SELECT
                u.id AS id,
                u.name AS name,
                u.login AS login,
                u.email AS email,
                u.password AS password,
                u.active AS active,
                u.built_in AS built_in,
                r.name AS role_name,
                r.id AS role_id
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE 1=1"; // Начальное условие для удобства

        $params = [];

        if ($id !== null) {
            $sql .= " AND u.id = :id";
            $params[':id'] = $id;
        }

        if ($login !== null) {
            $sql .= " AND u.login = :login";
            $params[':login'] = $login;
        }

        // Если только активные пользователи
        if ($onlyActive) {
            $sql .= " AND u.active = 1";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRolesList() {
        $stmt = $this->db->prepare("
            SELECT
                r.id AS id,
                r.name AS name,
                r.description AS description
            FROM roles r
            ORDER BY r.name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает полный список всех пользователей (для админа)
     */
    public function getAllUsersList() {
        $stmt = $this->db->prepare("
            SELECT
                u.id AS id,
                u.name AS name,
                u.active AS active,
                u.built_in AS built_in
            FROM users u");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Создает нового пользователя в базе данных
     * @param array $data Данные пользователя (name, login, email, HASHED password, role_id)
     * @return int Возвращает данные созданного пользователя или false при ошибке
     * @throws \PDOException При ошибке выполнения запроса
     */
    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, login, email, password, role_id, created_at)
            VALUES (:name, :login, :email, :password, :role_id, NOW())
        ");
        
        $stmt->execute([
            ':name' => $data['name'],
            ':login' => $data['login'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':role_id' => $data['role_id']
        ]);

        return (int)$this->db->lastInsertId(); 
    }

    /**
     * Изменяет статус пользователя в базе данных
     * @param int $userId ID пользователя
     * @param int $activeStatus Статус пользователя (0 - заблокирован, 1 - разблокирован)
     * @return void
     * @throws \PDOException При ошибке выполнения запроса
     */
    public function updateUserStatus($userId, $activeStatus): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET active = :active WHERE id = :user_id
        ");
        $stmt->execute([
            ':active' => $activeStatus,
            ':user_id' => $userId
        ]);
    }

    /**
     * Удаляет пользователя из БД.
     * @param int $userId ID пользователя
     * @throws \PDOException При ошибке базы данных.
     */
    public function deleteUser(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    }
    public function hasPosts(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM posts WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        // fetchColumn() > 0 or rowCount() > 0 - более эффективно, чем COUNT(*)
        return $stmt->rowCount() > 0; 
    }

    public function hasMedia(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM media WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Проверяет, существует ли логин в базе данных
     * @param string $login
     * @return bool
     */
    public function isLoginExists(string $login)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE login = :login");
        $stmt->execute([':login' => $login]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Проверяет, существует ли email в базе данных
     * @param string $email
     * @return bool
     */
    public function isEmailExists(string $email)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

        /**
     * Проверяет, существует ли роль в базе данных
     * @param int $roleId
     * @return bool
     */
    public function isRoleExists(int $roleId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $stmt->execute([':id' => $roleId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Обновляет данные пользователя в базе данных.
     * @param int $id ID пользователя.
     * @param array $data Ассоциативный массив с данными для обновления.
     * @return void
     * @throws PDOException Если произошла ошибка БД (т.к. ATTR_ERRMODE => EXCEPTION).
     */
    public function updateUser(int $id, array $data): void
    {
        // Добавление updated_at, если оно еще не было добавлено в $data
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = 'NOW()'; // Специальное значение для NOW()
        }
        
        $sql = "UPDATE users SET ";
        $params = [];
        $setClauses = [];
        
        foreach ($data as $key => $value) {
            // Специальная обработка для NOW(), чтобы не экранировать его
            if ($value === 'NOW()') {
                $setClauses[] = "$key = NOW()";
            } else {
                $setClauses[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        // Если $data пуст, то это кривые данные
        if (empty($setClauses)) {
            throw new UserDataException('Передан пустой массив данных');
        }

        $sql .= implode(', ', $setClauses);
        $sql .= " WHERE id = :id";
        $params[':id'] = $id;

        $stmt = $this->db->prepare($sql);
        
        $stmt->execute($params);
    }
}