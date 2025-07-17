<?php

require_once 'config.php';
require 'vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

// CSRF Token validation
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please reload the page and try again.']);
    exit;
}

$response = array();

try {
    // Get and sanitize input data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Server-side validation
    $errors = validateFormData($firstName, $lastName, $email, $password, $confirmPassword);

    if (!empty($errors)) {
        $response['success'] = false;
        $response['message'] = 'Please fix the errors and try again';
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Check if email already exists
    $checkEmailStmt = $conn->prepare("SELECT id, is_email_verified FROM users_info WHERE email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $result = $checkEmailStmt->get_result();

    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();

        if ($existingUser['is_email_verified'] == 1) {
            $response['success'] = false;
            $response['message'] = 'Email already registered and verified. Please login or use a different email address.';
            echo json_encode($response);
            exit;
        } else {
            // Delete unverified account
            $deleteStmt = $conn->prepare("DELETE FROM users_info WHERE id = ?");
            $deleteStmt->bind_param("i", $existingUser['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
        }
    }
    $checkEmailStmt->close();

    // Hash password and insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare("INSERT INTO users_info (first_name, last_name, email, password, is_email_verified, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $insertStmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

    if ($insertStmt->execute()) {
        $userId = $conn->insert_id;
        $otp = sprintf("%06d", mt_rand(100000, 999999));

        // Save session data
        $_SESSION['temp_user_id'] = $userId;
        $_SESSION['temp_otp'] = $otp;
        $_SESSION['temp_email'] = $email;
        $_SESSION['temp_name'] = $firstName . ' ' . $lastName;
        $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

        // Send OTP email
        $emailResult = sendOTPEmail($email, $otp);

        if ($emailResult['sent']) {
            $response['success'] = true;
            $response['message'] = 'Registration successful! Please check your email for the OTP verification code.';
            $response['redirect'] = 'verify_otp.php';
        } else {
            $response['success'] = false;
            $response['message'] = 'Error: We could not send the OTP email. Please try again later.';
        }
    } else {
        throw new Exception('Error inserting user data');
    }

    $insertStmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Registration failed. Please try again.';
    error_log("Registration Error: " . $e->getMessage());
}

echo json_encode($response);

function validateFormData($firstName, $lastName, $email, $password, $confirmPassword)
{
    $errors = array();

    // Validate First Name
    if (empty($firstName)) {
        $errors['firstName'] = 'First name is required';
    } elseif (strlen($firstName) < 2) {
        $errors['firstName'] = 'First name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
        $errors['firstName'] = 'First name can only contain letters and spaces';
    }

    // Validate Last Name
    if (empty($lastName)) {
        $errors['lastName'] = 'Last name is required';
    } elseif (strlen($lastName) < 2) {
        $errors['lastName'] = 'Last name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
        $errors['lastName'] = 'Last name can only contain letters and spaces';
    }

    // Validate Email
    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 255) {
        $errors['email'] = 'Email address is too long';
    }

    // Validate Password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must include at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must include at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must include at least one number';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $errors['password'] = 'Password must include at least one special character';
    }

    // Validate Confirm Password
    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = 'Please confirm your password';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    return $errors;
}

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
        $result['error'] = 'PHPMailer not found';
        return $result;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'himanshsood311@gmail.com';
        $mail->Password = 'zkrj euwx dfnl tdwy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

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
        $mail->Subject = 'Welcome! Please verify your email address';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welcome - Email Verification</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f4f4f4; padding: 20px; border-radius: 10px;">
                <h2 style="color: #2c3e50; text-align: center;">Welcome to Oriental Outsourcing!</h2>
                <p>Thank you for registering with us. To complete your registration, please verify your email address using the OTP code below:</p>
                <div style="background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;">
                    <h1 style="margin: 0; font-size: 32px; letter-spacing: 5px;">' . $otp . '</h1>
                </div>
                <p><strong>Important:</strong> This OTP is valid for 5 minutes only.</p>
                <p>If you did not create this account, please ignore this email.</p>
                <hr style="border: 1px solid #eee; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Welcome to Oriental Outsourcing!\n\nYour email verification OTP is: $otp\n\nThis OTP is valid for 5 minutes only.\n\nIf you did not create this account, please ignore this email.";

        $mail->send();
        $result['sent'] = true;

    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        error_log("Email Error: " . $e->getMessage());
    }

    return $result;
}
