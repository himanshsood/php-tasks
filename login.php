<?php
$PAGE_TITLE = "Login";
include_once(__DIR__ . '/header.php');

// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 d-flex align-items-center">
            <img src="resources/images/4957136_4957136.jpg" alt="Login Image" class="img-fluid">
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <div class="p-5 w-100">
                <!-- Login Form -->
                <div id="loginStep">
                    <h2 class="mb-5">Login</h2>
                    <form id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="form-group mb-3">
                            <label for="email">Email: <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email">
                            <small class="text-danger" id="emailErr"></small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password: <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-danger" id="passwordErr"></small>
                        </div>
                        <button type="submit" class="btn btn-primary my-3">Login</button>
                    </form>
                    <a href="forgot_password.php" class="d-block mt-2">Forgot Password?</a>
                </div>

                <!-- OTP Verification Form -->
                <div id="otpStep" style="display: none;">
                    <h2 class="mb-5">Enter OTP</h2>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        We've sent a 6-digit OTP to your email address. Please enter it below to complete your login.
                    </div>
                    <form id="otpForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="otp_verification" value="true">
                        <div class="form-group mb-3">
                            <label for="otp">Enter OTP: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-center" id="otp" name="otp" placeholder="123456" maxlength="6" style="font-size: 1.5em; letter-spacing: 0.5em;">
                            <small class="text-danger" id="otpErr"></small>
                        </div>
                        <button type="submit" class="btn btn-success my-3">
                            <i class="fas fa-check"></i> Verify OTP
                        </button>
                        <button type="button" class="btn btn-secondary my-3 ms-2" id="backToLogin">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </button>
                    </form>
                    <div class="mt-3">
                        <small class="text-muted">Didn't receive OTP? 
                            <a href="#" id="resendOTP" class="text-primary">Resend OTP</a>
                        </small>
                    </div>
                </div>

                <div id="responseMessage" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let userEmail = '';

        // Handle initial login form submission
        $('#loginForm').on('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            var isValid = true;

            // Clear previous errors
            $('#emailErr').text('');
            $('#passwordErr').text('');
            $('#responseMessage').empty();

            var email = $('#email').val().trim();
            var password = $('#password').val().trim();

            // Validation
            if (email === '') {
                $('#emailErr').text('Email is required');
                isValid = false;
            } else if (!validateEmail(email)) {
                $('#emailErr').text('Invalid email format');
                isValid = false;
            }
            if (password === '') {
                $('#passwordErr').text('Password is required');
                isValid = false;
            }

            if (isValid) {
                userEmail = email;
                showLoading();
    
                $.ajax({
                    url: 'login_ajax.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        hideLoading();
                        console.log(response);
                        
                        if (response.success) {
                            if (response.require_otp) {
                                // Show OTP form
                                $('#loginStep').hide();
                                $('#otpStep').show();
                                $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                                $('#otp').focus();
                            } else {
                                // Direct login success (fallback)
                                $('#responseMessage').html('<div class="alert alert-success">' + response.message + ' Redirecting...</div>');
                                setTimeout(function() {
                                    window.location.href = response.redirectUrl;
                                }, 2000);
                            }
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                            } else {
                                $('#responseMessage').html('<div class="alert alert-danger">An unknown error occurred.</div>');
                            }
                        } catch {
                            $('#responseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                        }
                    }
                });
            }
        });

        // Handle OTP form submission
        $('#otpForm').on('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            var isValid = true;

            // Clear previous errors
            $('#otpErr').text('');
            $('#responseMessage').empty();

            var otp = $('#otp').val().trim();

            // Validation
            if (otp === '') {
                $('#otpErr').text('OTP is required');
                isValid = false;
            } else if (otp.length !== 6 || !otp.match(/^\d+$/)) {
                $('#otpErr').text('OTP must be 6 digits');
                isValid = false;
            }

            if (isValid) {
                showLoading();
    
                $.ajax({
                    url: 'login_ajax.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        hideLoading();
                        console.log(response);
                        
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success">' + response.message + ' Redirecting...</div>');
                            setTimeout(function() {
                                window.location.href = response.redirectUrl;
                            }, 2000);
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                            } else {
                                $('#responseMessage').html('<div class="alert alert-danger">An unknown error occurred.</div>');
                            }
                        } catch {
                            $('#responseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                        }
                    }
                });
            }
        });

        // Back to login button
        $('#backToLogin').on('click', function() {
            $('#otpStep').hide();
            $('#loginStep').show();
            $('#responseMessage').empty();
            $('#otp').val('');
            $('#otpErr').text('');
        });

        // Resend OTP functionality
        $('#resendOTP').on('click', function(e) {
            e.preventDefault();
            
            if (!userEmail) {
                $('#responseMessage').html('<div class="alert alert-danger">Please login again to resend OTP.</div>');
                return;
            }

            showLoading();
            
            // Resend OTP by re-submitting login form data
            var formData = new FormData();
            formData.append('csrf_token', $('input[name="csrf_token"]').val());
            formData.append('email', userEmail);
            formData.append('password', $('#password').val());

            $.ajax({
                url: 'login_ajax.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    hideLoading();
                    if (response.success && response.require_otp) {
                        $('#responseMessage').html('<div class="alert alert-success">OTP has been resent to your email address.</div>');
                    } else {
                        $('#responseMessage').html('<div class="alert alert-danger">Unable to resend OTP. Please try again.</div>');
                    }
                },
                error: function() {
                    hideLoading();
                    $('#responseMessage').html('<div class="alert alert-danger">Unable to resend OTP. Please try again.</div>');
                }
            });
        });

        // Auto-focus and format OTP input
        $('#otp').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });

        // Email validation function
        function validateEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    });
</script>

<?php include_once(__DIR__ . '/footer.php'); ?>