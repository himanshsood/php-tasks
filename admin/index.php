<?php
require_once '../config.php';

// Check login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: customers.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>