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

// Handle deleting reports
if (isset($_POST['delete_report'])) {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = :id");
    $stmt->execute([':id' => $_POST['report_id']]);
    
    // Redirect to refresh the page after deletion
    header('Location: admin-dashboard.php');
    exit();
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

// Get statistics
$total_reports = count($reports);
$pending_reports = count(array_filter($reports, function($r) { return $r['status'] === 'pending'; }));
$valid_reports = count(array_filter($reports, function($r) { return $r['status'] === 'valid'; }));
$invalid_reports = count(array_filter($reports, function($r) { return $r['status'] === 'invalid'; }));
$blocked_users = count(array_filter($reports, function($r) { return $r['is_blocked'] == 1; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MoveMigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #17a2b8;
            --accent: #ffc107;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
            --bg: #f4f6f9;
            --card-bg: #fff;
            --shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            --header-bg: #232946;
            --sidebar-bg: #2c3e50;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --border: #dee2e6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            color: var(--accent);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--accent);
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
        }
        
        .page-header h1 {
            color: var(--text-primary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: center;
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.total { border-left-color: var(--primary); }
        .stat-card.pending { border-left-color: var(--warning); }
        .stat-card.valid { border-left-color: var(--success); }
        .stat-card.invalid { border-left-color: var(--danger); }
        .stat-card.blocked { border-left-color: var(--info); }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .total .stat-icon { color: var(--primary); }
        .pending .stat-icon { color: var(--warning); }
        .valid .stat-icon { color: var(--success); }
        .invalid .stat-icon { color: var(--danger); }
        .blocked .stat-icon { color: var(--info); }
        
        .total .stat-number { color: var(--primary); }
        .pending .stat-number { color: var(--warning); }
        .valid .stat-number { color: var(--success); }
        .invalid .stat-number { color: var(--danger); }
        .blocked .stat-number { color: var(--info); }
        
        /* Reports Table */
        .reports-section {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .section-header {
            background: var(--light);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .section-header h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .reports-table th {
            background: var(--light);
            color: var(--text-primary);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--border);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }
        
        .reports-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        
        .reports-table tr:hover {
            background: rgba(0,123,255,0.05);
        }
        
        .reports-table tr.blocked-user {
            background: rgba(220,53,69,0.1);
        }
        
        .reports-table tr.blocked-user:hover {
            background: rgba(220,53,69,0.15);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: rgba(255,193,7,0.2);
            color: #856404;
        }
        
        .status-valid {
            background: rgba(40,167,69,0.2);
            color: #155724;
        }
        
        .status-invalid {
            background: rgba(220,53,69,0.2);
            color: #721c24;
        }
        
        /* Action Buttons */
        .actions-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-update {
            background: var(--primary);
            color: white;
        }
        
        .btn-update:hover {
            background: #0056b3;
        }
        
        .btn-block {
            background: var(--danger);
            color: white;
        }
        
        .btn-block:hover {
            background: #c82333;
        }
        
        .btn-unblock {
            background: var(--success);
            color: white;
        }
        
        .btn-unblock:hover {
            background: #1e7e34;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        /* Forms */
        .form-inline {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .form-inline select {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .form-inline input[type="hidden"] {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .reports-table {
                font-size: 0.8rem;
            }
            
            .reports-table th,
            .reports-table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        /* Toggle Sidebar Button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        @media (max-width: 1024px) {
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
                <p>MoveMigo Administration</p>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    Dashboard
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-flag"></i>
                    Reports
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i>
                    Users
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-home"></i>
                    Properties
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Admin Dashboard
                </h1>
                <p>Monitor and manage user reports, user accounts, and system activities</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stat-number"><?= $total_reports ?></div>
                    <div class="stat-label">Total Reports</div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $pending_reports ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                
                <div class="stat-card valid">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $valid_reports ?></div>
                    <div class="stat-label">Valid</div>
                </div>
                
                <div class="stat-card invalid">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?= $invalid_reports ?></div>
                    <div class="stat-label">Invalid</div>
                </div>
                
                <div class="stat-card blocked">
                    <div class="stat-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-number"><?= $blocked_users ?></div>
                    <div class="stat-label">Blocked Users</div>
                </div>
            </div>
            
            <!-- Reports Section -->
            <div class="reports-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-flag"></i>
                        User Reports Management
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="reports-table">
                        <thead>
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
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr class="<?= ($report['is_blocked'] == 1) ? 'blocked-user' : '' ?>">
                                <td><strong>#<?= $report['id'] ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($report['reporter_name'] ?? 'Unknown') ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($report['reporter_email'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $report['reporter_type'] ?>">
                                        <?= ucfirst($report['reporter_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($report['reported_name'] ?? 'Unknown') ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($report['reported_email'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $report['reported_user_type'] ?>">
                                        <?= ucfirst($report['reported_user_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 200px; word-wrap: break-word;">
                                        <?= htmlspecialchars($report['reason']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $report['status'] ?>">
                                        <?= ucfirst($report['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-container">
                                        <?php if ($report['status'] === 'pending'): ?>
                                            <form method="post" class="form-inline">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <select name="status">
                                                    <option value="valid">Valid</option>
                                                    <option value="invalid">Invalid</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-update">
                                                    <i class="fas fa-check"></i> Update
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($report['status'] === 'valid'): ?>
                                            <form method="post" class="form-inline">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <select name="status">
                                                    <option value="valid" selected>Valid</option>
                                                    <option value="invalid">Invalid</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-update">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                            </form>
                                            
                                            <?php if ($report['is_blocked'] == 1): ?>
                                                <form method="post" class="form-inline">
                                                    <input type="hidden" name="user_id" value="<?= $report['reported_user_id'] ?>">
                                                    <input type="hidden" name="user_type" value="<?= $report['reported_user_type'] ?>">
                                                    <button type="submit" name="unblock_user" class="btn btn-unblock">
                                                        <i class="fas fa-unlock"></i> Unblock User
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" class="form-inline">
                                                    <input type="hidden" name="user_id" value="<?= $report['reported_user_id'] ?>">
                                                    <input type="hidden" name="user_type" value="<?= $report['reported_user_type'] ?>">
                                                    <button type="submit" name="block_user" class="btn btn-block">
                                                        <i class="fas fa-ban"></i> Block User
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($report['status'] === 'invalid'): ?>
                                            <form method="post" class="form-inline">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <select name="status">
                                                    <option value="invalid" selected>Invalid</option>
                                                    <option value="valid">Valid</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-update">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Delete button for all reports -->
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                            <button type="submit" name="delete_report" class="btn btn-delete" 
                                                    onclick="return confirm('Are you sure you want to delete this report? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>
