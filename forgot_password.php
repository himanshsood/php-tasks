<?php
$PAGE_TITLE = "Forgot Password";
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
</style>
<div class="container mt-4">
    <div class="mt-5 justify-content-center">
        <!-- <div class="col-md-6"> -->
        <div class="card">
            <div class="text-center">
                <img src="resources/images/7070629_3293465.jpg" alt="Forgot Password Image" class="img-fluid mb-2" style="max-width: 200px;">
            </div>
            <h2 class="text-center">Forgot Password</h2>
            <p class="mt-3">Enter the email address associated with your account and we'll send you a link to reset your password.</p>
            <form id="forgotPasswordForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-group">
                    <label for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" >
                    <small class="text-danger" id="emailErr"></small>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Continue</button>
                <div id="responseMessage" class="mt-3"></div>
            </form>
            <div class="text-center my-5">
                <a href="#" class="text-primary">Don't have an account? Sign up</a>
            </div>

        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
    $('#forgotPasswordForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting via the browser
        var formData = new FormData(this);
        var isValid = true;

        $('#emailErr').text('');

        var email = $('#email').val().trim();

        if (email === '') {
            $('#emailErr').text('Email is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            $('#emailErr').text('Invalid email format');
            isValid = false;
        }

        if (isValid) {
            $.ajax({
                url: 'send_otp.php', // Replace with your actual AJAX endpoint
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json', // Add this line
                success: function(response) {
                    console.log('Raw response:', response);
                    console.log('Response type:', typeof response);
                    
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch(e) {
                            console.log('JSON parse error:', e);
                            console.log('Response content:', response);
                        }
                    }
                    
                    console.log('Parsed response:', response);
                    
                    if (response.message) {
                        $('#responseMessage').html('<div class="alert alert-success"> ' + response.message + '</div>');
                        setTimeout(function() {
                            window.location.href = 'verify_password_reset_otp.php';  // Fixed: Use correct OTP verification page
                        }, 1500); 
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

    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>

<?php include_once(__DIR__ . "/footer.php"); ?>