<?php
$PAGE_TITLE = "Reset Password";
include_once(__DIR__ . "/header.php");

// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<style>
    .container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .card {
        width: 500px;
        margin: auto;
        padding: 0 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        background-color: white;
    }

    .card-title {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .btn-primary {
        background-color: #007bff;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 2rem;
        font-size: 1rem;
    }

    .text-primary {
        color: #007bff !important;
    }

    .text-primary:hover {
        text-decoration: underline;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .text-center {
        margin-top: 1rem;
    }

    .text-danger {
        color: #dc3545;
    }
</style>

<div class="container mt-4">
    <div class="mt-5 justify-content-center">
        <div class="card">
            <div class="text-center">
                <img src="resources/images/13246824_5191079.jpg" alt="Forgot Password Image" class="img-fluid mb-2" style="max-width: 200px;">
            </div>
            <h2 class="text-center">Reset Password</h2>
            <p class="mt-3">Enter a new and strong password.</p>
            <form id="forgotPasswordForm" class="mb-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" id="email" name="email" value="<?php if (isset($_GET['email'])) {
                    echo htmlspecialchars($_GET['email']);
                } ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="new_password" name="password" required>
                    <small class="text-danger" id="passwordErr"></small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <small class="text-danger" id="confirmPasswordErr"></small>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Password</button>
                <div id="responseMessage" class="mt-3"></div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#forgotPasswordForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting via the browser
            var formData = new FormData(this);
            var isValid = true;

            $('#passwordErr').text('');
            $('#confirmPasswordErr').text('');
            $('#responseMessage').html('');

            var password = $('#new_password').val().trim();
            var confirmPassword = $('#confirm_password').val().trim();

            if (password === '') {
                $('#passwordErr').text('Password is required.');
                isValid = false;
            } else if (password.length < 8) {
                $('#passwordErr').text('Password must be at least 8 characters long.');
                isValid = false;
            }

            if (confirmPassword === '') {
                $('#confirmPasswordErr').text('Please confirm your password.');
                isValid = false;
            } else if (password !== confirmPassword) {
                $('#confirmPasswordErr').text('Passwords do not match.');
                isValid = false;
            }

            if (isValid) {
                $.ajax({
                    url: 'update_password.php', // Replace with your actual AJAX endpoint
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                            setTimeout(function() {
                                window.location.href = 'login.php'; // Redirect to login page or any other page
                            }, 2000); // 2000 milliseconds = 2 seconds
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                    error: function() {
                        $('#responseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                    }
                });
            }
        });
    });
</script>

<?php
include_once(__DIR__ . "/footer.php");
?>
