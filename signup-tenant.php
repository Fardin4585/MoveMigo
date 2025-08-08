<?php
session_start();
require_once 'includes/Auth.php';

$auth = new Auth();
$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $user_type = 'tenant'; // Fixed for tenant signup

        // Validation
        if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
            $error_message = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long';
        } else {
            $result = $auth->register($email, $password, $user_type, $first_name, $last_name, $phone);
            
            if ($result['success']) {
                $success_message = 'Registration successful! You can now sign in.';
                // Clear form data on success
                $_POST = array();
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up as Tenant - MoveMigo</title>
    <link rel="stylesheet" href="signin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #232946 !important;
        }

        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            margin-top: 60px;
        }

        .logo-top-right {
            position: absolute;
            top: 32px;
            right: 48px;
            width: 80px;
            height: auto;
            z-index: 10;
            border-radius: 50%;
            padding: 8px;
            background: none;
            border: none;
            box-shadow: none;
        }
        .tenant-logo {
            display: block;
            margin: 0 auto 20px auto;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            object-fit: cover;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .role-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-display {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .signup-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .signup-btn:hover {
            transform: translateY(-2px);
        }

        .signup-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: #333;
        }

        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <img src="logo.png" alt="Logo" class="logo-top-right circular-logo">
    <div class="signup-container">
        <img src="Searching for a Home.png" alt="Tenant Logo" class="tenant-logo">

        <div class="header">
            <h1>Join MoveMigo</h1>
            <p>Create your tenant account</p>
            <div class="role-badge">Tenant</div>
        </div>

        <?php if ($error_message): ?>
            <div class="error-display"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form id="signupForm" method="POST" action="">
            <input type="hidden" name="action" value="register">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    <div class="error-message" id="firstNameError"></div>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    <div class="error-message" id="lastNameError"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <div class="error-message" id="emailError"></div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                <div class="error-message" id="phoneError"></div>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message" id="passwordError"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div class="error-message" id="confirmPasswordError"></div>
            </div>

            <button type="submit" class="signup-btn" id="signupBtn">Create Account</button>
        </form>

        <div class="links">
            <p>Already have an account? <a href="signin.php">Sign In</a></p>
            <a href="signin.php" class="back-link">← Back to Sign In</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const signupBtn = document.getElementById('signupBtn');

            // Email validation
            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Real-time validation
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');

            emailInput.addEventListener('input', function() {
                const email = emailInput.value.trim();
                if (email === '') {
                    emailError.style.display = 'none';
                } else if (!validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            });

            // Password validation
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordError = document.getElementById('passwordError');
            const confirmPasswordError = document.getElementById('confirmPasswordError');

            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                if (password === '') {
                    passwordError.style.display = 'none';
                } else if (password.length < 6) {
                    passwordError.textContent = 'Password must be at least 6 characters';
                    passwordError.style.display = 'block';
                } else {
                    passwordError.style.display = 'none';
                }
            });

            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                if (confirmPassword === '') {
                    confirmPasswordError.style.display = 'none';
                } else if (password !== confirmPassword) {
                    confirmPasswordError.textContent = 'Passwords do not match';
                    confirmPasswordError.style.display = 'block';
                } else {
                    confirmPasswordError.style.display = 'none';
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = emailInput.value.trim();
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();

                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(error => {
                    error.style.display = 'none';
                });

                let isValid = true;

                // Validate required fields
                if (!firstName) {
                    document.getElementById('firstNameError').textContent = 'First name is required';
                    document.getElementById('firstNameError').style.display = 'block';
                    isValid = false;
                }

                if (!lastName) {
                    document.getElementById('lastNameError').textContent = 'Last name is required';
                    document.getElementById('lastNameError').style.display = 'block';
                    isValid = false;
                }

                if (!email) {
                    emailError.textContent = 'Email is required';
                    emailError.style.display = 'block';
                    isValid = false;
                } else if (!validateEmail(email)) {
                    emailError.textContent = 'Please enter a valid email address';
                    emailError.style.display = 'block';
                    isValid = false;
                }

                if (!password) {
                    passwordError.textContent = 'Password is required';
                    passwordError.style.display = 'block';
                    isValid = false;
                } else if (password.length < 6) {
                    passwordError.textContent = 'Password must be at least 6 characters';
                    passwordError.style.display = 'block';
                    isValid = false;
                }

                if (!confirmPassword) {
                    confirmPasswordError.textContent = 'Please confirm your password';
                    confirmPasswordError.style.display = 'block';
                    isValid = false;
                } else if (password !== confirmPassword) {
                    confirmPasswordError.textContent = 'Passwords do not match';
                    confirmPasswordError.style.display = 'block';
                    isValid = false;
                }

                if (isValid) {
                    // Show loading state
                    signupBtn.textContent = 'Creating Account...';
                    signupBtn.disabled = true;

                    // Submit the form
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 