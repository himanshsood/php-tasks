<?php
$PAGE_TITLE = "Register";
$EXTRA_STYLES = '<link rel="stylesheet" href="./styles/register.css">';
include_once(__DIR__ . '/header.php');

//if csrf token not present make a new one
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- loader hidden by default, shows when user submits form  -->

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

<script>
    //this code only runs when the page has loaded
    $(document).ready(function() {
        // Clear error messages, warnings, bootstrap msgs
        function clearErrors() {
            $('.error-message').text('');
            $('.form-control').removeClass('is-invalid is-valid');
            $('#alert-container').empty();
        }

        // Show field error
        // Displays an error message under a specific input.
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

            if (type === 'success') {
                setTimeout(() => $('.alert').alert('close'), 5000);
            }
        }

        // Email validation
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email) && email.length <= 255;
        }

        // Password validation
        function isValidPassword(password) {
            return password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password) &&
                /[\W_]/.test(password);
        }

        // Name validation
        function isValidName(name) {
            return name && name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
        }

        // Client-side validation
        function validateForm() {
            clearErrors();
            let isValid = true;

            const firstName = $('#firstName').val().trim();
            const lastName = $('#lastName').val().trim();
            const email = $('#email').val().trim();
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();

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

            if (!password) {
                showFieldError('password', 'Password is required');
                isValid = false;
            } else if (!isValidPassword(password)) {
                let requirements = [];
                if (password.length < 8) requirements.push('8 characters');
                if (!/[A-Z]/.test(password)) requirements.push('1 uppercase letter');
                if (!/[a-z]/.test(password)) requirements.push('1 lowercase letter');
                if (!/[0-9]/.test(password)) requirements.push('1 number');
                if (!/[\W_]/.test(password)) requirements.push('1 special character');

                showFieldError('password', 'Password must contain at least: ' + requirements.join(', '));
                isValid = false;
            }

            if (!confirmPassword) {
                showFieldError('confirmPassword', 'Please confirm your password');
                isValid = false;
            } else if (password !== confirmPassword) {
                showFieldError('confirmPassword', 'Passwords do not match');
                isValid = false;
            }

            return isValid;
        }

        // Loading state
        //PREVENTS RESUBMISSIONS
        function showLoading() {
            $('#registerBtn').prop('disabled', true);
            $('#loader').css('display', 'flex');
        }

        function hideLoading() {
            $('#registerBtn').prop('disabled', false);
            $('#btnText').text('Create Account');
            $('#btnSpinner').addClass('d-none');
            $('#loader').css('display', 'none');
        }

        // Form submission
        $('#registrationForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateForm()) return;

            showLoading();

            const formData = {
                firstName: $('#firstName').val().trim(),
                lastName: $('#lastName').val().trim(),
                email: $('#email').val().trim(),
                password: $('#password').val(),
                confirmPassword: $('#confirmPassword').val(),
                csrf_token: $('#csrf_token').val()
            };

            $.ajax({
                url: 'handleregister.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        $('#registrationForm')[0].reset();

                        if (response.redirect) {
                            setTimeout(() => window.location.href = response.redirect, 2000);
                        }
                    } else {
                        showAlert(response.message, 'danger');

                        if (response.errors) {
                            Object.keys(response.errors).forEach(field => {
                                showFieldError(field, response.errors[field]);
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);

                    let message = 'An error occurred. Please try again.';

                    // Check if response is HTML (PHP error page)
                    if (xhr.responseText && xhr.responseText.includes('<br />')) {
                        message = 'Server configuration error. Please check server logs.';
                        console.error('PHP Error detected:', xhr.responseText);
                    } else if (status === 'timeout') {
                        message = 'Request timed out. Please try again.';
                    } else if (xhr.status === 403) {
                        message = 'Security error. Please reload the page and try again.';
                    } else if (xhr.status === 500) {
                        message = 'Server error. Please try again later.';
                    } else if (xhr.status === 404) {
                        message = 'Registration handler not found. Please check the file path.';
                    }

                    showAlert(message, 'danger');
                },
                complete: function() {
                    hideLoading();
                }
            });
        });

        // Real-time validation

    });
</script>