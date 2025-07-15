<?php
include_once(__DIR__ . '/config.php');

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
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if (empty($email)) {
        http_response_code(400);
        $response['error'] = 'Email is required.';
        echo json_encode($response);
        exit;
    }

    if (empty($otp)) {
        http_response_code(400);
        $response['error'] = 'OTP is required.';
        echo json_encode($response);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        $response['error'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

       // Check if OTP is set in session
       if (!isset($_SESSION['forgot_password_otp']) || !isset($_SESSION['otp_email'])) {
        http_response_code(400);
        $response['error'] = 'OTP not set or session expired.';
        echo json_encode($response);
        exit;
    }

    // Check if email matches the session email
    if ($_SESSION['otp_email'] !== $email) {
        http_response_code(400);
        $response['error'] = 'Email not found or session expired.';
        echo json_encode($response);
        exit;
    }

    // Check if OTP matches
    if ($_SESSION['forgot_password_otp'] != $otp) {
        http_response_code(400);
        $response['error'] = 'Invalid OTP.';
        echo json_encode($response);
        exit;
    }

    // OTP is valid
    http_response_code(200);
    $response['success'] = true;
    $response['message'] = 'OTP verified successfully. Please proceed to reset your password.';
    echo json_encode($response);

    // Optionally, clear OTP data from session
    unset($_SESSION['forgot_password_otp']);
    unset($_SESSION['otp_email']);
 

} else {
    http_response_code(405);
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
