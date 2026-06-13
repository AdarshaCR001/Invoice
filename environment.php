<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');

#Load Environments
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once('helpers.php');

// Security: Check for App Password
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app_password = isset($_ENV['APP_PASSWORD']) ? $_ENV['APP_PASSWORD'] : '';

// Enforce authentication for all pages except login
// Using SCRIPT_NAME avoids path info vulnerabilities
$current_script = basename($_SERVER['SCRIPT_NAME']);
if ($current_script !== 'login.php') {
    // Fail closed: If no password is set, deny access entirely.
    if ($app_password === '') {
        die("Security Error: APP_PASSWORD is not configured in the environment.");
    }

    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header("Location: login.php");
        exit;
    }
}
?>