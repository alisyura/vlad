<?php

class UserModel extends BaseModel {
    /**
     * Возвращает пользователя по его логину.
     * @param string $login Логин пользователя
     * @param bool $onlyActive Флаг, указывающий, нужно ли искать только активных пользователей
     * @return array|false
     */
    public function getUserByLogin(string $login, bool $onlyActive = false): array|false {
        $sql = "
            SELECT
                u.id AS id,
                u.name AS name,
                u.login AS login,
                u.password AS password,
                r.name AS role_name
            FROM users u
            INNER JOIN roles r ON r.id=u.role_id
            WHERE
                login = :login";

        // Если только активные пользователи
        if ($onlyActive) {
            $sql .= " AND u.active = 1";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':login' => $login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRolesList() {
        $stmt = $this->db->prepare("
            SELECT
                r.id AS id,
                r.name AS name,
                r.description AS description
            FROM roles r");
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
     * Возвращает данные одного пользователя по ID
     */
    public function getUserById(int $id) {
        $stmt = $this->db->prepare("
            SELECT
                u.id AS id,
                u.name AS name,
                u.active AS active,
                u.built_in AS built_in
            FROM users u
            WHERE u.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Создает нового пользователя в базе данных
     * @param array $data Данные пользователя (name, login, email, password, role_id)
     * @return bool
     */
    public function createUser(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, login, email, password, role_id, created_at)
            VALUES (:name, :login, :email, :password, :role_id, NOW())
        ");
        return $stmt->execute([
            ':name' => $data['name'],
            ':login' => $data['login'],
            ':email' => $data['email'],
            ':password' => $data['password'], // Здесь уже хешированный пароль
            ':role_id' => $data['role_id']
        ]);
    }

    /**
     * Изменяет статус пользователя в базе данных
     * @param int $userId ID пользователя
     * @param int $activeStatus Статус пользователя (0 - заблокирован, 1 - разблокирован)
     * @return bool
     */
    public function updateUserStatus($userId, $activeStatus)
    {
        $stmt = $this->db->prepare("
            UPDATE users SET active = :active WHERE id = :user_id
        ");
        return $stmt->execute([
            ':active' => $activeStatus,
            ':user_id' => $userId
        ]);
    }

    /**
     * Полностью удаляет пользователя из базы данных,
     * только если у него нет опубликованных постов или медиафайлов.
     * @param int $userId ID пользователя
     * @return bool Возвращает true в случае успешного удаления, false - если удаление не было выполнено
     */
    public function deleteUser(int $userId): bool
    {
        // 1. Проверяем, есть ли у пользователя опубликованные посты
        $stmtPosts = $this->db->prepare("
            SELECT COUNT(*) FROM posts WHERE user_id = :user_id
        ");
        $stmtPosts->execute([':user_id' => $userId]);
        $postCount = $stmtPosts->fetchColumn();

        if ($postCount > 0) {
            // У пользователя есть посты, удаление невозможно
            return false;
        }

        // 2. Проверяем, есть ли у пользователя медиафайлы
        $stmtMedia = $this->db->prepare("
            SELECT COUNT(*) FROM media WHERE user_id = :user_id
        ");
        $stmtMedia->execute([':user_id' => $userId]);
        $mediaCount = $stmtMedia->fetchColumn();

        if ($mediaCount > 0) {
            // У пользователя есть медиа, удаление невозможно
            return false;
        }

        // 3. Если постов и медиа нет, выполняем удаление пользователя
        $stmt = $this->db->prepare("
            DELETE FROM users WHERE id = :user_id
        ");
        
        return $stmt->execute([':user_id' => $userId]);
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
}