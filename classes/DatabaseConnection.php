<?php
abstract class DatabaseConnection {
    protected $conn;
    protected $server;
    protected $username;
    protected $password;
    protected $database;

    public function __construct($server, $username, $password, $database) {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    abstract public function connect();

    public function executeQuery($sql, $params = []) {
        if (!$this->conn) {
            return ["error" => "Brak połączenia z bazą danych."];
        }

        $rs = $this->conn->Execute($sql, $params);
        if ($rs === false) {
            return ["error" => "Błąd zapytania: " . $this->conn->ErrorMsg()];
        }

        return $rs;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->Close();
        }
    }

    public function getServer() {
        return $this->server;
    }
}
?>
