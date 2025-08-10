<?php
session_start();
require_once 'config/database.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// If already logged in and is admin
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin-dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch from admins table
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Set session
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['admin_name'] = $admin['name'] ?? 'Administrator';

        header('Location: admin-dashboard.php');
        exit();
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MoveMigo</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .admin-login-container {
            background: #1a1d2b;
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            max-width: 400px;
            width: 100%;
            margin: 2rem auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
            border-radius: 16px;
            background: #fff;
            padding: 8px;
            box-shadow: 0 4px 16px rgba(31, 38, 135, 0.12);
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            color: #e0e6f7;
            margin-bottom: 0.5rem;
            letter-spacing: 0.02em;
        }

        .admin-subtitle {
            font-size: 1rem;
            color: #a5d8ff;
            opacity: 0.8;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: #e0e6f7;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 0.2rem;
        }

        .form-group input {
            padding: 1rem;
            border: 2px solid #2d3748;
            border-radius: 0.7rem;
            background: #232946;
            color: #e0e6f7;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: #718096;
        }

        .admin-login-btn {
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.7rem;
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .admin-login-btn:hover {
            background: linear-gradient(90deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(31, 38, 135, 0.2);
        }

        .admin-login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(245, 101, 101, 0.1);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: #f56565;
            padding: 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .back-to-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-home a {
            color: #a5d8ff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-to-home a:hover {
            color: #e0e6f7;
        }

        .security-notice {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            color: #a5d8ff;
            padding: 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            text-align: center;
            margin-top: 1rem;
        }

        @media (max-width: 600px) {
            .admin-login-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .admin-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="admin-login-container">
            <div class="admin-header">
                <img src="logo.png" alt="MoveMigo Logo" class="admin-logo">
                <h1 class="admin-title">Admin Access</h1>
                <p class="admin-subtitle">Secure administrative login</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="admin-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="Enter your admin email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="admin-login-btn">
                    Sign In to Admin Panel
                </button>
            </form>

            <div class="security-notice">
                <strong>🔒 Secure Access:</strong> This area is restricted to authorized administrators only.
            </div>

            <div class="back-to-home">
                <a href="index.php">← Back to Homepage</a>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-group input');
            const loginBtn = document.querySelector('.admin-login-btn');

            // Add focus effects
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Form validation
            const form = document.querySelector('.admin-form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                if (!email || !password) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                    return;
                }

                // Show loading state
                loginBtn.textContent = 'Signing In...';
                loginBtn.disabled = true;
            });

            // Auto-focus on email field
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
