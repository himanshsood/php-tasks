<?php
require_once '../config.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => ''
];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if user is already logged in
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $response['success'] = true;
        $response['message'] = 'Already logged in';
        $response['redirect_url'] = 'customers.php';
        echo json_encode($response);
        exit;
    }

    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
    }

    // Get and sanitize input data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Server-side validation
    $validation_errors = [];

    // Email validation
    if (empty($email)) {
        $validation_errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 255) {
        $validation_errors['email'] = 'Invalid email';
    }

    // Password validation
    if (empty($password)) {
        $validation_errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6 || strlen($password) > 255) {
        $validation_errors['password'] = 'Invalid email or password';
    }

    // If validation errors exist, return them
    if (!empty($validation_errors)) {
        $response['message'] = 'Validation errors occurred';
        $response['errors'] = $validation_errors;
        echo json_encode($response);
        exit;
    }

    // Database connection check
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Rate limiting check (optional - implement based on your needs)
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    
    // Check for too many failed login attempts (you can implement this based on your requirements)
    // This is a basic example - you might want to store this in database or cache
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    // Clean old attempts (older than 15 minutes)
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt_time) use ($current_time) {
        return ($current_time - $attempt_time) < 900; // 15 minutes
    });
    
    // Check if too many attempts
    if (count($_SESSION['login_attempts']) >= 5) {
        throw new Exception('Too many failed login attempts. Please try again later.');
    }

    // Prepare and execute query to find admin by email
    $stmt = $conn->prepare("SELECT admin_id, first_name, last_name, email, password, status FROM admin_info WHERE email = ? AND status = 'active'");
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            // Login successful - clear failed attempts
            unset($_SESSION['login_attempts']);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_first_name'] = $admin['first_name'];
            $_SESSION['admin_last_name'] = $admin['last_name'];
            $_SESSION['login_time'] = time();
            
            // Update last login time in database (optional)
            $update_stmt = $conn->prepare("UPDATE admin_info SET last_login = NOW() WHERE admin_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("i", $admin['admin_id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Log successful login (optional)
            error_log("Admin login successful: " . $admin['email'] . " from IP: " . $ip_address);
            
            $response['success'] = true;
            $response['message'] = 'Login successful';
            $response['redirect_url'] = 'customers.php';
            
        } else {
            // Invalid password - record failed attempt
            $_SESSION['login_attempts'][] = $current_time;
            
            // Log failed login attempt
            error_log("Failed login attempt for email: " . $email . " from IP: " . $ip_address);
            
            throw new Exception('Invalid email or password');
        }
    } else {
        // User not found - record failed attempt
        $_SESSION['login_attempts'][] = $current_time;
        
        // Log failed login attempt
        error_log("Failed login attempt for non-existent email: " . $email . " from IP: " . $ip_address);
        
        throw new Exception('Invalid email or password');
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log the error for debugging (don't expose sensitive information)
    error_log("Login error: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR']);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
exit;
?>