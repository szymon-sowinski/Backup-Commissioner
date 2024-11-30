<?php
require_once '../classes/SessionManager.php';
require_once '../adodb5/adodb.inc.php';
require_once '../classes/MysqlDatabaseConnection.php';

class BackupInstances {
    private $dbConnection;
    private $user;

    public function __construct($servername = "localhost", $username = "root", $password = "", $dbname = "backup_zlecenia") {
        $sessionManager = SessionManager::getInstance();

        $this->user = filter_var($sessionManager->get('user'), FILTER_SANITIZE_SPECIAL_CHARS);
        
        if (empty($this->user)) {
            die("Błąd: Brak użytkownika w sesji.");
        }

        $this->dbConnection = new MysqlDatabaseConnection($servername, $username, $password, $dbname);
        
        $connectionResult = $this->dbConnection->connect();
        if ($connectionResult !== true) {
            die("Błąd połączenia: " . $connectionResult['error']);
        }
    }

    public function getInstances() {
        $sql = "SELECT i.nazwa_instancji
                FROM instancje_uzytkownika iu
                JOIN instancje i ON iu.mssql_instance = i.nazwa_instancji
                WHERE iu.ldap_user = ?";

        $rs = $this->dbConnection->executeQuery($sql, array($this->user));
        
        if ($rs === false) {
            die("Błąd zapytania: " . $this->dbConnection->getErrorMsg());
        }

        $instances = [];
        while (!$rs->EOF) {
            $instances[] = $rs->fields['nazwa_instancji'];
            $rs->MoveNext();
        }

        $rs->Close();
        
        return $instances;
    }

    public function __destruct() {
        $this->dbConnection->closeConnection();
    }
}

$backupInstances = new BackupInstances();
$instances = $backupInstances->getInstances();

header('Content-Type: application/json');
echo json_encode($instances);