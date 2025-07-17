<?php
$PAGE_TITLE = "Profile";
$EXTRA_STYLES = '<link rel="stylesheet" href="./styles/profile.css">';
include_once(__DIR__ . '/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$qry = "SELECT * FROM users_info WHERE id = " . intval($user_id);
$result = $conn->query($qry);

if ($result->num_rows === 0) {
    // User not found, redirect to login
    session_destroy();
    header('Location: login.php');
    exit();
}

$user = $result->fetch_assoc();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card profile-card">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'https://via.placeholder.com/150x150/007bff/ffffff?text=' . strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>" 
                             alt="Profile Picture" class="rounded-circle" width="150" height="150">
                    </div>
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted">Member since: <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    
                    <button class="btn btn-primary btn-sm" id="editProfileBtn">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Alert Container -->
            <div id="alert-container"></div>
            
            <!-- Profile Information View -->
            <div class="card" id="profileView">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>First Name:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($user['first_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Last Name:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($user['last_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Email:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Phone:</strong></div>
                        <div class="col-sm-9"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '-'; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Bio:</strong></div>
                        <div class="col-sm-9"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : '-'; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Location:</strong></div>
                        <div class="col-sm-9"><?php echo !empty($user['location']) ? htmlspecialchars($user['location']) : '-'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Edit Form -->
            <div class="card d-none" id="profileEdit">
                <div class="card-header">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form id="profileForm" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    <div class="error-message" id="firstName-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    <div class="error-message" id="lastName-error"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <div class="error-message" id="email-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            <div class="error-message" id="phone-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            <div class="error-message" id="location-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <div class="error-message" id="bio-error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="profilePicture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profilePicture" name="profilePicture" accept="image/*">
                            <div class="form-text">Supported formats: JPG, PNG, GIF (Max: 2MB)</div>
                            <div class="error-message" id="profilePicture-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" 
                                   placeholder="Enter current password to save changes">
                            <div class="form-text">Required only when updating profile information</div>
                            <div class="error-message" id="currentPassword-error"></div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Change Password (Optional)</h6>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword">
                            <div class="form-text">Leave blank to keep current password</div>
                            <div class="error-message" id="newPassword-error"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword">
                            <div class="error-message" id="confirmNewPassword-error"></div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">
                                <span id="saveBtnText">Save Changes</span>
                                <span id="saveBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle between view and edit mode
    $('#editProfileBtn').on('click', function() {
        $('#profileView').addClass('d-none');
        $('#profileEdit').removeClass('d-none');
    });
    
    $('#cancelBtn').on('click', function() {
        $('#profileEdit').addClass('d-none');
        $('#profileView').removeClass('d-none');
        clearErrors();
    });
    
    // Clear error messages and styles
    function clearErrors() {
        $('.error-message').text('');
        $('.form-control').removeClass('is-invalid is-valid');
        $('#alert-container').empty();
    }
    
    // Show field error
    function showFieldError(fieldId, message) {
        $('#' + fieldId + '-error').text(message);
        $('#' + fieldId).addClass('is-invalid');
    }
    
    // Show alert message
    function showAlert(message, type = 'danger') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#alert-container').html(alertHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => $('.alert').alert('close'), 5000);
        }
    }
    
    // Validation functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email) && email.length <= 255;
    }
    
    function isValidPassword(password) {
        return password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /[0-9]/.test(password) &&
            /[\W_]/.test(password);
    }
    
    function isValidName(name) {
        return name && name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
    }
    
    function isValidPhone(phone) {
        if (!phone) return true; // Optional field
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    // Image validation function
    function isValidImage(file) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!allowedTypes.includes(file.type)) {
            return { valid: false, message: 'Only JPG, PNG, and GIF files are allowed.' };
        }
        
        if (file.size > maxSize) {
            return { valid: false, message: 'File size must be less than 2MB.' };
        }
        
        return { valid: true };
    }

    // Add image preview functionality
    $('#profilePicture').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const validation = isValidImage(file);
            if (!validation.valid) {
                showFieldError('profilePicture', validation.message);
                $(this).val('');
            } else {
                $('#profilePicture-error').text('');
                $('#profilePicture').removeClass('is-invalid');
            }
        }
    });

    
    // Form validation
    function validateForm() {
        clearErrors();
        let isValid = true;
        
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmNewPassword = $('#confirmNewPassword').val();
        
        // Validate required fields
        if (!firstName) {
            showFieldError('firstName', 'First name is required');
            isValid = false;
        } else if (!isValidName(firstName)) {
            showFieldError('firstName', 'First name must be at least 2 characters and contain only letters');
            isValid = false;
        }
        
        if (!lastName) {
            showFieldError('lastName', 'Last name is required');
            isValid = false;
        } else if (!isValidName(lastName)) {
            showFieldError('lastName', 'Last name must be at least 2 characters and contain only letters');
            isValid = false;
        }
        
        if (!email) {
            showFieldError('email', 'Email address is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }
        
        if (phone && !isValidPhone(phone)) {
            showFieldError('phone', 'Please enter a valid phone number');
            isValid = false;
        }

        // Validate profile picture if provided
        const profilePictureInput = document.getElementById('profilePicture');
        if (profilePictureInput.files.length > 0) {
            const file = profilePictureInput.files[0];
            const validation = isValidImage(file);
            if (!validation.valid) {
                showFieldError('profilePicture', validation.message);
                isValid = false;
            }
        }
                
        if (!currentPassword) {
            showFieldError('currentPassword', 'Current password is required to save changes');
            isValid = false;
        }

        
        
        // Validate new password if provided
        if (newPassword) {
            if (!isValidPassword(newPassword)) {
                let requirements = [];
                if (newPassword.length < 8) requirements.push('8 characters');
                if (!/[A-Z]/.test(newPassword)) requirements.push('1 uppercase letter');
                if (!/[a-z]/.test(newPassword)) requirements.push('1 lowercase letter');
                if (!/[0-9]/.test(newPassword)) requirements.push('1 number');
                if (!/[\W_]/.test(newPassword)) requirements.push('1 special character');
                
                showFieldError('newPassword', 'Password must contain at least: ' + requirements.join(', '));
                isValid = false;
            }
            
            if (newPassword !== confirmNewPassword) {
                showFieldError('confirmNewPassword', 'New passwords do not match');
                isValid = false;
            }
        } else if (confirmNewPassword) {
            showFieldError('newPassword', 'Please enter a new password');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Loading state
    // function showLoading() {
    //     $('#saveBtn').prop('disabled', true);
    //     $('#saveBtnText').text('Saving...');
    //     $('#saveBtnSpinner').removeClass('d-none');
    // }
    
    // function hideLoading() {
    //     $('#saveBtn').prop('disabled', false);
    //     $('#saveBtnText').text('Save Changes');
    //     $('#saveBtnSpinner').addClass('d-none');
    // }
    
    // Form submission
    
});
</script>

<?php include_once(__DIR__ . '/footer.php'); ?>