<?php
// Prevent any output before headers
ob_start();

require_once 'config.php';

// Error handling - catch any PHP errors and convert to JSON
function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred. Please try again.',
        'debug' => "Error: $errstr in $errfile on line $errline"
    ]);
    exit;
}

function handleException($exception) {
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
    if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_otp']) || !isset($_SESSION['temp_email'])) {
        $response['success'] = false;
        $response['message'] = 'Session expired. Please register again.';
        $response['redirect'] = 'register.php';
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    // Check if OTP has expired
    if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
        // Clear expired session data
        unset($_SESSION['temp_user_id'], $_SESSION['temp_otp'], $_SESSION['temp_email'], $_SESSION['temp_name'], $_SESSION['otp_expiry']);
        $response['success'] = false;
        $response['message'] = 'OTP has expired. Please register again.';
        $response['redirect'] = 'register.php';
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    // Get and validate OTP
    $submittedOTP = trim($_POST['otp'] ?? '');
    
    if (empty($submittedOTP)) {
        $response['success'] = false;
        $response['message'] = 'Please enter the OTP code.';
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    if (strlen($submittedOTP) !== 6 || !ctype_digit($submittedOTP)) {
        $response['success'] = false;
        $response['message'] = 'Please enter a valid 6-digit OTP code.';
        $response['invalid_otp'] = true;
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    // Verify OTP
    if ($submittedOTP !== $_SESSION['temp_otp']) {
        $response['success'] = false;
        $response['message'] = 'Invalid OTP code. Please try again.';
        $response['invalid_otp'] = true;
        $response['debug'] = 'OTP mismatch - Submitted: ' . $submittedOTP . ', Expected: ' . $_SESSION['temp_otp'];
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    // OTP is valid, update user in database
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // First, check if user exists
    $checkStmt = $conn->prepare("SELECT id, email, is_email_verified FROM users_info WHERE id = ?");
    $checkStmt->bind_param("i", $_SESSION['temp_user_id']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $response['success'] = false;
        $response['message'] = 'User not found. Please register again.';
        $response['redirect'] = 'register.php';
        $response['debug'] = 'User ID: ' . $_SESSION['temp_user_id'] . ' not found in database';
        $checkStmt->close();
        $conn->close();
        ob_clean();
        echo json_encode($response);
        exit;
    }
    
    $userData = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // Add debug info
    $response['debug_info'] = [
        'user_id' => $_SESSION['temp_user_id'],
        'user_email' => $userData['email'],
        'session_email' => $_SESSION['temp_email'],
        'current_verification_status' => $userData['is_email_verified']
    ];
    
    // Check if table has the required columns
    $columnsResult = $conn->query("DESCRIBE users_info");
    $columns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Update user's email verification status
    if (in_array('email_verified_at', $columns)) {
        $updateStmt = $conn->prepare("UPDATE users_info SET is_email_verified = 1, email_verified_at = NOW() WHERE id = ?");
    } else {
        $updateStmt = $conn->prepare("UPDATE users_info SET is_email_verified = 1 WHERE id = ?");
    }
    
    $updateStmt->bind_param("i", $_SESSION['temp_user_id']);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            // Successfully verified, clear session data
            unset($_SESSION['temp_user_id'], $_SESSION['temp_otp'], $_SESSION['temp_email'], $_SESSION['temp_name'], $_SESSION['otp_expiry']);
            
            $response['success'] = true;
            $response['message'] = 'Email verified successfully! You can now login to your account.';
            $response['redirect'] = 'login.php';
        } else {
            $response['success'] = false;
            $response['message'] = 'Failed to update verification status. User may already be verified.';
            $response['debug'] = 'Affected rows: ' . $updateStmt->affected_rows;
        }
    } else {
        throw new Exception('Error updating user verification status: ' . $updateStmt->error);
    }
    
    $updateStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Verification failed. Please try again.';
    $response['debug'] = $e->getMessage();
    error_log("OTP Verification Error: " . $e->getMessage());
}

ob_clean();
echo json_encode($response);
?>