<?php

session_start();

require_once '../app/config/database.php';
require_once '../app/models/User.php';

class AuthController
{
    public function login($username, $password, $role)
    {
        $db = (new Database())->connect();

        $userModel = new User($db);

        $user = $userModel->findByUsername($username);

        if (!$user) {
            return false;
        }

        if ($user['status'] != 'Active') {
            return false;
        }

        if ($user['role'] !== $role) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return true;
        }

        return false;
    }
}