<?php

// Prevent any output before headers
ob_start();

// Error handling - catch any PHP errors and convert to JSON
function handleError($errno, $errstr, $errfile, $errline)
{
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred. Please try again.',
        'debug' => "Error: $errstr in $errfile on line $errline"
    ]);
    exit;
}

function handleException($exception)
{
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred. Please try again.',
        'debug' => $exception->getMessage()
    ]);
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

require_once 'config.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

header('Content-Type: application/json');

// CSRF Token validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    ob_clean();
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token. Please reload the page and try again.'
    ]);
    exit;
}

$response = array();

try {
    // Check if user has valid session data
    if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
        $response['success'] = false;
        $response['message'] = 'Session expired. Please register again.';
        $response['redirect'] = 'register.php';
        ob_clean();
        echo json_encode($response);
        exit;
    }

    // Rate limiting - check if user is trying to resend too frequently
    if (isset($_SESSION['last_resend_time']) && (time() - $_SESSION['last_resend_time']) < 60) {
        $response['success'] = false;
        $response['message'] = 'Please wait at least 60 seconds before requesting another OTP.';
        ob_clean();
        echo json_encode($response);
        exit;
    }

    // Generate new OTP
    $newOTP = sprintf("%06d", mt_rand(100000, 999999));

    // Update session with new OTP and expiry
    $_SESSION['temp_otp'] = $newOTP;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes from now
    $_SESSION['last_resend_time'] = time();

    // Send new OTP email
    $emailResult = sendOTPEmail($_SESSION['temp_email'], $newOTP);

    if ($emailResult['sent']) {
        $response['success'] = true;
        $response['message'] = 'New OTP has been sent to your email address.';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to send OTP. Your new OTP is: ' . $newOTP . ' (Email error: ' . $emailResult['error'] . ')';
        $response['debug'] = $emailResult['error'];
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Failed to resend OTP. Please try again.';
    $response['debug'] = $e->getMessage();
    error_log("Resend OTP Error: " . $e->getMessage());
}

ob_clean();
echo json_encode($response);

function sendOTPEmail($email, $otp)
{
    $result = ['sent' => false, 'error' => ''];

    // Try different autoload paths
    $autoloadPaths = [
        'vendor/autoload.php',
    ];

    $autoloadFound = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $autoloadFound = true;
            break;
        }
    }

    if (!$autoloadFound) {
        $result['error'] = 'PHPMailer not found. Please run: composer require phpmailer/phpmailer';
        return $result;
    }

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
        $mail->Port = 587;

        // Additional SMTP settings for Gmail
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('himanshsood311@gmail.com', 'Oriental Outsourcing');
        $mail->addAddress($email);
        $mail->addReplyTo('himanshsood311@gmail.com', 'Oriental Outsourcing');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Your New OTP Code';

        // Enhanced HTML email template
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Verification - Resend OTP</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f4f4f4; padding: 20px; border-radius: 10px;">
                <h2 style="color: #2c3e50; text-align: center;">Email Verification - New OTP</h2>
                <p>Hello,</p>
                <p>You requested a new OTP for email verification. Please use the following code:</p>
                <div style="background: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;">
                    <h1 style="margin: 0; font-size: 32px; letter-spacing: 5px;">' . $otp . '</h1>
                </div>
                <p><strong>Important:</strong> This OTP is valid for 5 minutes only.</p>
                <p>If you did not request this OTP, please ignore this email.</p>
                <hr style="border: 1px solid #eee; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>';

        // Plain text version
        $mail->AltBody = "Your new OTP for email verification is: $otp\n\nThis OTP is valid for 5 minutes only.\n\nIf you did not request this OTP, please ignore this email.";

        $mail->send();
        $result['sent'] = true;

    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        error_log("Email Error: " . $e->getMessage());
    }

    return $result;
}
