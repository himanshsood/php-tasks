<?php
include_once(__DIR__ . '/config.php');
require 'vendor/autoload.php'; // Load Composer's autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


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

    if (empty($email)) {
        http_response_code(400);
        $response['error'] = 'Email is required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        $response['error'] = 'Invalid email format.';
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

    // Generate OTP
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $_SESSION['forgot_password_otp'] = $otp;
    $_SESSION['otp_email'] = $email;
 

    // Send email using PHPMailer
    
    try {
        $mail = new PHPMailer(true);
        
        // Enable SMTP debugging (set to 0 to disable)
        $mail->SMTPDebug = 0; // Change to 2 for detailed debugging
        $mail->Debugoutput = 'error_log';
        
        // Server settings
        $mail->isSMTP(); 
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'himanshsood311@gmail.com';
        $mail->Password = 'zkrj euwx dfnl tdwy'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;                  // TCP port to connect to

        // Recipients
        $mail->setFrom('harshita@orientaloutsourcing.com', 'Mailer');
        $mail->addAddress($email);                 // Add a recipient

        // Content
        $mail->isHTML(true);                      // Set email format to HTML
        $mail->Subject = 'Your OTP for Password Reset';
        $mail->Body    = 'Your OTP for password reset is: ' . $otp;

        $mail->send();

        http_response_code(200);
        $response['message'] = 'OTP has been sent to your email address.';
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        echo json_encode($response);
    }
} else {
    http_response_code(405);
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
?>