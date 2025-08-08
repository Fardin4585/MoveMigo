<?php
session_start();
require_once 'includes/Auth.php';

$auth = new Auth();

// Logout the user
if (isset($_SESSION['session_token'])) {
    $auth->logout($_SESSION['session_token']);
}

// Destroy all session data
session_destroy();

// Redirect to signin page
header('Location: signin.php');
exit();
?> 