<?php

// Add this at the very beginning

include_once(__DIR__ . '/config.php');
include_once(__DIR__ . '/config.php');

header('Content-Type: application/json');
$response = [];
$http_status_code = 200; // Default HTTP status code

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $http_status_code = 400;
        $response['error'] = 'CSRF token missing.';
        $response['debug'] = [
            'post_csrf' => isset($_POST['csrf_token']) ? $_POST['csrf_token'] : 'missing',
            'session_csrf' => isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'missing'
        ];
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $http_status_code = 400;
        $response['error'] = 'Invalid CSRF token.';
        $response['debug'] = [
            'post_csrf' => $_POST['csrf_token'],
            'session_csrf' => $_SESSION['csrf_token']
        ];
    } else {
        // Rest of your login logic...else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($email) || empty($password)) {
            $http_status_code = 400;
            $response['error'] = 'Both email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $http_status_code = 400;
            $response['error'] = 'Invalid email format.';
        } else {
            // Validate user credentials (example using a simple database check)
            $qry = "SELECT * FROM users_info WHERE email = '".$conn->real_escape_string($email)."' and is_email_verified=1";
            $result = $conn->query($qry);

            if ($result->num_rows === 0) {
                $http_status_code = 400;
                $response['error'] = 'No user found with this email address.';
            } else {
                $user = $result->fetch_assoc();

                // Verify password (assuming passwords are hashed in the database)
                if (!password_verify($password, $user['password'])) {
                    $http_status_code = 400;
                    $response['error'] = 'Incorrect password.';
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];

                    $http_status_code = 200;
                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirectUrl'] = 'profile.php'; // Redirect to a protected page after login
                }
            }
        }
    }
} else {
    $http_status_code = 405;
    $response['error'] = 'Invalid request method.';
}

http_response_code($http_status_code);
echo json_encode($response);
