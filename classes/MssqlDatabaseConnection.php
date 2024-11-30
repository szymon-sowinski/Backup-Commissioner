<?php
require_once 'DatabaseConnection.php';

class MssqlDatabaseConnection extends DatabaseConnection {

    public function __construct($server, $username, $password, $database) {
        parent::__construct($server, $username, $password, $database);
        $this->conn = ADONewConnection('mssqlnative');
    }

    public function connect() {
        if (!$this->conn) {
            return ["error" => "Nie udało się utworzyć obiektu połączenia."];
        }

        $connected = $this->conn->Connect($this->server, $this->username, $this->password, $this->database);
        if ($connected) {
            return true;
        } else {
            return ["error" => "Połączenie nie powiodło się: " . $this->conn->ErrorMsg()];
        }
    }
}

?>
