<?php
include_once(__DIR__ . '/header.php');

// Uncomment and fix session validation
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_otp']) || !isset($_SESSION['temp_email'])) {
    header('Location: register.php?error=session_expired');
    exit;
}

// Check if OTP has expired
// c:\Users\sgtech\Downloads\verify_password_reset_otp.php

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userEmail = $_SESSION['temp_email'] ?? '';
$userName = $_SESSION['temp_name'] ?? '';

// Fix time calculation - ensure it's always positive
$timeLeft = 0;
if (isset($_SESSION['otp_expiry'])) {
    $timeLeft = $_SESSION['otp_expiry'] - time();
    if ($timeLeft < 0) {
        $timeLeft = 0;
    }
} else {
    // If no expiry set, set it to 5 minutes from now
    $_SESSION['otp_expiry'] = time() + 300;
    $timeLeft = 300;
}

// Debug information (remove in production)
error_log("OTP Debug - Time left: " . $timeLeft . ", Current time: " . time() . ", Expiry: " . ($_SESSION['otp_expiry'] ?? 'not set'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Your Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .success-message {
            color: #198754;
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
        .otp-input {
            width: 60px;
            height: 60px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            margin: 0 5px;
            font-weight: bold;
        }
        .otp-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .otp-input.is-invalid {
            border-color: #dc3545;
        }
        .otp-input.is-valid {
            border-color: #198754;
        }
        .otp-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        .timer {
            font-size: 18px;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 15px 0;
        }
        .timer.warning {
            color: #fd7e14;
        }
        .timer.normal {
            color: #198754;
        }
        .resend-section {
            text-align: center;
            margin-top: 20px;
        }
        .resend-btn {
            background: none;
            border: none;
            color: #0d6efd;
            text-decoration: underline;
            cursor: pointer;
            font-size: 14px;
        }
        .resend-btn:hover {
            color: #0a58ca;
        }
        .resend-btn:disabled {
            color: #6c757d;
            cursor: not-allowed;
            text-decoration: none;
        }
        .verification-icon {
            font-size: 64px;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 20px;
        }
        .masked-email {
            font-weight: bold;
            color: #198754;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 10px;
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
                <div class="verification-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="mb-4">Verify Your Email</h2>
                <p class="text-muted mb-3">We've sent a verification code to:</p>
                <p class="masked-email mb-3"><?php echo htmlspecialchars($userEmail); ?></p>
                <p class="text-muted">Please enter the 6-digit code to complete your registration.</p>
                
                <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    Time Left: <?php echo $timeLeft; ?>s<br>
                    Current Time: <?php echo time(); ?><br>
                    Expiry Time: <?php echo $_SESSION['otp_expiry'] ?? 'Not set'; ?><br>
                    User ID: <?php echo $_SESSION['temp_user_id'] ?? 'Not set'; ?><br>
                    OTP: <?php echo $_SESSION['temp_otp'] ?? 'Not set'; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div id="alert-container"></div>
                
                <form id="otpForm" novalidate>
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label text-center w-100">Enter 6-Digit Code <span class="required">*</span></label>
                        <div class="otp-container">
                            <input type="text" class="form-control otp-input" id="otp1" name="otp1" maxlength="1" required>
                            <input type="text" class="form-control otp-input" id="otp2" name="otp2" maxlength="1" required>
                            <input type="text" class="form-control otp-input" id="otp3" name="otp3" maxlength="1" required>
                            <input type="text" class="form-control otp-input" id="otp4" name="otp4" maxlength="1" required>
                            <input type="text" class="form-control otp-input" id="otp5" name="otp5" maxlength="1" required>
                            <input type="text" class="form-control otp-input" id="otp6" name="otp6" maxlength="1" required>
                        </div>
                        <div class="error-message text-center" id="otp-error"></div>
                    </div>
                    
                    <div class="timer" id="timer">
                        <?php if ($timeLeft > 0): ?>
                            Time remaining: <span id="time-left"><?php echo $timeLeft; ?></span>s
                        <?php else: ?>
                            <span class="text-danger">Code expired! Please request a new one.</span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="verifyBtn" <?php echo $timeLeft <= 0 ? 'disabled' : ''; ?>>
                        <span id="btnText">Verify Code</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                    
                    <div class="resend-section">
                        <p class="text-muted mb-2">Didn't receive the code?</p>
                        <button type="button" class="resend-btn" id="resendBtn">
                            <i class="fas fa-redo me-1"></i>Resend Code
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p><a href="register.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Registration
                        </a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let timeLeft = <?php echo $timeLeft; ?>;
        let timerInterval;
        let resendCooldown = 0;
        let resendInterval;
        
        $(document).ready(function() {
            console.log('Initial time left:', timeLeft);
            
            if (timeLeft > 0) {
                startTimer();
                $('#verifyBtn').prop('disabled', false);
            } else {
                $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                $('#verifyBtn').prop('disabled', true);
                $('#resendBtn').prop('disabled', false);
            }
            
            setupOTPInputs();
            
            // Auto-focus first input
            $('#otp1').focus();
        });
        
        function startTimer() {
            if (timeLeft <= 0) {
                $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                $('#verifyBtn').prop('disabled', true);
                $('#resendBtn').prop('disabled', false);
                return;
            }
            
            timerInterval = setInterval(function() {
                timeLeft--;
                $('#time-left').text(timeLeft);
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                    $('#verifyBtn').prop('disabled', true);
                    $('#resendBtn').prop('disabled', false);
                } else if (timeLeft <= 60) {
                    $('#timer').removeClass('normal').addClass('warning');
                } else {
                    $('#timer').removeClass('warning').addClass('normal');
                }
            }, 1000);
        }
        
        function setupOTPInputs() {
            $('.otp-input').on('input', function() {
                let value = $(this).val();
                
                // Only allow numbers
                if (!/^\d$/.test(value)) {
                    $(this).val('');
                    return;
                }
                
                // Move to next input
                let nextInput = $(this).next('.otp-input');
                if (nextInput.length > 0) {
                    nextInput.focus();
                }
                
                // Check if all inputs are filled
                checkOTPComplete();
            });
            
            $('.otp-input').on('keydown', function(e) {
                // Handle backspace
                if (e.key === 'Backspace' && $(this).val() === '') {
                    let prevInput = $(this).prev('.otp-input');
                    if (prevInput.length > 0) {
                        prevInput.focus();
                    }
                }
                
                // Handle paste
                if (e.ctrlKey && e.key === 'v') {
                    e.preventDefault();
                    handlePaste();
                }
            });
        }
        
        function handlePaste() {
            navigator.clipboard.readText().then(function(text) {
                if (/^\d{6}$/.test(text)) {
                    for (let i = 0; i < 6; i++) {
                        $('#otp' + (i + 1)).val(text[i]);
                    }
                    checkOTPComplete();
                }
            });
        }
        
        function checkOTPComplete() {
            let otp = getOTPValue();
            if (otp.length === 6) {
                $('.otp-input').removeClass('is-invalid').addClass('is-valid');
                $('#otp-error').text('');
            }
        }
        
        function getOTPValue() {
            let otp = '';
            for (let i = 1; i <= 6; i++) {
                otp += $('#otp' + i).val();
            }
            return otp;
        }
        
        function clearOTPInputs() {
            $('.otp-input').val('').removeClass('is-invalid is-valid');
            $('#otp1').focus();
        }
        
        function showAlert(message, type = 'danger') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alert-container').html(alertHtml);
            
            if (type === 'success') {
                setTimeout(() => $('.alert').alert('close'), 5000);
            }
        }
        
        function showLoading() {
            $('#verifyBtn').prop('disabled', true);
            $('#btnText').text('Verifying...');
            $('#btnSpinner').removeClass('d-none');
            $('#loader').css('display', 'flex');
        }
        
        function hideLoading() {
            $('#verifyBtn').prop('disabled', false);
            $('#btnText').text('Verify Code');
            $('#btnSpinner').addClass('d-none');
            $('#loader').css('display', 'none');
        }
        
        $('#otpForm').on('submit', function(e) {
            e.preventDefault();
            
            // Check if timer has expired
            if (timeLeft <= 0) {
                showAlert('OTP has expired. Please request a new one.', 'danger');
                return;
            }
            
            let otp = getOTPValue();
            if (otp.length !== 6) {
                $('.otp-input').addClass('is-invalid');
                $('#otp-error').text('Please enter all 6 digits');
                return;
            }
            
            showLoading();
            
            $.ajax({
                url: 'handle_verify_otp.php',
                type: 'POST',
                data: {
                    otp: otp,
                    csrf_token: $('#csrf_token').val()
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = response.redirect || 'login.php';
                        }, 2000);
                    } else {
                        showAlert(response.message, 'danger');
                        if (response.invalid_otp) {
                            $('.otp-input').addClass('is-invalid');
                            $('#otp-error').text('Invalid or expired code');
                            clearOTPInputs();
                        }
                        
                        // If session expired, redirect to registration
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 2000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    let message = 'An error occurred. Please try again.';
                    if (status === 'timeout') {
                        message = 'Request timed out. Please try again.';
                    } else if (xhr.status === 403) {
                        message = 'Security error. Please reload the page and try again.';
                    } else if (xhr.status === 500) {
                        message = 'Server error. Please try again later.';
                    }
                    
                    showAlert(message, 'danger');
                },
                complete: function() {
                    hideLoading();
                }
            });
        });
        
        $('#resendBtn').on('click', function() {
            if (resendCooldown > 0) return;
            
            $(this).prop('disabled', true);
            resendCooldown = 60;
            
            $.ajax({
                url: 'handle_resend_otp.php',
                type: 'POST',
                data: {
                    csrf_token: $('#csrf_token').val()
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Resend response:', response);
                    
                    if (response.success) {
                        showAlert(response.message, 'success');
                        timeLeft = 300; // Reset timer to 5 minutes
                        clearInterval(timerInterval);
                        $('#timer').removeClass('warning').addClass('normal');
                        $('#verifyBtn').prop('disabled', false);
                        startTimer();
                        clearOTPInputs();
                    } else {
                        showAlert(response.message, 'danger');
                        
                        // If session expired, redirect to registration
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 2000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Resend error:', error);
                    showAlert('Failed to resend code. Please try again.', 'danger');
                }
            });
            
            // Start resend cooldown
            resendInterval = setInterval(function() {
                resendCooldown--;
                $('#resendBtn').html(`<i class="fas fa-redo me-1"></i>Resend Code (${resendCooldown}s)`);
                
                if (resendCooldown <= 0) {
                    clearInterval(resendInterval);
                    $('#resendBtn').html('<i class="fas fa-redo me-1"></i>Resend Code').prop('disabled', false);
                }
            }, 1000);
        });
    </script>
</body>
</html>