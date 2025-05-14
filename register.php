<?php
session_start();
require_once "config/database.php";

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EcoMap</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">EcoMap</a>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create an Account</h2>
            <form id="registration-form" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <small class="text-muted">This will be used for login</small>
                </div>
                <div class="mb-3">
                    <label for="nickname" class="form-label">Nickname</label>
                    <input type="text" class="form-control" id="nickname" name="nickname" required>
                    <small class="text-muted">This will be displayed to other users</small>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">I am a:</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="user">Community User</option>
                        <option value="driver">Garbage Collection Driver</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other / Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">I agree to the Terms and Conditions</label>
                </div>
                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            <div id="register-message" class="mt-3 text-center"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>EcoMap</h5>
                    <p>Making waste management smarter and more efficient.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact Us</h5>
                    <p>Email: info@ecomap.com<br>Phone: (123) 456-7890</p>
                </div>
            </div>
        </div>
    </footer>

    <style>
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    footer {
        margin-top: auto;
    }
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('registration-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const messageDiv = document.getElementById('register-message');
        messageDiv.textContent = '';
        try {
            const response = await fetch('api/register.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                messageDiv.textContent = 'Registration successful! Redirecting to login...';
                messageDiv.classList.remove('text-danger');
                messageDiv.classList.add('text-success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                messageDiv.textContent = data.message;
                messageDiv.classList.remove('text-success');
                messageDiv.classList.add('text-danger');
            }
        } catch (error) {
            messageDiv.textContent = 'An error occurred during registration. Please try again.';
            messageDiv.classList.remove('text-success');
            messageDiv.classList.add('text-danger');
        }
    });
    </script>
</body>
</html> 