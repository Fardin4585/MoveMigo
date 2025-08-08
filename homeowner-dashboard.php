<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/HomeManager.php';
require_once 'includes/Messaging.php';

// Check if user is logged in and is a homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'homeowner') {
    header('Location: signin.php');
    exit();
}

$auth = new Auth();
$user = $auth->validateSession($_SESSION['session_token'] ?? '');

if (!$user || $user['user_type'] !== 'homeowner') {
    session_destroy();
    header('Location: signin.php');
    exit();
}

$homeManager = new HomeManager();
$homeowner_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_home':
                $home_details = [
                    'home_name' => $_POST['home_name'] ?? '',
                    'num_of_bedrooms' => $_POST['num_of_bedrooms'] ?? 1,
                    'washrooms' => $_POST['washrooms'] ?? 1,
                    'rent_monthly' => $_POST['rent_monthly'] ?? 0,
                    'utility_bills' => $_POST['utility_bills'] ?? 0,
                    'facilities' => implode(',', $_POST['facilities'] ?? []),
                    'family_bachelor_status' => $_POST['family_bachelor_status'] ?? 'both',
                    'address' => $_POST['address'] ?? '',
                    'city' => $_POST['city'] ?? '',
                    'state' => $_POST['state'] ?? '',
                    'zip_code' => $_POST['zip_code'] ?? '',
                    'description' => $_POST['description'] ?? ''
                ];
                
                $result = $homeManager->addHome($homeowner_id, $home_details);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
                break;

            case 'delete_home':
                $home_id = $_POST['home_id'] ?? 0;
                $result = $homeManager->deleteHome($home_id, $homeowner_id);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
                break;

            case 'toggle_availability':
                $home_id = $_POST['home_id'] ?? 0;
                $result = $homeManager->toggleHomeAvailability($home_id, $homeowner_id);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get homeowner's homes
$homes = $homeManager->getHomesByHomeowner($homeowner_id);

// Initialize messaging
$messaging = new Messaging();
$conversations = $messaging->getConversations($user['user_id'], $user['user_type']);
$unread_count = $messaging->getUnreadCount($user['user_id'], $user['user_type']);

