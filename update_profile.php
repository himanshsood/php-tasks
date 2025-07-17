<?php
include_once(__DIR__ . '/config.php');

header('Content-Type: application/json');

$response = [];
$http_status_code = 200;

// Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Function to handle image upload
function handleImageUpload($file, $uploadDir, $userId) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and GIF files are allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size must be less than 2MB.'];
    }
    
    // Validate actual image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Invalid image file.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => './uploads/profile_pictures/' . $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $http_status_code = 401;
    $response['error'] = 'User not authenticated.';
    http_response_code($http_status_code);
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $http_status_code = 400;
        $response['error'] = 'CSRF token missing.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $http_status_code = 400;
        $response['error'] = 'Invalid CSRF token.';
    } else {
        // Get and sanitize input data
        $user_id = intval($_SESSION['user_id']);
        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
        $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
        $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
        $confirmNewPassword = isset($_POST['confirmNewPassword']) ? $_POST['confirmNewPassword'] : '';
        
        $errors = [];
        
        // Validate required fields
        if (empty($firstName)) {
            $errors['firstName'] = 'First name is required.';
        } elseif (strlen($firstName) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
            $errors['firstName'] = 'First name must be at least 2 characters and contain only letters.';
        }
        
        if (empty($lastName)) {
            $errors['lastName'] = 'Last name is required.';
        } elseif (strlen($lastName) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
            $errors['lastName'] = 'Last name must be at least 2 characters and contain only letters.';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        if (empty($currentPassword)) {
            $errors['currentPassword'] = 'Current password is required to save changes.';
        }
        
        // Validate optional phone
        if (!empty($phone) && !preg_match('/^[\+]?[\d\s\-\(\)]{10,}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        
        // Validate new password if provided
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8 || 
                !preg_match('/[A-Z]/', $newPassword) || 
                !preg_match('/[a-z]/', $newPassword) || 
                !preg_match('/[0-9]/', $newPassword) || 
                !preg_match('/[\W_]/', $newPassword)) {
                
                $requirements = [];
                if (strlen($newPassword) < 8) $requirements[] = '8 characters';
                if (!preg_match('/[A-Z]/', $newPassword)) $requirements[] = '1 uppercase letter';
                if (!preg_match('/[a-z]/', $newPassword)) $requirements[] = '1 lowercase letter';
                if (!preg_match('/[0-9]/', $newPassword)) $requirements[] = '1 number';
                if (!preg_match('/[\W_]/', $newPassword)) $requirements[] = '1 special character';
                
                $errors['newPassword'] = 'Password must contain at least: ' . implode(', ', $requirements);
            }
            
            if ($newPassword !== $confirmNewPassword) {
                $errors['confirmNewPassword'] = 'New passwords do not match.';
            }
        } elseif (!empty($confirmNewPassword)) {
            $errors['newPassword'] = 'Please enter a new password.';
        }
        
        // Handle profile picture upload
        $profilePicturePath = null;
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['profilePicture'], $uploadDir, $user_id);
            if (!$uploadResult['success']) {
                $errors['profilePicture'] = $uploadResult['error'];
            } else {
                $profilePicturePath = $uploadResult['path'];
            }
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            $http_status_code = 400;
            $response['success'] = false;
            $response['message'] = 'Please fix the following errors:';
            $response['errors'] = $errors;
        } else {
            // Get current user data to verify current password
            $stmt = $conn->prepare("SELECT * FROM users_info WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $http_status_code = 404;
                $response['success'] = false;
                $response['message'] = 'User not found.';
            } else {
                $user = $result->fetch_assoc();
                
                // Verify current password
                if (!password_verify($currentPassword, $user['password'])) {
                    $http_status_code = 400;
                    $response['success'] = false;
                    $response['message'] = 'Current password is incorrect.';
                    $response['errors'] = ['currentPassword' => 'Current password is incorrect.'];
                } else {
                    // Check if email is already taken by another user
                    $stmt = $conn->prepare("SELECT id FROM users_info WHERE email = ? AND id != ?");
                    $stmt->bind_param("si", $email, $user_id);
                    $stmt->execute();
                    $email_result = $stmt->get_result();
                    
                    if ($email_result->num_rows > 0) {
                        $http_status_code = 400;
                        $response['success'] = false;
                        $response['message'] = 'Email address is already taken.';
                        $response['errors'] = ['email' => 'Email address is already taken.'];
                    } else {
                        // Start transaction
                        $conn->begin_transaction();
                        
                        try {
                            // Update user information
                            $password_to_update = !empty($newPassword) ? password_hash($newPassword, PASSWORD_DEFAULT) : $user['password'];
                            
                            // Prepare the update query based on whether profile picture is being updated
                            if ($profilePicturePath) {
                                // Delete old profile picture if it exists
                                if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                                    unlink(__DIR__ . '/' . $user['profile_picture']);
                                }
                                
                                $stmt = $conn->prepare("UPDATE users_info SET 
                                    first_name = ?, 
                                    last_name = ?, 
                                    email = ?, 
                                    phone = ?, 
                                    location = ?, 
                                    bio = ?, 
                                    password = ?, 
                                    profile_picture = ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?");
                                
                                $stmt->bind_param("ssssssssi", 
                                    $firstName, 
                                    $lastName, 
                                    $email, 
                                    $phone, 
                                    $location, 
                                    $bio, 
                                    $password_to_update, 
                                    $profilePicturePath,
                                    $user_id
                                );
                            } else {
                                $stmt = $conn->prepare("UPDATE users_info SET 
                                    first_name = ?, 
                                    last_name = ?, 
                                    email = ?, 
                                    phone = ?, 
                                    location = ?, 
                                    bio = ?, 
                                    password = ?, 
                                    updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?");
                                
                                $stmt->bind_param("sssssssi", 
                                    $firstName, 
                                    $lastName, 
                                    $email, 
                                    $phone, 
                                    $location, 
                                    $bio, 
                                    $password_to_update, 
                                    $user_id
                                );
                            }
                            
                            if ($stmt->execute()) {
                                // Commit transaction
                                $conn->commit();
                                
                                $response['success'] = true;
                                $response['message'] = 'Profile updated successfully!';
                                $response['user'] = [
                                    'firstName' => $firstName,
                                    'lastName' => $lastName,
                                    'email' => $email,
                                    'phone' => $phone,
                                    'location' => $location,
                                    'bio' => $bio,
                                    'profilePicture' => $profilePicturePath ?: $user['profile_picture']
                                ];
                                
                                // Log the update (optional)
                                error_log("Profile updated for user ID: " . $user_id);
                                
                            } else {
                                throw new Exception('Database update failed');
                            }
                            
                        } catch (Exception $e) {
                            // Rollback transaction
                            $conn->rollback();
                            
                            // If there was an uploaded file, clean it up
                            if ($profilePicturePath && file_exists(__DIR__ . '/' . $profilePicturePath)) {
                                unlink(__DIR__ . '/' . $profilePicturePath);
                            }
                            
                            $http_status_code = 500;
                            $response['success'] = false;
                            $response['message'] = 'An error occurred while updating your profile. Please try again.';
                            
                            // Log the error
                            error_log("Profile update error for user ID " . $user_id . ": " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
} else {
    $http_status_code = 405;
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

http_response_code($http_status_code);
echo json_encode($response);
?>