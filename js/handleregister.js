$(document).ready(function() {
    // Clear error messages
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
    function showLoading() {
        $('#registerBtn').prop('disabled', true);
        $('#btnText').text('Creating Account...');
        $('#btnSpinner').removeClass('d-none');
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
            timeout: 30000,
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
    $('#firstName, #lastName').on('input', function() {
        const value = $(this).val().trim();
        if (value && isValidName(value)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#' + $(this).attr('id') + '-error').text('');
        } else if (value) {
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });

    $('#email').on('input', function() {
        const email = $(this).val().trim();
        if (email && isValidEmail(email)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#email-error').text('');
        } else if (email) {
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });

    $('#password').on('input', function() {
        const password = $(this).val();
        if (password && isValidPassword(password)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#password-error').text('');
        } else if (password) {
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
        
        // Re-validate confirm password
        const confirmPassword = $('#confirmPassword').val();
        if (confirmPassword) {
            if (password === confirmPassword) {
                $('#confirmPassword').removeClass('is-invalid').addClass('is-valid');
                $('#confirmPassword-error').text('');
            } else {
                $('#confirmPassword').removeClass('is-valid');
            }
        }
    });

    $('#confirmPassword').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && password === confirmPassword) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#confirmPassword-error').text('');
        } else if (confirmPassword) {
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
});