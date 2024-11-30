<?php
require_once '../classes/SessionManager.php';

sessionManager::getInstance();

if (!isset($_SESSION['user'])) {
    header("Location: ../views/login.html");
    exit();
}

header("Location: ../views/main_view.html"); 