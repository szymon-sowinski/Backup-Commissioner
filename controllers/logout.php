<?php
require_once '../classes/SessionManager.php';

$sessionManager = SessionManager::getInstance();
$sessionManager->destroy();

header("Location: ../views/login.html");
exit();