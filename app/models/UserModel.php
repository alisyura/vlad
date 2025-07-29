<?php

class UserModel {
    private $db;

    public function __construct() {
        $dbHost = Config::getDbHost('DB_HOST');
        $dbName = Config::getDbHost('DB_NAME');
        $dbUser = Config::getDbHost('DB_USER');
        $dbPass = Config::getDbHost('DB_PASS');

        $this->db = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
    }

    public function getUserByLogin($login) {
        $stmt = $this->db->prepare("
        SELECT
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