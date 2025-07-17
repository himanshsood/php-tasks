<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Log the logout action
if (isset($_SESSION['admin_email'])) {
    error_log("Admin logout: " . $_SESSION['admin_email'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
}

// Destroy all session data
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Redirect to login page
header("Location: login.php");
exit;
?>