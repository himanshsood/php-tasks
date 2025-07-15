<?php
$PAGE_TITLE = "Verify OTP - Password Reset";
include_once(__DIR__ . "/header.php");

// Check if user came from forgot password flow
if (!isset($_SESSION['forgot_password_otp']) || !isset($_SESSION['otp_email'])) {
    header('Location: forgot_password.php?error=session_expired');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userEmail = $_SESSION['otp_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Password Reset</title>
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
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div id="loader">
        <div class="loader-spinner"></div>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="verification-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h2 class="text-center mb-4">Verify OTP</h2>
                        <p class="text-muted text-center mb-3">We've sent a verification code to:</p>
                        <p class="masked-email text-center mb-4"><?php echo htmlspecialchars($userEmail); ?></p>
                        <p class="text-muted text-center mb-4">Please enter the 6-digit code to reset your password.</p>
                        
                        <div id="alert-container"></div>
                        
                        <form id="otpForm" novalidate>
                            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label text-center w-100">Enter 6-Digit Code <span class="required">*</span></label>
                                <div class="otp-container">
                                    <input type="text" class="form-control otp-input" id="otp1" maxlength="1" required>
                                    <input type="text" class="form-control otp-input" id="otp2" maxlength="1" required>
                                    <input type="text" class="form-control otp-input" id="otp3" maxlength="1" required>
                                    <input type="text" class="form-control otp-input" id="otp4" maxlength="1" required>
                                    <input type="text" class="form-control otp-input" id="otp5" maxlength="1" required>
                                    <input type="text" class="form-control otp-input" id="otp6" maxlength="1" required>
                                </div>
                                <div class="error-message text-center" id="otp-error"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" id="verifyBtn">
                                <span id="btnText">Verify Code</span>
                                <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                            
                            <div class="text-center mt-3">
                                <p><a href="forgot_password.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Forgot Password
                                </a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            setupOTPInputs();
            $('#otp1').focus();
        });
        
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
            
            let otp = getOTPValue();
            if (otp.length !== 6) {
                $('.otp-input').addClass('is-invalid');
                $('#otp-error').text('Please enter all 6 digits');
                return;
            }
            
            showLoading();
            
            $.ajax({
                url: 'check_otp.php',
                type: 'POST',
                data: {
                    otp: otp,
                    email: $('#email').val(),
                    csrf_token: $('#csrf_token').val()
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'reset_password.php?email=' + encodeURIComponent($('#email').val());
                        }, 2000);
                    } else {
                        showAlert(response.error, 'danger');
                        $('.otp-input').addClass('is-invalid');
                        $('#otp-error').text('Invalid or expired code');
                        clearOTPInputs();
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
    </script>
</body>
</html>