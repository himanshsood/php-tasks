<?php
include_once(__DIR__ . '/header.php');

// FIXED: Only start session if not already started
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Your Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #ffffff;
            border-top: 6px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>
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
                <div id="alert-container"></div>
                
                <form id="registrationForm" novalidate>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/handleregister.js"></script>
</body>
</html>
