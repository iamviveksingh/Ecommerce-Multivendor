<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Handle vendor deletion
if (isset($_POST['delete_vendor'])) {
    $vendor_id = (int)$_POST['vendor_id'];
    
    // First get all product images to delete them
    $get_products = $conn->prepare("SELECT image FROM products WHERE vendor_id = ?");
    $get_products->bind_param("i", $vendor_id);
    $get_products->execute();
    $products_result = $get_products->get_result();
    
    // Delete product images from server
    while ($product = $products_result->fetch_assoc()) {
        if (!empty($product['image']) && file_exists("../assets/uploads/" . $product['image'])) {
            unlink("../assets/uploads/" . $product['image']);
        }
    }
    $get_products->close();
    
    // Delete all products from this vendor
    $delete_products = $conn->prepare("DELETE FROM products WHERE vendor_id = ?");
    $delete_products->bind_param("i", $vendor_id);
    $delete_products->execute();
    $delete_products->close();
    
    // Then delete the vendor
    $delete_vendor = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'vendor'");
    $delete_vendor->bind_param("i", $vendor_id);
    
    if ($delete_vendor->execute()) {
        $success_message = "Vendor and all associated products deleted successfully!";
    } else {
        $error_message = "Error deleting vendor: " . $conn->error;
    }
    $delete_vendor->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$performance_filter = isset($_GET['performance']) ? $_GET['performance'] : '';

// Build query with filters
$where_conditions = ["u.role = 'vendor'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get all vendors with their statistics
$vendors_query = "SELECT u.*, 
                  COUNT(p.id) as total_products,
                  SUM(CASE WHEN p.stock > 0 THEN 1 ELSE 0 END) as active_products,
                  SUM(CASE WHEN p.stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                  COALESCE(SUM(p.price * p.stock), 0) as inventory_value,
                  COUNT(DISTINCT o.id) as total_orders,
                  COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales,
                  COALESCE(SUM(oi.quantity * oi.price) * 0.1, 0) as commission_earned
                  FROM users u 
                  LEFT JOIN products p ON u.id = p.vendor_id 
                  LEFT JOIN order_items oi ON p.id = oi.product_id
                  LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
                  $where_clause 
                  GROUP BY u.id 
                  ORDER BY total_sales DESC";

$stmt = $conn->prepare($vendors_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$vendors_result = $stmt->get_result();

// Get statistics
$total_vendors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'];
$active_vendors = $conn->query("SELECT COUNT(DISTINCT u.id) as count FROM users u JOIN products p ON u.id = p.vendor_id WHERE u.role = 'vendor' AND p.stock > 0")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_sales = $conn->query("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled'")->fetch_assoc()['total'];
$total_commission = $conn->query("SELECT COALESCE(SUM(oi.quantity * oi.price) * 0.1, 0) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled'")->fetch_assoc()['total'];
$avg_performance = $conn->query("SELECT AVG(performance_score) as avg_score FROM (
    SELECT u.id, 
           CASE 
               WHEN COUNT(p.id) = 0 THEN 0
               WHEN COUNT(p.id) < 5 THEN 25
               WHEN COUNT(p.id) < 10 THEN 50
               WHEN COUNT(p.id) < 20 THEN 75
               ELSE 100
           END as performance_score
    FROM users u 
    LEFT JOIN products p ON u.id = p.vendor_id 
    WHERE u.role = 'vendor'
    GROUP BY u.id
) as vendor_performance")->fetch_assoc()['avg_score'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Bazario Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-glow: #667eea;
            --secondary-glow: #764ba2;
            --accent-neon: #00d4ff;
            --success-neon: #00ff88;
            --warning-neon: #ffaa00;
            --danger-neon: #ff4757;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 15px 20px;
            margin: 8px 0;
            border-radius: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-neon), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .glass-card:hover::before {
            opacity: 1;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Vendor Cards */
        .vendor-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .vendor-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .vendor-card:hover::after {
            opacity: 1;
        }

        .vendor-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .vendor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-neon), var(--success-neon));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
        }

        .vendor-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .vendor-email {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 15px;
        }

        .vendor-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-neon);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .performance-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .performance-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-neon), var(--accent-neon));
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .commission-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .commission-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-neon);
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .filter-chip {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 8px 16px;
            margin: 5px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            font-size: 0.9rem;
        }

        .filter-chip:hover,
        .filter-chip.active {
            background: var(--accent-neon);
            color: white;
            transform: translateY(-2px);
        }

        /* Search Bar */
        .search-container {
            position: relative;
            max-width: 400px;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 12px 20px 12px 50px;
            color: white;
            width: 100%;
            backdrop-filter: blur(10px);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-neon);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }

        /* Statistics Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--accent-neon), var(--success-neon));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Action Buttons */
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
            display: inline-block;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }

        .action-btn:hover {
            background: var(--accent-neon);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 153, 204, 0.3);
        }

        .action-btn.text-danger {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: var(--danger-neon);
        }

        .action-btn.text-danger:hover {
            background: var(--danger-neon);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }

        /* Ensure buttons are clickable */
        .action-btn:active {
            transform: scale(0.95);
        }

        .action-btn:focus {
            outline: 2px solid var(--accent-neon);
            outline-offset: 2px;
        }

        /* Fix any potential z-index issues */
        .action-btn {
            position: relative;
            z-index: 10;
        }

        /* Main Content */
        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 20px;
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Neon Colors */
        .text-primary { color: var(--accent-neon) !important; }
        .text-success { color: var(--success-neon) !important; }
        .text-warning { color: var(--warning-neon) !important; }
        .text-danger { color: var(--danger-neon) !important; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Alert Messages */
        .alert {
            border-radius: 15px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success-neon);
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .alert-danger {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger-neon);
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        /* Alert Messages */
        .alert {
            border-radius: 15px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success-neon);
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .alert-danger {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger-neon);
            border: 1px solid rgba(255, 71, 87, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Glassmorphism Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3 h-100">
                    <div class="text-center mb-4">
                        <h4 class="text-white mb-2">
                            <i class="fas fa-store me-2"></i>Bazario
                        </h4>
                        <p class="text-white-50 mb-0">Welcome, <?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i>All Products
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag me-2"></i>All Orders
                        </a>
                        <a class="nav-link active" href="vendors.php">
                            <i class="fas fa-store me-2"></i>Vendor Management
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../store.php">
                            <i class="fas fa-home me-2"></i>Back to Store
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="text-white mb-1">
                                <i class="fas fa-store me-2"></i>Vendor Management
                            </h2>
                            <p class="text-white-50 mb-0">Manage vendors, track performance, and monitor commissions</p>
                        </div>
                        <a href="dashboard.php" class="btn glass-card text-white border-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-2 loading" style="animation-delay: 0.2s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_vendors; ?></div>
                                <div class="stat-label">Total Vendors</div>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.3s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $active_vendors; ?></div>
                                <div class="stat-label">Active Vendors</div>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.4s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_products; ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.5s;">
                            <div class="stat-card">
                                <div class="stat-number">₹<?php echo number_format($total_sales, 2); ?></div>
                                <div class="stat-label">Total Sales</div>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.6s;">
                            <div class="stat-card">
                                <div class="stat-number">₹<?php echo number_format($total_commission, 2); ?></div>
                                <div class="stat-label">Total Commission</div>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.7s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo round($avg_performance); ?>%</div>
                                <div class="stat-label">Avg Performance</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section loading" style="animation-delay: 0.8s;">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white mb-3">Filter Vendors</h5>
                                <div class="mb-3">
                                    <a href="vendors.php" class="filter-chip <?php echo empty($performance_filter) ? 'active' : ''; ?>">
                                        All Vendors
                                    </a>
                                    <a href="vendors.php?performance=high" class="filter-chip <?php echo $performance_filter == 'high' ? 'active' : ''; ?>">
                                        High Performers
                                    </a>
                                    <a href="vendors.php?performance=medium" class="filter-chip <?php echo $performance_filter == 'medium' ? 'active' : ''; ?>">
                                        Medium Performers
                                    </a>
                                    <a href="vendors.php?performance=low" class="filter-chip <?php echo $performance_filter == 'low' ? 'active' : ''; ?>">
                                        Low Performers
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" name="search" 
                                           placeholder="Search vendors..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="performance" value="<?php echo htmlspecialchars($performance_filter); ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Vendors Grid -->
                    <div class="row g-4">
                        <?php if ($vendors_result->num_rows > 0): ?>
                            <?php $shown_count = 0; while ($vendor = $vendors_result->fetch_assoc()): 
                                // Calculate performance score
                                $performance_score = 0;
                                if ($vendor['total_products'] > 0) {
                                    if ($vendor['total_products'] >= 20) $performance_score = 100;
                                    elseif ($vendor['total_products'] >= 10) $performance_score = 75;
                                    elseif ($vendor['total_products'] >= 5) $performance_score = 50;
                                    else $performance_score = 25;
                                }
                                // Apply performance filter if set
                                $matches_filter = true;
                                if ($performance_filter === 'high') {
                                    $matches_filter = ($performance_score >= 75);
                                } elseif ($performance_filter === 'medium') {
                                    $matches_filter = ($performance_score >= 50 && $performance_score < 75);
                                } elseif ($performance_filter === 'low') {
                                    $matches_filter = ($performance_score > 0 && $performance_score < 50) || $vendor['total_products'] == 0;
                                }
                                if (!$matches_filter) { continue; }
                                $shown_count++;
                                
                                // Calculate commission rate (10% of sales)
                                $commission_rate = 10;
                                $commission_earned = $vendor['total_sales'] * ($commission_rate / 100);
                            ?>
                                <div class="col-lg-6 col-xl-4 loading" style="animation-delay: 0.9s;">
                                    <div class="vendor-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="vendor-avatar">
                                                <?php echo strtoupper(substr($vendor['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ms-3">
                                                <div class="vendor-name"><?php echo htmlspecialchars($vendor['name']); ?></div>
                                                <div class="vendor-email"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                            </div>
                                        </div>

                                        <!-- Performance Bar -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-white-50">Performance Score</span>
                                                <span class="text-white"><?php echo $performance_score; ?>%</span>
                                            </div>
                                            <div class="performance-bar">
                                                <div class="performance-fill" style="width: <?php echo $performance_score; ?>%"></div>
                                            </div>
                                        </div>

                                        <!-- Vendor Statistics -->
                                        <div class="vendor-stats">
                                            <div class="stat-item">
                                                <div class="stat-number"><?php echo $vendor['total_products']; ?></div>
                                                <div class="stat-label">Products</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-number"><?php echo $vendor['active_products']; ?></div>
                                                <div class="stat-label">Active</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-number"><?php echo $vendor['total_orders']; ?></div>
                                                <div class="stat-label">Orders</div>
                                            </div>
                                        </div>

                                        <!-- Sales & Commission -->
                                        <div class="commission-info">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <div class="text-white-50 mb-1">Total Sales</div>
                                                    <div class="commission-amount">₹<?php echo number_format($vendor['total_sales'], 2); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-white-50 mb-1">Commission (<?php echo $commission_rate; ?>%)</div>
                                                    <div class="commission-amount">₹<?php echo number_format($commission_earned, 2); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Inventory Value -->
                                        <div class="mb-3">
                                            <div class="text-white-50 mb-1">Inventory Value</div>
                                            <div class="text-white">₹<?php echo number_format($vendor['inventory_value'], 2); ?></div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex justify-content-between">
                                            <a href="vendor_products.php?vendor_id=<?php echo $vendor['id']; ?>" class="action-btn">
                                                <i class="fas fa-eye me-2"></i>View Products
                                            </a>
                                            <a href="mailto:<?php echo htmlspecialchars($vendor['email']); ?>" class="action-btn">
                                                <i class="fas fa-envelope me-2"></i>Contact
                                            </a>
                                            <button class="action-btn text-danger" onclick="deleteVendor(<?php echo $vendor['id']; ?>, '<?php echo htmlspecialchars($vendor['name']); ?>')">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 loading" style="animation-delay: 0.9s;">
                                <div class="glass-card p-5 text-center">
                                    <i class="fas fa-store fa-4x text-white-50 mb-3"></i>
                                    <h4 class="text-white mb-2">No Vendors Found</h4>
                                    <p class="text-white-50">Try adjusting your search criteria or filters.</p>
                                    <a href="vendors.php" class="btn glass-card text-white border-0">
                                        <i class="fas fa-refresh me-2"></i>Clear Filters
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Results Summary -->
                    <?php if (isset($shown_count) ? $shown_count > 0 : $vendors_result->num_rows > 0): ?>
                        <div class="mt-4 loading" style="animation-delay: 1.0s;">
                            <div class="glass-card p-3 text-center">
                                <p class="text-white-50 mb-0">
                                    Showing <?php echo isset($shown_count) ? $shown_count : $vendors_result->num_rows; ?> vendor(s)
                                    <?php if (!empty($search) || !empty($performance_filter)): ?>
                                        (filtered results)
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for vendor deletion -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="vendor_id" id="vendorId">
        <input type="hidden" name="delete_vendor" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit search form on input
        document.querySelector('.search-input').addEventListener('input', function() {
            if (this.value.length >= 3 || this.value.length === 0) {
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });

        // Add hover effects to vendor cards
        document.querySelectorAll('.vendor-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Filter chip animations
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                // Remove active class from all chips in the same group
                const parent = this.parentElement;
                parent.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Delete vendor function
        function deleteVendor(vendorId, vendorName) {
            if (confirm('Are you sure you want to delete vendor "' + vendorName + '"? This action cannot be undone and will also delete all their products.')) {
                document.getElementById('vendorId').value = vendorId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Add click effects to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                console.log('Button clicked:', this.textContent.trim());
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Debug delete buttons
        console.log('Vendor delete function loaded');
        window.deleteVendor = function(vendorId, vendorName) {
            console.log('deleteVendor called with:', vendorId, vendorName);
            if (confirm('Are you sure you want to delete vendor "' + vendorName + '"? This action cannot be undone and will also delete all their products.')) {
                console.log('Confirmation accepted, submitting form');
                document.getElementById('vendorId').value = vendorId;
                document.getElementById('deleteForm').submit();
            } else {
                console.log('Confirmation cancelled');
            }
        };

        // Animate performance bars on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelectorAll('.performance-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 1000);
        });

        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
