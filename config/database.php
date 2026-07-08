<?php

require_once __DIR__ . "/constants.php";

class Database
{
    private $host = DB_HOST;
    private $dbname = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;

    public $conn;

    public function connect()
    {
        $this->conn = null;

        try {

            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->dbname
            );

            if ($this->conn->connect_error) {
                die("Database Connection Failed : " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8");

        } catch (Exception $e) {

            die("Connection Error : " . $e->getMessage());

        }

        return $this->conn;
    }
}