<?php

class UserModel extends BaseModel {
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

    public function getUsersList() {
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
}