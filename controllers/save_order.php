<?php
require_once '../classes/SessionManager.php';
require_once '../utils/EncryptionUtility.php';
require_once '../adodb5/adodb.inc.php'; 

use LS\Helpers\EncryptionUtility;
require_once '../classes/MysqlDatabaseConnection.php';

class BackupOrderManager {
    private $dbConnection;
    private $loggedInUser;

    public function __construct($host = 'localhost', $username = 'root', $password = '', $database = 'backup_zlecenia') {
        $sessionManager = SessionManager::getInstance();

        $this->loggedInUser = $sessionManager->get('user') ?? 'N/A';

        $this->dbConnection = new MysqlDatabaseConnection($host, $username, $password, $database);

        $connectionResult = $this->dbConnection->connect();
        if ($connectionResult !== true) {
            die("Błąd połączenia: " . $connectionResult['error']);
        }
    }

    public function getInstances() {
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $sql = "SELECT id, nazwa_instancji FROM instancje";
            $result = $this->dbConnection->executeQuery($sql);

            if ($result === false) {
                die("Błąd w zapytaniu: " . $this->dbConnection->getErrorMsg());
            }

            $instances = [];
            while (!$result->EOF) {
                $instances[] = [
                    "id" => $result->fields['id'],
                    "nazwa_instancji" => $result->fields['nazwa_instancji']
                ];
                $result->MoveNext();
            }

            echo json_encode(["instancje" => $instances]);
        }
    }

    public function createOrder() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nazwy_baz = $_POST['nazwy_baz'];
            $instancja_sql = $_POST['instancja_sql'];
            $data_zlec = date('Y-m-d H:i:s');
            $email = $_POST['email'];
            $plainPassword = $_POST['password'];

            $hashedPassword = EncryptionUtility::EncryptSHA256($plainPassword);

            if (is_array($nazwy_baz)) {
                $nazwy_baz = implode(',', $nazwy_baz);
            }

            $zrealizowane = 0;

            $sql = "INSERT INTO zlecenia (data_zlec, nazwy_baz, instancja_sql, zrealizowane, email_form, haslo_zip, zalogowany_uzytkownik)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $data_zlec,
                $nazwy_baz,
                $instancja_sql,
                $zrealizowane,
                $email,
                $hashedPassword,
                $this->loggedInUser
            ];

            $result = $this->dbConnection->executeQuery($sql, $params);

            if ($result === false) {
                echo json_encode(["success" => false, "message" => "Błąd: " . $this->dbConnection->getErrorMsg()]);
            } else {
                echo json_encode(["success" => true, "message" => "Zlecenie zostało dodane pomyślnie."]);
            }
        }
    }

    public function __destruct() {
        $this->dbConnection->closeConnection();
    }
}

$orderManager = new BackupOrderManager();
$orderManager->getInstances();
$orderManager->createOrder();
