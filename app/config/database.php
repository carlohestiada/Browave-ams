<?php

date_default_timezone_set('Asia/Manila');

class Database
{
    private $host = "localhost";
    private $dbname = "browave_ams";
    private $username = "browave_user";
    private $password = "alwaysBrowave123";

    public function connect()
    {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->username,
                $this->password
            );
            $pdo->exec("SET time_zone = '+08:00'");

            return $pdo;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}
