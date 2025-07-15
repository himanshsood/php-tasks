<?php
// Include configuration file
include_once(__DIR__ . "/config.php");

// Destroy all session data
$_SESSION = array();

// Delete the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();



// Close database connection
if ($conn) {
    $conn->close();
}

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>