<?php

// Add this at the very beginning

include_once(__DIR__ . '/config.php');
require 'vendor/autoload.php'; // Load Composer's autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = [];
$http_status_code = 200; // Default HTTP status code

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        // Check if this is OTP verification step
        if (isset($_POST['otp_verification']) && $_POST['otp_verification'] === 'true') {
            // OTP Verification Logic
            $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
            
            if (empty($otp)) {
                $http_status_code = 400;
                $response['error'] = 'OTP is required.';
            } elseif (!isset($_SESSION['login_otp']) || !isset($_SESSION['login_otp_email']) || !isset($_SESSION['login_user_id'])) {
                $http_status_code = 400;
                $response['error'] = 'OTP session expired. Please login again.';
            } elseif (strval($_SESSION['login_otp']) !== $otp) {
                $http_status_code = 400;
                $response['error'] = 'Invalid OTP. Please try again.';
                // Debug information (remove in production)
                $response['debug'] = [
                    'session_otp' => $_SESSION['login_otp'],
                    'provided_otp' => $otp,
                    'session_type' => gettype($_SESSION['login_otp']),
                    'provided_type' => gettype($otp)
                ];
            } else {
                // OTP is valid, complete the login
                $_SESSION['user_id'] = $_SESSION['login_user_id'];
                
                // Clear OTP session variables
                unset($_SESSION['login_otp']);
                unset($_SESSION['login_otp_email']);
                unset($_SESSION['login_user_id']);
                
                $http_status_code = 200;
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                $response['redirectUrl'] = 'profile.php';
            }
        } else {
            // Initial login credential verification
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            if (empty($email) || empty($password)) {
                $http_status_code = 400;
                $response['error'] = 'Both email and password are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $http_status_code = 400;
                $response['error'] = 'Invalid email format.';
            } else {
                // Validate user credentials
                $qry = "SELECT * FROM users_info WHERE email = '".$conn->real_escape_string($email)."' and is_email_verified=1";
                $result = $conn->query($qry);

                if ($result->num_rows === 0) {
                    $http_status_code = 400;
                    $response['error'] = 'No user found with this email address.';
                } else {
                    $user = $result->fetch_assoc();

                    // Verify password
                    if (!password_verify($password, $user['password'])) {
                        $http_status_code = 400;
                        $response['error'] = 'Incorrect email or password.';
                    } else {
                        // Credentials are valid, now send OTP for 2FA
                        $otp = rand(100000, 999999);
                        
                        // Store OTP and user info in session for verification
                        $_SESSION['login_otp'] = $otp;
                        $_SESSION['login_otp_email'] = $email;
                        $_SESSION['login_user_id'] = $user['id'];
                        
                        // Send OTP email
                        try {
                            $mail = new PHPMailer(true);
                            
                            // Enable SMTP debugging (set to 0 to disable)
                            $mail->SMTPDebug = 0;
                            $mail->Debugoutput = 'error_log';
                            
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'himanshsood311@gmail.com';
                            $mail->Password = 'zkrj euwx dfnl tdwy'; // App password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            // Recipients
                            $mail->setFrom('harshita@orientaloutsourcing.com', 'Oriental Outsourcing');
                            $mail->addAddress($email);

                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Your Login OTP - Oriental Outsourcing';
                            $mail->Body = '
                                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                                    <h2 style="color: #333;">Login Verification</h2>
                                    <p>Your OTP for login verification is:</p>
                                    <div style="background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0;">
                                        ' . $otp . '
                                    </div>
                                    <p style="color: #666;">This OTP is valid for 10 minutes.</p>
                                    <p style="color: #666;">If you did not request this login, please ignore this email.</p>
                                </div>
                            ';

                            $mail->send();
                            
                            $http_status_code = 200;
                            $response['success'] = true;
                            $response['require_otp'] = true;
                            $response['message'] = 'OTP has been sent to your email address. Please check your email and enter the OTP to complete login.';
                            
                        } catch (Exception $e) {
                            $http_status_code = 500;
                            $response['error'] = 'Unable to send OTP email. Please try again later.';
                            error_log('Mail Error: ' . $mail->ErrorInfo);
                        }
                    }
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
?>