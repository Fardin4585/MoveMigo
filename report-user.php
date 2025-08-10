<?php
session_start();
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: signin.php');
    exit();
}

// Get reporter and reported user info from URL parameters
$reporter_id = $_SESSION['user_id'];
$reporter_type = $_SESSION['user_type'];
$reported_user_id = $_GET['reported_user_id'] ?? null;
$reported_user_type = $_GET['reported_user_type'] ?? null;

// If no reported user info provided, redirect back
if (!$reported_user_id || !$reported_user_type) {
    header('Location: tenant-dashboard.php');
    exit();
}

// Get reporter details
$database = new Database();
$pdo = $database->getConnection();

$reporter_table = $reporter_type === 'tenant' ? 'tenants' : 'homeowners';
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM {$reporter_table} WHERE id = ?");
$stmt->execute([$reporter_id]);
$reporter = $stmt->fetch(PDO::FETCH_ASSOC);

// Get reported user details
$reported_table = $reported_user_type === 'tenant' ? 'tenants' : 'homeowners';
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM {$reported_table} WHERE id = ?");
$stmt->execute([$reported_user_id]);
$reported_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO reports 
        (reporter_id, reported_user_id, reported_user_type, reason) 
        VALUES (:reporter_id, :reported_user_id, :reported_user_type, :reason)");
    $stmt->execute([
        ':reporter_id' => $reporter_id,
        ':reported_user_id' => $reported_user_id,
        ':reported_user_type' => $reported_user_type,
        ':reason' => $_POST['reason']
    ]);
    $message = "Report submitted successfully.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Report User - MoveMigo</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .report-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .report-info h3 { margin-top: 0; color: #333; }
        .info-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .info-label { font-weight: bold; color: #555; }
        .info-value { color: #333; }
        label { display: block; margin-top: 20px; font-weight: bold; color: #333; }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; resize: vertical; min-height: 100px; }
        .btn-container { text-align: center; margin-top: 30px; }
        button { padding: 12px 24px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #c82333; }
        .btn-back { background: #6c757d; margin-right: 10px; }
        .btn-back:hover { background: #5a6268; }
        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Report a User</h1>
        <p>Please provide details about the issue you're reporting</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="report-info">
        <h3>Report Details</h3>
        <div class="info-row">
            <span class="info-label">Reporter:</span>
            <span class="info-value"><?php echo htmlspecialchars($reporter['first_name'] . ' ' . $reporter['last_name']); ?> (<?php echo ucfirst($reporter_type); ?>)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Reporter Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($reporter['email']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Reported User:</span>
            <span class="info-value"><?php echo htmlspecialchars($reported_user['first_name'] . ' ' . $reported_user['last_name']); ?> (<?php echo ucfirst($reported_user_type); ?>)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Reported User Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($reported_user['email']); ?></span>
        </div>
    </div>

    <form method="post">
        <label for="reason">Reason for Report:</label>
        <textarea name="reason" id="reason" placeholder="Please describe the issue or reason for reporting this user..." required></textarea>
        
        <div class="btn-container">
            <a href="tenant-dashboard.php" class="btn-back" style="text-decoration: none; display: inline-block; padding: 12px 24px; background: #6c757d; color: white; border-radius: 6px;">Back to Dashboard</a>
            <button type="submit">Submit Report</button>
        </div>
    </form>
</body>
</html>
