<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

$auth = new Auth($pdo);

// Ensure user is logged in & is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: admin_login.php'); // Use your admin login page
    exit();
}

// Handle report status updates
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE reports SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $_POST['status'],
        ':id' => $_POST['report_id']
    ]);
}

// Handle blocking users
if (isset($_POST['block_user'])) {
    $table = $_POST['user_type'] === 'tenant' ? 'tenants' : 'homeowners';
    $stmt = $pdo->prepare("UPDATE {$table} SET blocked = 1 WHERE id = :id");
    $stmt->execute([':id' => $_POST['user_id']]);
}

// Handle unblocking users
if (isset($_POST['unblock_user'])) {
    $table = $_POST['user_type'] === 'tenant' ? 'tenants' : 'homeowners';
    $stmt = $pdo->prepare("UPDATE {$table} SET blocked = 0 WHERE id = :id");
    $stmt->execute([':id' => $_POST['user_id']]);
}

// Fetch all reports with reporter & reported user info
$sql = "SELECT 
            r.*,
            -- Reporter name: from tenants or homeowners
            COALESCE(
                CONCAT(t1.first_name, ' ', t1.last_name),
                CONCAT(h1.first_name, ' ', h1.last_name)
            ) AS reporter_name,

            -- Reporter email: from tenants or homeowners
            COALESCE(t1.email, h1.email) AS reporter_email,

            -- Reporter type
            CASE 
                WHEN t1.id IS NOT NULL THEN 'tenant'
                WHEN h1.id IS NOT NULL THEN 'homeowner'
                ELSE 'unknown'
            END AS reporter_type,

            -- Reported user name: from tenants or homeowners
            COALESCE(
                CONCAT(t2.first_name, ' ', t2.last_name),
                CONCAT(h2.first_name, ' ', h2.last_name)
            ) AS reported_name,

            -- Reported user email: from tenants or homeowners
            COALESCE(t2.email, h2.email) AS reported_email,

            -- Check if reported user is blocked
            COALESCE(t2.blocked, h2.blocked) AS is_blocked

        FROM reports r
        -- Reporter joins
        LEFT JOIN tenants t1 ON r.reporter_id = t1.id
        LEFT JOIN homeowners h1 ON r.reporter_id = h1.id

        -- Reported user joins
        LEFT JOIN tenants t2 ON r.reported_user_type = 'tenant' AND r.reported_user_id = t2.id
        LEFT JOIN homeowners h2 ON r.reported_user_type = 'homeowner' AND r.reported_user_id = h2.id";


$reports = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - MoveMigo</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        form { display: inline; margin-right: 5px; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; }
        .btn-update { background: #007BFF; color: white; }
        .btn-block { background: #dc3545; color: white; }
        .btn-unblock { background: #28a745; color: white; }
        .blocked-user { background-color: #ffe6e6; }
    </style>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <h2>Reports</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Reporter</th>
            <th>Reporter Email</th>
            <th>Reporter Type</th>
            <th>Reported User</th>
            <th>Reported Email</th>
            <th>Reported Type</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($reports as $report): ?>
        <tr class="<?= ($report['is_blocked'] == 1) ? 'blocked-user' : '' ?>">
            <td><?= $report['id'] ?></td>
            <td><?= htmlspecialchars($report['reporter_name'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($report['reporter_email'] ?? 'Unknown') ?></td>
            <td><?= ucfirst($report['reporter_type']) ?></td>
            <td><?= htmlspecialchars($report['reported_name'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($report['reported_email'] ?? 'Unknown') ?></td>
            <td><?= ucfirst($report['reported_user_type']) ?></td>
            <td><?= htmlspecialchars($report['reason']) ?></td>
            <td><?= $report['status'] ?></td>
            <td>
                <?php if ($report['status'] === 'pending'): ?>
                    <form method="post">
                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                        <select name="status">
                            <option value="valid">Valid</option>
                            <option value="invalid">Invalid</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-update">Update</button>
                    </form>
                <?php endif; ?>
                <?php if ($report['status'] === 'valid'): ?>
                    <?php if ($report['is_blocked'] == 1): ?>
                        <!-- User is blocked, show unblock button -->
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $report['reported_user_id'] ?>">
                            <input type="hidden" name="user_type" value="<?= $report['reported_user_type'] ?>">
                            <button type="submit" name="unblock_user" class="btn btn-unblock">Unblock User</button>
                        </form>
                    <?php else: ?>
                        <!-- User is not blocked, show block button -->
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $report['reported_user_id'] ?>">
                            <input type="hidden" name="user_type" value="<?= $report['reported_user_type'] ?>">
                            <button type="submit" name="block_user" class="btn btn-block">Block User</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
