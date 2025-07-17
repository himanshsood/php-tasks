<?php
$EXTRA_STYLES = '<link rel="stylesheet" href="./styles/verify_otp.css">';
include_once(__DIR__ . '/header.php');


// Session validation
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_otp']) || !isset($_SESSION['temp_email'])) {
    header('Location: register.php?error=session_expired');
    exit;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userEmail = $_SESSION['temp_email'];

// Calculate time left
$timeLeft = 0;
if (isset($_SESSION['otp_expiry'])) {
    $timeLeft = max(0, $_SESSION['otp_expiry'] - time());
} else {
    $_SESSION['otp_expiry'] = time() + 300;
    $timeLeft = 300;
}
?>

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
            </div>
            <div class="col-md-6">
                <div id="alert-container"></div>
                
                <form id="otpForm" novalidate>
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label text-center w-100">Enter 6-Digit Code <span class="text-danger">*</span></label>
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
                    
                    <div class="text-center mt-3">
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

    
    <script>
        let timeLeft = <?php echo $timeLeft; ?>;
        let timerInterval;
        let resendCooldown = 0;
        let resendInterval;
        
        $(document).ready(function() {
            if (timeLeft > 0) {
                startTimer();
                $('#verifyBtn').prop('disabled', false);
            } else {
                $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                $('#verifyBtn').prop('disabled', true);
            }
            
            setupOTPInputs();
            $('#otp1').focus();
        });
        
        function startTimer() {
            if (timeLeft <= 0) {
                $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                $('#verifyBtn').prop('disabled', true);
                return;
            }
            
            timerInterval = setInterval(function() {
                timeLeft--;
                $('#time-left').text(timeLeft);
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    $('#timer').html('<span class="text-danger">Code expired! Please request a new one.</span>');
                    $('#verifyBtn').prop('disabled', true);
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
                
                if (!/^\d$/.test(value)) {
                    $(this).val('');
                    return;
                }
                
                let nextInput = $(this).next('.otp-input');
                if (nextInput.length > 0) {
                    nextInput.focus();
                }
                
                checkOTPComplete();
            });
            
            $('.otp-input').on('keydown', function(e) {
                if (e.key === 'Backspace' && $(this).val() === '') {
                    let prevInput = $(this).prev('.otp-input');
                    if (prevInput.length > 0) {
                        prevInput.focus();
                    }
                }
                
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
                        
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 2000);
                        }
                    }
                },
                error: function(xhr, status, error) {
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
                    if (response.success) {
                        showAlert(response.message, 'success');
                        timeLeft = 300;
                        clearInterval(timerInterval);
                        $('#timer').removeClass('warning').addClass('normal').html('Time remaining: <span id="time-left">300</span>s');
                        $('#verifyBtn').prop('disabled', false);
                        startTimer();
                        clearOTPInputs();
                    } else {
                        showAlert(response.message, 'danger');
                        
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 2000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Failed to resend code. Please try again.', 'danger');
                }
            });
            
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
