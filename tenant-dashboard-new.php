<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header('Location: signin.php');
    exit();
}

$auth = new Auth();
$user = $auth->validateSession($_SESSION['session_token'] ?? '');

if (!$user || $user['user_type'] !== 'tenant') {
    session_destroy();
    header('Location: signin.php');
    exit();
}

// Database connection
$database = new Database();
$conn = $database->getConnection();

// Get all available homes
$query = "SELECT 
            h.id as home_id,
            hd.home_name,
            hd.num_of_bedrooms,
            hd.washrooms,
            hd.rent_monthly,
            hd.utility_bills,
            hd.facilities,
            hd.family_bachelor_status,
            hd.address,
            hd.city,
            hd.state,
            hd.zip_code,
            hd.description,
            hd.is_available,
            ho.first_name as homeowner_first_name,
            ho.last_name as homeowner_last_name,
            ho.phone as homeowner_phone
          FROM home h
          JOIN home_details hd ON h.id = hd.home_id
          JOIN homeowners ho ON h.homeowner_id = ho.id
          WHERE hd.is_available = 1
          ORDER BY h.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$available_homes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get saved properties for this tenant
$savedQuery = "SELECT 
                h.id as home_id,
                hd.home_name,
                hd.num_of_bedrooms,
                hd.washrooms,
                hd.rent_monthly,
                hd.utility_bills,
                hd.facilities,
                hd.family_bachelor_status,
                hd.address,
                hd.city,
                hd.state,
                hd.zip_code,
                hd.description,
                hd.is_available,
                ho.first_name as homeowner_first_name,
                ho.last_name as homeowner_last_name,
                ho.phone as homeowner_phone,
                sp.saved_at
              FROM saved_properties sp
              JOIN home h ON sp.home_id = h.id
              JOIN home_details hd ON h.id = hd.home_id
              JOIN homeowners ho ON h.homeowner_id = ho.id
              WHERE sp.tenant_id = ?
              ORDER BY sp.saved_at DESC";

$stmt = $conn->prepare($savedQuery);
$stmt->execute([$user['user_id']]);
$saved_homes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get saved home IDs for this tenant
$savedHomeIds = [];
$savedIdsQuery = "SELECT home_id FROM saved_properties WHERE tenant_id = ?";
$stmt = $conn->prepare($savedIdsQuery);
$stmt->execute([$user['user_id']]);
$savedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
$savedHomeIds = $savedIds;

// Process homes for display
$properties = [];
foreach ($available_homes as $home) {
    $facilities = [];
    if ($home['facilities']) {
        $facilities = explode(',', $home['facilities']);
        $facilities = array_map('trim', $facilities);
        $facilities = array_filter($facilities);
    }
    
    $properties[] = [
        'id' => $home['home_id'],
        'name' => $home['home_name'] ?: 'Beautiful Home',
        'location' => $home['address'] ? $home['address'] . ', ' . $home['city'] : $home['city'],
        'rent' => $home['rent_monthly'],
        'rooms' => $home['num_of_bedrooms'],
        'baths' => $home['washrooms'],
        'desc' => $home['description'] ?: 'Beautiful property available for rent.',
        'amenities' => array_map('ucfirst', $facilities),
        'saved' => in_array($home['home_id'], $savedHomeIds),
        'homeowner_name' => $home['homeowner_first_name'] . ' ' . $home['homeowner_last_name'],
        'homeowner_phone' => $home['homeowner_phone'],
        'utility_bills' => $home['utility_bills'],
        'family_bachelor_status' => $home['family_bachelor_status']
    ];
}

// Process saved homes for display
$saved_properties = [];
foreach ($saved_homes as $home) {
    $facilities = [];
    if ($home['facilities']) {
        $facilities = explode(',', $home['facilities']);
        $facilities = array_map('trim', $facilities);
        $facilities = array_filter($facilities);
    }
    
    $saved_properties[] = [
        'id' => $home['home_id'],
        'name' => $home['home_name'] ?: 'Beautiful Home',
        'location' => $home['address'] ? $home['address'] . ', ' . $home['city'] : $home['city'],
        'rent' => $home['rent_monthly'],
        'rooms' => $home['num_of_bedrooms'],
        'baths' => $home['washrooms'],
        'desc' => $home['description'] ?: 'Beautiful property available for rent.',
        'amenities' => array_map('ucfirst', $facilities),
        'saved' => true,
        'homeowner_name' => $home['homeowner_first_name'] . ' ' . $home['homeowner_last_name'],
        'homeowner_phone' => $home['homeowner_phone'],
        'utility_bills' => $home['utility_bills'],
        'family_bachelor_status' => $home['family_bachelor_status'],
        'saved_at' => $home['saved_at']
    ];
}

