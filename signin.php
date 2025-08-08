<?php
session_start();
require_once 'includes/Auth.php';

$auth = new Auth();
$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $user_type = $_POST['user_type'] ?? '';

        if (empty($email) || empty($password) || empty($user_type)) {
            $error_message = 'Please fill in all fields';
        } else {
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                // Check if user type matches
                if ($result['user']['user_type'] === $user_type) {
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['user_type'] = $result['user']['user_type'];
                    $_SESSION['user_name'] = $result['user']['first_name'] . ' ' . $result['user']['last_name'];
                    $_SESSION['session_token'] = $result['session_token'];
                    
                    // Redirect based on user type
                    if ($user_type === 'tenant') {
                        header('Location: tenant-dashboard.php');
                    } else {
                        header('Location: homeowner-dashboard.php');
                    }
                    exit();
                } else {
                    $error_message = 'Invalid user type for this account';
                }
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
  <title>Sign In - MoveMigo</title>
  <link rel="stylesheet" href="signin.css">
</head>
<body>
  <img src="logo.png" alt="Logo" class="logo-top-right circular-logo">
  <div class="container">
    <div class="signin-card">
      <div class="header">
        <h1 style="letter-spacing: 2px; font-weight: bold;">MoveMigo</h1>
        <p style="font-size: 1.1em; color: #666; margin-bottom: 5px;">your best friend while moving</p>
        <p>Sign In to Continue</p>
      </div>
      
      <?php if ($error_message): ?>
        <div class="error-message" style="color: red; text-align: center; margin: 10px 0; padding: 10px; background: #ffe6e6; border-radius: 5px;"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if ($success_message): ?>
        <div class="success-message" style="color: green; text-align: center; margin: 10px 0; padding: 10px; background: #e6ffe6; border-radius: 5px;"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <div class="role-selection">
        <div class="role-option" data-role="tenant">
          <div class="role-icon">
            <img src="Searching for a Home.png" alt="Tenant">
          </div>
          <div class="role-content">
            <h3>Tenant</h3>
            <p>Select if you're looking for a house</p>
          </div>
          <div class="checkmark">✓</div>
        </div>
        <div class="role-option" data-role="homeowner">
          <div class="role-icon">
            <img src="Vintage Property Owner Logo.png" alt="Homeowner">
          </div>
          <div class="role-content">
            <h3>Homeowner</h3>
            <p>Select if you're listing your home</p>
          </div>
          <div class="checkmark">✓</div>
        </div>
      </div>
      <form class="signin-form" id="signinForm" method="POST" action="">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="user_type" id="selectedUserType" value="">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="name@abcd.com" required>
          <div class="error-message" id="emailError"></div>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-input">
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="toggle-password" id="togglePassword">👁</button>
          </div>
          <div class="error-message" id="passwordError"></div>
        </div>
        <button type="submit" class="signin-btn">Sign In</button>
      </form>
      <div class="links">
        <a href="signup-tenant.php" class="signup-link">Sign Up as Tenant</a>
        <a href="signup-homeowner.php" class="signup-link">Sign Up as Homeowner</a>
        <a href="#" class="forgot-password">Forgot Password?</a>
      </div>
    </div>
  </div>
  <script src="signin.js?v=<?php echo time(); ?>"></script>
</body>
</html> 