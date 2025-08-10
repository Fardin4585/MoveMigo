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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report User - MoveMigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #17a2b8;
            --accent: #ffc107;
            --danger: #dc3545;
            --success: #28a745;
            --bg: #f5f5f5;
            --card-bg: #fff;
            --shadow: 0 2px 10px rgba(0,0,0,0.08);
            --header-bg: #232946;
            --text-primary: #333;
            --text-secondary: #666;
            --border: #e1e5e9;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .navbar {
            background: var(--header-bg);
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .logo-text {
            color: white;
        }
        
        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        
        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
        }
        
        .back-btn {
            background: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-btn:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }
        
        .report-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
        }
        
        .card-header h2 {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .card-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .report-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }
        
        .report-info h3 {
            color: var(--text-primary);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .form-group textarea::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }
        
        .btn-container {
            text-align: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            border: 1px solid;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .navbar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-container {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                margin: 0;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="logo">
                <img src="logo.png" alt="MoveMigo Logo">
                <div class="logo-text">
                    <h1>MoveMigo</h1>
                    <p>your best friend while moving</p>
                </div>
            </div>
            <a href="tenant-dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </nav>

        <!-- Main Report Card -->
        <div class="report-card">
            <div class="card-header">
                <h2><i class="fas fa-flag"></i> Report a User</h2>
                <p>Please provide detailed information about the issue you're reporting</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Report Information Section -->
            <div class="report-info">
                <h3><i class="fas fa-info-circle"></i> Report Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Reporter</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($reporter['first_name'] . ' ' . $reporter['last_name']); ?>
                            <span style="color: var(--secondary); font-size: 0.9rem;">
                                (<?php echo ucfirst($reporter_type); ?>)
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Reporter Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($reporter['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Reported User</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($reported_user['first_name'] . ' ' . $reported_user['last_name']); ?>
                            <span style="color: var(--danger); font-size: 0.9rem;">
                                (<?php echo ucfirst($reported_user_type); ?>)
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Reported User Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($reported_user['email']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Report Form -->
            <form method="post">
                <div class="form-section">
                    <div class="form-group">
                        <label for="reason">
                            <i class="fas fa-exclamation-triangle"></i>
                            Reason for Report
                        </label>
                        <textarea 
                            name="reason" 
                            id="reason" 
                            placeholder="Please describe the issue or reason for reporting this user in detail. Include any relevant information that will help our admin team understand the situation..."
                            required
                        ></textarea>
                    </div>
                </div>
                
                <div class="btn-container">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-paper-plane"></i>
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
