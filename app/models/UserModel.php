<?php

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getUserByLogin($login) {
        $stmt = $this->db->prepare("
        SELECT
            u.id AS id,
            u.name AS name,
            u.login AS login,
            u.password AS password,
            r.name AS role_name
        FROM users u
        INNER JOIN roles r ON r.id=u.role_id
        WHERE
            login = :login LIMIT 1");
        $stmt->execute([':login' => $login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}