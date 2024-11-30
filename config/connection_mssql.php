<?php
include('../adodb5/adodb.inc.php');
header('Content-Type: application/json');

require_once '../classes/SessionManager.php';
require_once '../classes/MssqlDatabaseConnection.php';

class DatabaseInfo {
    private $dbConnection;

    public function __construct($server = 'PC016\SQLEXPRESS') {
        $sessionManager = SessionManager::getInstance();

        $user = $sessionManager->get('user');
        $password = $sessionManager->get('password');

        $this->dbConnection = new MssqlDatabaseConnection(
            $server, 
            $user, 
            $password, 
            'master'
        );
    }

    public function connect() {
        return $this->dbConnection->connect();
    }

    public function getDatabasesInfo() {
        $sql1 = "SELECT name FROM sys.databases WHERE state_desc = 'ONLINE' AND name NOT IN ('master', 'tempdb', 'model', 'msdb')";
        $sql2 = "SELECT d.name, SUM(mf.size * 8 / 1024) AS size_mb 
                  FROM sys.databases d 
                  LEFT JOIN sys.master_files mf ON d.database_id = mf.database_id 
                  WHERE d.state_desc = 'ONLINE' AND d.name NOT IN ('master', 'tempdb', 'model', 'msdb')
                  GROUP BY d.name";

        $rs1 = $this->dbConnection->executeQuery($sql1);
        $rs2 = $this->dbConnection->executeQuery($sql2);

        if ($rs1 && $rs2) {
            $sizes = [];
            while (!$rs2->EOF) {
                $name = $rs2->fields['name'] ?? $rs2->fields[0];
                $size_mb = $rs2->fields['size_mb'] ?? $rs2->fields[1];
                $sizes[$name] = $size_mb;
                $rs2->MoveNext();
            }

            $databases = [];
            $lp = 1;

            while (!$rs1->EOF) {
                $nazwa = $rs1->fields['name'] ?? $rs1->fields[0];
                $rozmiar = $sizes[$nazwa] ?? "N/A";
                $databases[] = [
                    "Lp" => $lp,
                    "Nazwa" => $nazwa,
                    "Rozmiar" => $rozmiar . " MB",
                    "bool" => false
                ];
                $lp++;
                $rs1->MoveNext();
            }

            return [
                "databases" => $databases,
                "instance" => $this->dbConnection->getServer(),
            ];
        } else {
            return ["error" => "Błąd zapytania: " . $this->dbConnection->conn->ErrorMsg()];
        }
    }
}

$databaseInfo = new DatabaseInfo(isset($_POST['server']) ? $_POST['server'] : 'PC016\SQLEXPRESS');
$connectionResult = $databaseInfo->connect();

if ($connectionResult === true) {
    $databasesInfo = $databaseInfo->getDatabasesInfo();
    echo json_encode($databasesInfo);
} else {
    echo json_encode($connectionResult);
}