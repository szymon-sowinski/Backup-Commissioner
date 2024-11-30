<?php
require_once 'DatabaseConnection.php';

class MysqlDatabaseConnection extends DatabaseConnection {

    public function __construct($servername, $username, $password, $database) {
        parent::__construct($servername, $username, $password, $database);
        $this->conn = NewADOConnection('mysqli');
    }

    public function connect() {
        if (!$this->conn) {
            return ["error" => "Nie udało się utworzyć połączenia z bazą danych."];
        }

        $connected = $this->conn->Connect($this->server, $this->username, $this->password, $this->database);
        if ($connected) {
            return true;
        } else {
            return ["error" => "Połączenie z bazą danych nie powiodło się: " . $this->conn->ErrorMsg()];
        }
    }
}
?>