$all_amenities = ['wifi', 'water', 'gas', 'parking', 'furnished', 'AC'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - MoveMigo</title>
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
        .filters-bar {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .amenities-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .amenity-checkbox {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            font-size: 0.9rem;
            user-select: none;
        }
        .amenity-checkbox input[type="checkbox"] {
            display: none;
        }
        .checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: inline-block;
            position: relative;
            background: white;
            transition: all 0.2s;
        }
        .amenity-checkbox input[type="checkbox"]:checked + .checkbox-custom {
            background: var(--success);
            border-color: var(--success);
        }
        .amenity-checkbox input[type="checkbox"]:checked + .checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .amenity-checkbox:hover .checkbox-custom {
            border-color: var(--success);
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-group label {
            font-weight: 500;
            color: #333;
        }
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .tab-navigation {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            justify-content: center;
        }
        .tab-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--primary);
            color: white;
        }
        .tab-btn:hover {
            background: var(--primary);
            color: white;
        }
        .property-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.12s;
            padding: 1rem;
        }
        .property-card:hover {
            transform: translateY(-2px);
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
        .property-location {
            color: #666;
            font-size: 0.9rem;
        }
        .property-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.5rem 0;
        }
        .property-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--success);
        }
        .property-rooms {
            display: flex;
            gap: 1rem;
            color: #555;
            font-size: 0.9rem;
        }
        .property-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin: 0.5rem 0;
            align-items: center;
        }
        .property-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin: 0.5rem 0;
        }
        .amenity-tag {
            background: var(--secondary);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .property-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .property-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .property-actions button {
            flex: 1;
            border: none;
            border-radius: 6px;
            padding: 0.7rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-contact {
            background: var(--primary);
            color: white;
        }
        .btn-save {
            background: var(--accent);
            color: #333;
        }
        .btn-save.saved {
            background: var(--success);
            color: white;
        }
        .homeowner-info {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        .tenant-status {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .status-family {
            background: #d4edda;
            color: #155724;
        }
        .status-bachelor {
            background: #fff3cd;
            color: #856404;
        }
        .status-both {
            background: #cce5ff;
            color: #004085;
        }
        .no-properties {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .no-properties i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        @media (max-width: 800px) {
            .navbar { flex-direction: column; align-items: stretch; }
            .filters-bar { flex-direction: column; align-items: stretch; }
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
                <img src="Searching for a Home.png" alt="User Photo" style="width:40px; height:40px; border-radius:50%; object-fit:cover; display:block;">
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="#" onclick="openSettings()"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    <main class="dashboard-main">
        <div class="section-title">Available Properties (<?php echo count($properties); ?>)</div>
        
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('available')">Available Homes</button>
            <button class="tab-btn" onclick="switchTab('saved')">Saved Homes (<?php echo count($saved_properties); ?>)</button>
        </div>
        
        <div class="filters-bar">
            <div class="filter-group">
                <label for="priceRange">Price Range:</label>
                <select id="priceRange">
                    <option value="">All Prices</option>
                    <option value="0-1000">$0 - $1,000</option>
                    <option value="1000-2000">$1,000 - $2,000</option>
                    <option value="2000-3000">$2,000 - $3,000</option>
                    <option value="3000+">$3,000+</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="bedrooms">Bedrooms:</label>
                <select id="bedrooms">
                    <option value="">Any</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4+">4+</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="tenantType">Tenant Type:</label>
                <select id="tenantType">
                    <option value="">All Types</option>
                    <option value="family">Family</option>
                    <option value="bachelor">Bachelor</option>
                    <option value="both">Both</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Amenities:</label>
                <div class="amenities-checkboxes">
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="wifi" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        WiFi
                    </label>
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="water" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        Water
                    </label>
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="gas" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        Gas
                    </label>
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="parking" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        Parking
                    </label>
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="furnished" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        Furnished
                    </label>
                    <label class="amenity-checkbox">
                        <input type="checkbox" value="AC" class="amenity-filter">
                        <span class="checkbox-custom"></span>
                        AC
                    </label>
                </div>
            </div>
        </div>

        <!-- Available Properties Section -->
        <div class="property-grid" id="availableProperties">
            <?php if (empty($properties)): ?>
                <div class="no-properties" style="grid-column: 1 / -1;">
                    <i class="fas fa-home"></i>
                    <h3>No properties available</h3>
                    <p>Check back later for new listings!</p>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property-card" data-price="<?php echo $property['rent']; ?>" 
                         data-bedrooms="<?php echo $property['rooms']; ?>" 
                         data-tenant-type="<?php echo $property['family_bachelor_status']; ?>"
                         data-amenities="<?php echo strtolower(implode(',', $property['amenities'])); ?>">
                        <div class="property-info">
                            <div class="property-title"><?php echo htmlspecialchars($property['name']); ?></div>
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($property['location']); ?>
                            </div>
                            <div class="property-meta">
                                <div class="property-price">$<?php echo number_format($property['rent']); ?>/mo</div>
                                <div class="property-rooms">
                                    <span><i class="fas fa-bed"></i> <?php echo $property['rooms']; ?></span>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['baths']; ?></span>
                                </div>
                            </div>
                            <?php if ($property['utility_bills'] > 0): ?>
                                <div style="color: #666; font-size: 0.9rem;">
                                    <i class="fas fa-bolt"></i> Utilities: $<?php echo number_format($property['utility_bills']); ?>/mo
                                </div>
                            <?php endif; ?>
                            <div class="property-tags">
                                <span class="tenant-status status-<?php echo $property['family_bachelor_status']; ?>">
                                    <?php echo ucfirst($property['family_bachelor_status']); ?>
                                </span>
                                <?php if (!empty($property['amenities'])): ?>
                                    <?php foreach ($property['amenities'] as $amenity): ?>
                                        <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="homeowner-info">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($property['homeowner_name']); ?>
                                <?php if ($property['homeowner_phone']): ?>
                                    <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($property['homeowner_phone']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="property-actions">
                                <button class="btn-contact" onclick="contactHomeowner(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-phone"></i> Contact
                                </button>
                                <button class="btn-save <?php echo $property['saved'] ? 'saved' : ''; ?>" 
                                        onclick="toggleSave(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-heart"></i> <?php echo $property['saved'] ? 'Saved' : 'Save'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Saved Properties Section -->
        <div class="property-grid" id="savedProperties" style="display: none;">
            <?php if (empty($saved_properties)): ?>
                <div class="no-properties" style="grid-column: 1 / -1;">
                    <i class="fas fa-heart"></i>
                    <h3>No saved properties</h3>
                    <p>Save properties you like to see them here!</p>
                </div>
            <?php else: ?>
                <?php foreach ($saved_properties as $property): ?>
                    <div class="property-card" data-price="<?php echo $property['rent']; ?>" 
                         data-bedrooms="<?php echo $property['rooms']; ?>" 
                         data-tenant-type="<?php echo $property['family_bachelor_status']; ?>"
                         data-amenities="<?php echo strtolower(implode(',', $property['amenities'])); ?>">
                        <div class="property-info">
                            <div class="property-title"><?php echo htmlspecialchars($property['name']); ?></div>
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($property['location']); ?>
                            </div>
                            <div class="property-meta">
                                <div class="property-price">$<?php echo number_format($property['rent']); ?>/mo</div>
                                <div class="property-rooms">
                                    <span><i class="fas fa-bed"></i> <?php echo $property['rooms']; ?></span>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['baths']; ?></span>
                                </div>
                            </div>
                            <?php if ($property['utility_bills'] > 0): ?>
                                <div style="color: #666; font-size: 0.9rem;">
                                    <i class="fas fa-bolt"></i> Utilities: $<?php echo number_format($property['utility_bills']); ?>/mo
                                </div>
                            <?php endif; ?>
                            <div class="property-tags">
                                <span class="tenant-status status-<?php echo $property['family_bachelor_status']; ?>">
                                    <?php echo ucfirst($property['family_bachelor_status']); ?>
                                </span>
                                <?php if (!empty($property['amenities'])): ?>
                                    <?php foreach ($property['amenities'] as $amenity): ?>
                                        <span class="amenity-tag"><?php echo htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="homeowner-info">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($property['homeowner_name']); ?>
                                <?php if ($property['homeowner_phone']): ?>
                                    <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($property['homeowner_phone']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="property-actions">
                                <button class="btn-contact" onclick="contactHomeowner(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-phone"></i> Contact
                                </button>
                                <button class="btn-save saved" onclick="toggleSave(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-heart"></i> Saved
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

        // Tab functionality
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide property sections
            const availableSection = document.getElementById('availableProperties');
            const savedSection = document.getElementById('savedProperties');
            
            if (tabName === 'available') {
                availableSection.style.display = 'grid';
                savedSection.style.display = 'none';
            } else {
                availableSection.style.display = 'none';
                savedSection.style.display = 'grid';
            }
        }

        // Filter properties
        function filterProperties() {
            const priceRange = document.getElementById('priceRange').value;
            const bedrooms = document.getElementById('bedrooms').value;
            const tenantType = document.getElementById('tenantType').value;
            
            // Get selected amenities
            const selectedAmenities = [];
            document.querySelectorAll('.amenity-filter:checked').forEach(checkbox => {
                selectedAmenities.push(checkbox.value);
            });
            
            const properties = document.querySelectorAll('.property-card');
            
            properties.forEach(property => {
                let show = true;
                
                // Price filter
                if (priceRange) {
                    const price = parseInt(property.dataset.price);
                    if (priceRange === '0-1000' && (price < 0 || price > 1000)) show = false;
                    if (priceRange === '1000-2000' && (price < 1000 || price > 2000)) show = false;
                    if (priceRange === '2000-3000' && (price < 2000 || price > 3000)) show = false;
                    if (priceRange === '3000+' && price < 3000) show = false;
                }
                
                // Bedrooms filter
                if (bedrooms) {
                    const propertyBedrooms = parseInt(property.dataset.bedrooms);
                    if (bedrooms === '4+' && propertyBedrooms < 4) show = false;
                    else if (bedrooms !== '4+' && propertyBedrooms !== parseInt(bedrooms)) show = false;
                }
                
                // Tenant type filter
                if (tenantType && property.dataset.tenantType !== tenantType) {
                    show = false;
                }
                
                // Amenities filter - show properties that have ALL selected amenities
                if (selectedAmenities.length > 0) {
                    const propertyAmenities = property.dataset.amenities.toLowerCase();
                    for (let amenity of selectedAmenities) {
                        if (!propertyAmenities.includes(amenity.toLowerCase())) {
                            show = false;
                            break;
                        }
                    }
                }
                
                property.style.display = show ? 'flex' : 'none';
            });
        }

        // Add event listeners to filters
        document.getElementById('priceRange').addEventListener('change', filterProperties);
        document.getElementById('bedrooms').addEventListener('change', filterProperties);
        document.getElementById('tenantType').addEventListener('change', filterProperties);
        
        // Add event listeners to amenity checkboxes
        document.querySelectorAll('.amenity-filter').forEach(checkbox => {
            checkbox.addEventListener('change', filterProperties);
        });

        // Contact homeowner
        function contactHomeowner(propertyId) {
            alert('Contact functionality will be implemented soon! Property ID: ' + propertyId);
        }

        // Toggle save property
        async function toggleSave(propertyId) {
            const button = event.target.closest('.btn-save');
            const isCurrentlySaved = button.classList.contains('saved');
            const action = isCurrentlySaved ? 'unsave' : 'save';
            
            console.log('ToggleSave called:', { propertyId, action, isCurrentlySaved });
            
            try {
                const requestData = {
                    action: action,
                    home_id: propertyId
                };
                console.log('Sending request:', requestData);
                
                const response = await fetch('api/save-property.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response result:', result);
                
                if (result.success) {
                    // Update button state
                    if (action === 'save') {
                        button.classList.add('saved');
                        button.innerHTML = '<i class="fas fa-heart"></i> Saved';
                    } else {
                        button.classList.remove('saved');
                        button.innerHTML = '<i class="fas fa-heart"></i> Save';
                    }
                    
                    // Show success message
                    showMessage(result.message, 'success');
                    
                    // Refresh saved properties tab if needed
                    if (document.getElementById('savedProperties').style.display !== 'none') {
                        location.reload(); // Simple refresh for now
                    }
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Failed to save property. Please try again.', 'error');
            }
        }
        
        // Show message function
        function showMessage(message, type) {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                z-index: 1000;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            document.body.appendChild(messageDiv);
            
            // Remove after 3 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        // Settings placeholder
        function openSettings() {
            alert('Settings page coming soon!');
        }
    </script>
</body>
</html> 