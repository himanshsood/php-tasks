<?php
include_once(__DIR__ . '/config.php');

// Load Composer's autoloader
require 'vendor/autoload.php';


header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(400);
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        http_response_code(400);
        $response['error'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        $response['error'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        http_response_code(400);
        $response['error'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    // Check if email exists in the database
    $qry = "SELECT * FROM users_info WHERE email = '".$conn->real_escape_string($email)."'";
    $result = $conn->query($qry);

    if ($result->num_rows === 0) {
        http_response_code(400);
        $response['error'] = 'Email not found.';
        echo json_encode($response);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Update the password in the database
    $qry = "UPDATE users_info SET password = '".$conn->real_escape_string($hashed_password)."' WHERE email = '".$conn->real_escape_string($email)."'";
    if ($conn->query($qry)) {
        http_response_code(200);
        $response['message'] = 'Password updated successfully.';
        $response['success'] = true;
        echo json_encode($response);
    } else {
        http_response_code(500);
        $response['error'] = 'There was an error updating the password. Please try again later.';
        echo json_encode($response);
    }
} else {
    http_response_code(405);
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
