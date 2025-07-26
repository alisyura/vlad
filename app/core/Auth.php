<?php

class Auth {
    public static function login($login, $password) {
        $user = (new UserModel())->getUserByLogin($login);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        
        return false;
    }

    public static function check() {
        return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
    }

    public static function logout() {
        session_destroy();
    }
}