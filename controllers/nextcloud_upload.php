<?php
putenv("NODE_OPTIONS=--no-deprecation");
require_once '../adodb5/adodb.inc.php';

class BackupProcessor {
    private $db;
    private $filePath;
    private $orderId;
    private $username;
    private $password;
    private $email;
    private $shareLink;
    private $expireDate;

    public function __construct($filePath, $orderId, $username, $password) {
        $this->filePath = $filePath;
        $this->orderId = $orderId;
        $this->username = $username;
        $this->password = $password;

        $this->db = ADONewConnection('mysqli');
        $this->db->Connect('localhost', 'root', '', 'backup_zlecenia');
        if (!$this->db->IsConnected()) {
            throw new Exception("\033[31mBłąd połączenia z bazą danych: " . $this->db->ErrorMsg() . "\033[0m\n");
        }
    }

    public function process() {
        $this->checkFile();
        $this->getOrderData();
        $this->uploadToNextcloud();
        $this->generateShareLink();
        $this->sendEmail();
        $this->updateOrderStatus();
    }

    private function checkFile() {
        if (!file_exists($this->filePath)) {
            throw new Exception("\033[31mPlik o podanej ścieżce nie istnieje.\033[0m\n");
        }

        $fileExtension = pathinfo($this->filePath, PATHINFO_EXTENSION);
        if (strtolower($fileExtension) !== 'zip') {
            throw new Exception("\033[31mTylko pliki z rozszerzeniem .ZIP mogą być przesyłane.\033[0m\n");
        }
    }

    private function getOrderData() {
        $sql = "SELECT zrealizowane, EMAIL_FORM FROM zlecenia WHERE id = ?";
        $result = $this->db->Execute($sql, array($this->orderId));

        if (!$result || $result->RecordCount() == 0) {
            throw new Exception("\033[31mNie znaleziono zlecenia o podanym ID.\033[0m\n");
        }

        $row = $result->FetchRow();

        if ($row['zrealizowane'] == 1) {
            throw new Exception("\033[31mZlecenie o ID {$this->orderId} zostało już zrealizowane.\033[0m\n");
        }

        $this->email = $row['EMAIL_FORM'];
    }

    private function uploadToNextcloud() {
        $nextcloudUrlBefore = 'https://own.raton24.pl/remote.php/dav/files/';
        $filename = basename($this->filePath);
        $localFile = $this->filePath;

        $chAuth = curl_init($nextcloudUrlBefore . $this->username . "/");
        curl_setopt($chAuth, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chAuth, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($chAuth, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($chAuth, CURLOPT_SSL_VERIFYHOST, 2);
        curl_exec($chAuth);
        $httpCode = curl_getinfo($chAuth, CURLINFO_HTTP_CODE);
        curl_close($chAuth);

        if ($httpCode !== 200) {
            throw new Exception("\033[31mBłąd logowania: Niepoprawny login lub hasło.\033[0m\n");
        }

        $nextcloudUrl = $nextcloudUrlBefore . $this->username . "/Backupy/";
        $ch = curl_init($nextcloudUrl . $filename);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_INFILE, fopen($localFile, 'r'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFile));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("\033[31mError: " . curl_error($ch) . "\033[0m\n");
        }

        echo "\033[32mPlik został przesłany do Nextcloud.\033[0m\n";
        curl_close($ch);
    }

    private function generateShareLink() {
        $shareUrl = 'https://own.raton24.pl/ocs/v1.php/apps/files_sharing/api/v1/shares';
        $postData = array(
            'path' => '/Backupy/' . basename($this->filePath),
            'shareType' => 3,
            'password' => '',
            'permissions' => 1
        );
    
        $chShare = curl_init();
        curl_setopt($chShare, CURLOPT_URL, $shareUrl);
        curl_setopt($chShare, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chShare, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($chShare, CURLOPT_POST, true);
        curl_setopt($chShare, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($chShare, CURLOPT_HTTPHEADER, array('OCS-APIREQUEST: true'));
        curl_setopt($chShare, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($chShare, CURLOPT_SSL_VERIFYHOST, 2);
    
        $responseShare = curl_exec($chShare);
        if (curl_errno($chShare)) {
            throw new Exception("\033[31mError generating share link: " . curl_error($chShare) . "\033[0m\n");
        }
    
        $data = simplexml_load_string($responseShare);
        if ($data && $data->meta->status == 'ok' && isset($data->data->url)) {
            $this->shareLink = (string) $data->data->url;
            $expire = (string) $data->data->expiration;
            $this->expireDate = $expire ? (new DateTime($expire))->format('d-m-Y H:i:s') : 'Brak daty ważności';

            echo "\033[32mLink do udostępnienia pliku:\033[0m\n";
            echo "\033[32m{$this->shareLink}\033[0m\n";
            echo "Link traci ważność: \033[32m{$this->expireDate}\033[0m\n";
        } else {
            throw new Exception("\033[31mNie udało się wygenerować linku.\033[0m\n");
        }
    
        curl_close($chShare);
    }
    
    private function sendEmail() {
        $subject = "Backup bazy danych - ID zlecenia: {$this->orderId}";
        $templatePath = '../templates/email_template.mjml';
    
        if (!file_exists($templatePath)) {
            throw new Exception("\033[31mSzablon wiadomości e-mail nie został znaleziony.\033[0m\n");
        }

        $mjmlTemplate = file_get_contents($templatePath);
    
        $mjmlTemplate = str_replace(
            ['{{link}}', '{{expire_date}}', '{{order_id}}', '{{file_name}}'],
            [$this->shareLink, $this->expireDate, $this->orderId, basename($this->filePath)],
            $mjmlTemplate
        );
    
        $tempFilePath = tempnam(sys_get_temp_dir(), 'email_template_') . '.mjml';
        file_put_contents($tempFilePath, $mjmlTemplate);
    
        $htmlContent = shell_exec("mjml $tempFilePath");
    
        unlink($tempFilePath);
    
        if ($htmlContent === null) {
            throw new Exception("\033[31mBłąd podczas konwersji MJML do HTML.\033[0m\n");
        }
    
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: lagarta@localhost\r\n";
    
        if (mail($this->email, $subject, $htmlContent, $headers)) {
            echo "\033[32mWiadomość e-mail została wysłana na adres: {$this->email}\033[0m\n";
        } else {
            throw new Exception("\033[31mBłąd podczas wysyłania e-maila.\033[0m\n");
        }
    }

    private function updateOrderStatus() {
        $sqlUpdate = "UPDATE zlecenia SET zrealizowane = 1 WHERE id = ?";
        if ($this->db->Execute($sqlUpdate, array($this->orderId))) {
            echo "\033[32mStatus zlecenia w tabeli ze zleceniami został zaktualizowany na '1' (zrealizowano).\033[0m\n";
        } else {
            throw new Exception("\033[31mWystąpił błąd podczas aktualizacji statusu zlecenia.\033[0m\n");
        }
    }

    public function __destruct() {
        $this->db->Close();
    }
}

function getUserInput($prompt) {
    echo $prompt . ": ";
    return trim(fgets(STDIN));
}

$filePath = $argv[1] ?? getUserInput("Podaj ścieżkę do pliku");
$orderId = $argv[2] ?? getUserInput("Podaj numer zlecenia");
$username = $argv[3] ?? getUserInput("Podaj login Nextcloud");
$password = $argv[4] ?? getUserInput("Podaj hasło Nextcloud");

try {
    $backupProcessor = new BackupProcessor($filePath, $orderId, $username, $password);
    $backupProcessor->process();
} catch (Exception $e) {
    echo $e->getMessage();
}