<?php
require_once '../classes/SessionManager.php';

header('Content-Type: application/json');

class LdapAuthenticator {
    private $ldap_host;
    private $ldap_port;
    private $login;
    private $password;
    private $ldap_conn;
    private $sessionManager;
    public function __construct($ldap_host = "localhost", $ldap_port = 10389) {
        $this->ldap_host = $ldap_host;
        $this->ldap_port = $ldap_port;
        $this->sessionManager = SessionManager::getInstance();
    }

    public function authenticate() {
        $this->login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS);
        $this->password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($this->login) || empty($this->password)) {
            echo json_encode(['success' => false, 'message' => 'Brak danych logowania.']);
            return;
        }

        $ldap_user = "uid=$this->login,ou=users,dc=example,dc=com";

        $this->ldap_conn = ldap_connect($this->ldap_host, $this->ldap_port);
        if ($this->ldap_conn) {
            ldap_set_option($this->ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, 3);

            if (@ldap_bind($this->ldap_conn, $ldap_user, $this->password)) {
                $this->sessionManager->set('user', $this->login);
                $this->sessionManager->set('password', $this->password);

                echo json_encode(['success' => true, 'message' => 'Uwierzytelnienie udane!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Błędne dane logowania.']);
            }

            ldap_unbind($this->ldap_conn);
        } else {
            echo json_encode(['success' => false, 'message' => 'Błąd połączenia z serwerem LDAP.']);
        }
    }
}

$ldapAuthenticator = new LdapAuthenticator();
$ldapAuthenticator->authenticate();
