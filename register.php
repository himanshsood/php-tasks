<?php
$PAGE_TITLE = "Register";
include_once(__DIR__ . '/header.php');

//if csrf token not present make a new one
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./styles/register.css">
    
</head>
<body>

    <!-- loader hidden by default, shows when user submits form  -->
    <div id="loader">
        <div class="loader-spinner"></div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 mb-4">
                <img src="https://orientaloutsourcing.com/images/contact.png" class="img-fluid mb-3" alt="Register">
                <h2 class="mb-4">Create Account</h2>
                <p class="text-muted mb-4">Join us today! Please fill in the details below to create your account.</p>
            </div>
            <div class="col-md-6">

                <!-- warning which comes at top of form  -->
                <div id="alert-container"></div>
                
                <!-- novalidate - Tells browser not to show its own validation bubbles. -->
                <form id="registrationForm" novalidate>
                    

                    <!-- csrf token -->
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                        <div class="error-message" id="firstName-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                        <div class="error-message" id="lastName-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="error-message" id="email-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character</div>
                        <div class="error-message" id="password-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password <span class="required">*</span></label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        <div class="error-message" id="confirmPassword-error"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="registerBtn">
                        <span id="btnText">Create Account</span>

                        <!-- loader -->
                        <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./js/handleregister.js"></script>
</body>
</html>
