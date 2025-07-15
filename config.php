<?php

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_project_db";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

define('SITE_URL', 'http://localhost/my_page');
define('RESOURCES_URL', SITE_URL . '/resources');