// Available facilities
$all_facilities = ['wifi', 'water', 'gas', 'parking', 'furnished', 'AC'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeowner Dashboard - MoveMigo</title>
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
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            background: var(--bg);
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--header-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.2rem 2vw 0.2rem 2vw;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: none;
            margin: 0;
            min-height: 56px;
        }
        .navbar .logo {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0;
        }
        .navbar .logo-img-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
        }
        .navbar .logo-img-circle img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        .logo-title {
            font-weight: bold;
            font-size: 1.15rem;
            color: #fff;
            margin-bottom: 0.1rem;
        }
        .logo-subtitle {
            font-size: 0.85rem;
            color: #b8c1ec;
            margin-bottom: 0;
        }
        .navbar .profile {
            position: relative;
            margin-left: auto;
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 8px;
            min-width: 150px;
            overflow: hidden;
        }
        .profile-dropdown a {
            display: block;
            padding: 12px 18px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }
        .profile-dropdown a:hover {
            background: #f1f3f6;
        }
        .profile.open .profile-dropdown {
            display: block;
        }
        .profile-info {
            padding: 15px 18px;
            border-bottom: 1px solid #eee;
        }
        .profile-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
        .profile-email {
            color: #666;
            font-size: 0.85rem;
        }
        .profile-divider {
            height: 1px;
            background: #eee;
            margin: 5px 0;
        }
        .welcome-section {
            text-align: center;
            margin: 2rem 0 1rem 0;
            padding: 1rem;
        }
        .welcome-section h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .welcome-section p {
            color: #666;
            font-size: 1rem;
            margin: 0;
        }
        .dashboard-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem 2rem 1rem;
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
            margin: 2rem 0 1rem 0;
            text-align: center;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .property-form {
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 12px;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .property-form label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
            display: block;
        }
        .property-form input[type="text"],
        .property-form input[type="number"],
        .property-form select,
        .property-form textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1.5px solid #e1e5e9;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .property-form textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .property-form .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .property-form .facilities-list label {
            font-weight: 400;
            color: #444;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .property-form input[type="checkbox"] {
            margin: 0;
        }
        .property-form .form-actions {
            display: flex;
            gap: 1rem;
        }
        .property-form button {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .property-form button[type="button"] {
            background: #e0e0e0;
            color: #333;
        }
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .property-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.12s;
        }
        .property-card:hover {
            transform: translateY(-4px) scale(1.01);
        }
        .property-info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .property-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        .property-meta {
            font-size: 0.98rem;
            color: #555;
        }
        .property-facilities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            font-size: 0.92rem;
            color: var(--secondary);
        }
        .property-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        .property-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .property-actions button {
            flex: 1;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 0.7rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-edit { background: var(--secondary); color: #fff; }
        .btn-delete { background: var(--danger); color: #fff; }
        .btn-toggle { background: var(--accent); color: #333; }

        /* Messaging Section Styles */
        .messaging-section {
            margin-top: 2rem;
        }

        .no-messages {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-messages h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-messages p {
            color: #999;
        }

        .conversations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .conversation-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .conversation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .conversation-card.unread {
            border-left: 4px solid var(--primary);
            background: #f8f9ff;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .conversation-name {
            font-weight: bold;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .unread-indicator {
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .conversation-time {
            font-size: 0.8rem;
            color: #666;
        }

        .conversation-preview {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .conversation-property {
            font-size: 0.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        @media (max-width: 800px) {
            .navbar { flex-direction: column; align-items: stretch; }
            .form-row { flex-direction: column; }
        }
        @media (max-width: 600px) {
            .dashboard-main { padding: 0 0.2rem; }
            .property-card { font-size: 0.97rem; }
            .navbar { padding: 0.5rem 0.5rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo" style="flex-direction: row; align-items: center; gap: 1rem;">
            <div class="logo-img-circle">
                <img src="logo.png" alt="MoveMigo Logo">
            </div>
            <div class="logo-texts" style="display: flex; flex-direction: column; align-items: flex-start;">
                <span class="logo-title">MoveMigo</span>
                <span class="logo-subtitle">your best friend while moving</span>
            </div>
        </div>
        <div class="profile" id="profileMenu">
            <div class="profile-icon" onclick="toggleProfileMenu()" style="padding:0; background: none;">
                <img src="Vintage Property Owner Logo.png" alt="User Photo" style="width:40px; height:40px; border-radius:50%; object-fit:cover; display:block;">
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="profile-divider"></div>
                <a href="messages.php"><i class="fas fa-comments"></i> Messages</a>
                <a href="#" onclick="openSettings()"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    <main class="dashboard-main">
        <div class="welcome-section">
            <h2>Hello, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
            <p>Welcome to your homeowner dashboard. Manage your properties below.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="section-title">Add New Property</div>
        <form class="property-form" id="propertyForm" method="POST">
            <input type="hidden" name="action" value="add_home">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="home_name">Property Name *</label>
                    <input type="text" id="home_name" name="home_name" required>
                </div>
                <div class="form-group">
                    <label for="family_bachelor_status">Tenant Type</label>
                    <select id="family_bachelor_status" name="family_bachelor_status">
                        <option value="both">Both Family & Bachelor</option>
                        <option value="family">Family Only</option>
                        <option value="bachelor">Bachelor Only</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="num_of_bedrooms">Number of Bedrooms *</label>
                    <input type="number" id="num_of_bedrooms" name="num_of_bedrooms" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="washrooms">Number of Washrooms *</label>
                    <input type="number" id="washrooms" name="washrooms" min="1" value="1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="rent_monthly">Monthly Rent *</label>
                    <input type="number" id="rent_monthly" name="rent_monthly" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="utility_bills">Utility Bills (Monthly)</label>
                    <input type="number" id="utility_bills" name="utility_bills" min="0" step="0.01" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state">
                </div>
                <div class="form-group">
                    <label for="zip_code">ZIP Code</label>
                    <input type="text" id="zip_code" name="zip_code">
                </div>
            </div>

            <div class="form-group">
                <label>Facilities</label>
                <div class="facilities-list">
                    <?php foreach ($all_facilities as $facility): ?>
                        <label>
                            <input type="checkbox" name="facilities[]" value="<?php echo $facility; ?>">
                            <?php echo ucfirst($facility); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe your property..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit">Add Property</button>
                <button type="button" onclick="resetForm()">Reset</button>
            </div>
        </form>

        <div class="section-title">Your Properties (<?php echo count($homes); ?>)</div>
        <div class="property-grid" id="listingGrid">
            <?php if (empty($homes)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-home" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No properties added yet. Add your first property above!</p>
                </div>
            <?php else: ?>
                <?php foreach ($homes as $home): ?>
                    <div class="property-card">
                        <div class="property-info">
                            <div class="property-title"><?php echo htmlspecialchars($home['home_name']); ?></div>
                            <div class="property-meta">
                                <span><i class="fas fa-bed"></i> <?php echo $home['num_of_bedrooms']; ?> Bedrooms</span>
                                <span style="margin-left:10px;"><i class="fas fa-bath"></i> <?php echo $home['washrooms']; ?> Washrooms</span>
                            </div>
                            <div class="property-meta">
                                <span><i class="fas fa-dollar-sign"></i> <?php echo number_format($home['rent_monthly']); ?>/mo</span>
                                <?php if ($home['utility_bills'] > 0): ?>
                                    <span style="margin-left:10px;"><i class="fas fa-bolt"></i> Utilities: $<?php echo number_format($home['utility_bills']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($home['facilities']): ?>
                                <div class="property-facilities">
                                    <?php 
                                    $facilities = explode(',', $home['facilities']);
                                    foreach ($facilities as $facility): 
                                        if (trim($facility)): ?>
                                            <span><i class="fas fa-check"></i> <?php echo ucfirst(trim($facility)); ?></span>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="property-status <?php echo $home['is_available'] ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo $home['is_available'] ? 'Available' : 'Not Available'; ?>
                            </div>
                            <div class="property-actions">
                                <button class="btn-toggle" onclick="toggleAvailability(<?php echo $home['home_id']; ?>)">
                                    <i class="fas fa-toggle-on"></i> Toggle
                                </button>
                                <button class="btn-edit" onclick="editProperty(<?php echo $home['home_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="deleteProperty(<?php echo $home['home_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Messaging Section -->
        <div class="section-title">
            Messages 
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge" style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 8px; font-size: 0.8rem; margin-left: 10px;"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="messaging-section">
            <?php if (empty($conversations)): ?>
                <div class="no-messages">
                    <i class="fas fa-comments" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No messages yet</h3>
                    <p>When tenants message you about your properties, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="conversations-grid">
                    <?php foreach ($conversations as $conversation): ?>
                        <div class="conversation-card <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>" 
                             onclick="openConversation(<?php echo $conversation['id']; ?>, '<?php echo htmlspecialchars($conversation['other_user_name']); ?>')">
                            <div class="conversation-header">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($conversation['other_user_name']); ?>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="unread-indicator"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-time">
                                    <?php echo date('M j', strtotime($conversation['last_message_at'])); ?>
                                </div>
                            </div>
                            <div class="conversation-preview">
                                <?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'No messages yet', 0, 60)); ?>
                            </div>
                            <?php if ($conversation['home_name']): ?>
                                <div class="conversation-property">
                                    <i class="fas fa-home"></i> <?php echo htmlspecialchars($conversation['home_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Profile dropdown
        function toggleProfileMenu() {
            document.getElementById('profileMenu').classList.toggle('open');
        }
        window.onclick = function(e) {
            if (!e.target.closest('.profile')) {
                document.getElementById('profileMenu').classList.remove('open');
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('propertyForm').reset();
        }

        // Toggle availability
        function toggleAvailability(homeId) {
            if (confirm('Are you sure you want to toggle the availability of this property?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_availability">
                    <input type="hidden" name="home_id" value="${homeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete property
        function deleteProperty(homeId) {
            if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_home">
                    <input type="hidden" name="home_id" value="${homeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Edit property (placeholder for future implementation)
        function editProperty(homeId) {
            alert('Edit functionality will be implemented soon!');
        }

        // Settings placeholder
        function openSettings() {
            alert('Settings page coming soon!');
        }

        // Open conversation in messages page
        function openConversation(conversationId, userName) {
            // Store conversation info in sessionStorage
            sessionStorage.setItem('openConversationId', conversationId);
            sessionStorage.setItem('openConversationUser', userName);
            
            // Redirect to messages page
            window.location.href = 'messages.php';
        }
    </script>
</body>
</html> 