<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order #$order_id status updated to " . ucfirst($new_status) . " successfully!";
        } else {
            $error_message = "Error updating order status: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Invalid status provided!";
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($date_filter)) {
    // Date filtering removed due to missing created_at column
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all orders with user information and filters
$orders_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 $where_clause ORDER BY o.id DESC";
$stmt = $conn->prepare($orders_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders_result = $stmt->get_result();

// Get statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$confirmed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'confirmed'")->fetch_assoc()['count'];
$shipped_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'")->fetch_assoc()['count'];
$delivered_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'")->fetch_assoc()['count'];
$cancelled_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'];

// Get orders for Kanban board
$kanban_orders = $conn->query("SELECT o.*, u.name as customer_name, u.email as customer_email 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - Bazario Admin</title>
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

        /* Order Cards */
        .order-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            cursor: pointer;
        }

        .order-card::after {
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

        .order-card:hover::after {
            opacity: 1;
        }

        .order-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .order-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent-neon);
            margin-bottom: 10px;
        }

        .customer-info {
            color: white;
            margin-bottom: 10px;
        }

        .order-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-neon);
            margin-bottom: 10px;
        }

        .order-date {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 170, 0, 0.2);
            color: var(--warning-neon);
            border: 1px solid var(--warning-neon);
        }

        .status-confirmed {
            background: rgba(0, 212, 255, 0.2);
            color: var(--accent-neon);
            border: 1px solid var(--accent-neon);
        }

        .status-shipped {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            border: 1px solid #667eea;
        }

        .status-delivered {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success-neon);
            border: 1px solid var(--success-neon);
        }

        .status-cancelled {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger-neon);
            border: 1px solid var(--danger-neon);
        }

        /* Kanban Board */
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 0;
        }

        .kanban-column {
            min-width: 300px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .kanban-header {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .kanban-count {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 10px;
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
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
            display: inline-block;
            font-size: 0.9rem;
        }

        .action-btn:hover {
            background: var(--accent-neon);
            color: white;
            transform: translateY(-2px);
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

        /* View Toggle */
        .view-toggle {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 5px;
            display: inline-flex;
            margin-bottom: 20px;
        }

        .view-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s;
        }

        .view-btn.active {
            background: var(--accent-neon);
            color: white;
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
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-shopping-bag me-2"></i>All Orders
                        </a>
                        <a class="nav-link" href="vendors.php">
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
                                <i class="fas fa-shopping-bag me-2"></i>All Orders
                            </h2>
                            <p class="text-white-50 mb-0">Manage and track all customer orders</p>
                        </div>
                        <a href="dashboard.php" class="btn glass-card text-white border-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-2 loading" style="animation-delay: 0.1s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_orders; ?></div>
                                <p class="text-white-50 mb-0">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.2s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $pending_orders; ?></div>
                                <p class="text-white-50 mb-0">Pending</p>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.3s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $confirmed_orders; ?></div>
                                <p class="text-white-50 mb-0">Confirmed</p>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.4s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $shipped_orders; ?></div>
                                <p class="text-white-50 mb-0">Shipped</p>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.5s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $delivered_orders; ?></div>
                                <p class="text-white-50 mb-0">Delivered</p>
                            </div>
                        </div>
                        <div class="col-md-2 loading" style="animation-delay: 0.6s;">
                            <div class="stat-card">
                                <div class="stat-number">₹<?php echo number_format($total_revenue, 2); ?></div>
                                <p class="text-white-50 mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section loading" style="animation-delay: 0.7s;">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white mb-3">Filter Orders</h5>
                                <div class="mb-3">
                                    <a href="orders.php" class="filter-chip <?php echo empty($status_filter) ? 'active' : ''; ?>">
                                        All Orders
                                    </a>
                                    <a href="orders.php?status=pending" class="filter-chip <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                                        Pending
                                    </a>
                                    <a href="orders.php?status=confirmed" class="filter-chip <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">
                                        Confirmed
                                    </a>
                                    <a href="orders.php?status=shipped" class="filter-chip <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">
                                        Shipped
                                    </a>
                                    <a href="orders.php?status=delivered" class="filter-chip <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
                                        Delivered
                                    </a>
                                    <a href="orders.php?status=cancelled" class="filter-chip <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                                        Cancelled
                                    </a>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-white me-2">Date Range:</strong>
                                    <a href="orders.php" class="filter-chip <?php echo empty($date_filter) ? 'active' : ''; ?>">
                                        All Time
                                    </a>
                                    <a href="orders.php?date=today" class="filter-chip <?php echo $date_filter == 'today' ? 'active' : ''; ?>">
                                        Today
                                    </a>
                                    <a href="orders.php?date=week" class="filter-chip <?php echo $date_filter == 'week' ? 'active' : ''; ?>">
                                        This Week
                                    </a>
                                    <a href="orders.php?date=month" class="filter-chip <?php echo $date_filter == 'month' ? 'active' : ''; ?>">
                                        This Month
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" name="search" 
                                           placeholder="Search orders..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- View Toggle -->
                    <div class="loading" style="animation-delay: 0.8s;">
                        <div class="view-toggle">
                            <button class="view-btn active" onclick="showKanban()">
                                <i class="fas fa-columns me-2"></i>Kanban Board
                            </button>
                            <button class="view-btn" onclick="showList()">
                                <i class="fas fa-list me-2"></i>List View
                            </button>
                        </div>
                    </div>

                    <!-- Kanban Board View -->
                    <div id="kanban-view" class="loading" style="animation-delay: 0.9s;">
                        <div class="kanban-board">
                            <!-- Pending Column -->
                            <div class="kanban-column">
                                <div class="kanban-header">
                                    <i class="fas fa-clock me-2"></i>Pending
                                    <span class="kanban-count"><?php echo $pending_orders; ?></span>
                                </div>
                                <?php 
                                $kanban_orders->data_seek(0);
                                while ($order = $kanban_orders->fetch_assoc()): 
                                    if ($order['status'] == 'pending'):
                                ?>
                                <div class="order-card" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">
                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                    <div class="customer-info"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div class="order-date">Order #<?php echo $order['id']; ?></div>
                                    <div class="status-badge status-pending">Pending</div>
                                    <div class="text-center">
                                        <small class="text-white-50">Click to confirm</small>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                            </div>

                            <!-- Confirmed Column -->
                            <div class="kanban-column">
                                <div class="kanban-header">
                                    <i class="fas fa-check me-2"></i>Confirmed
                                    <span class="kanban-count"><?php echo $confirmed_orders; ?></span>
                                </div>
                                <?php 
                                $kanban_orders->data_seek(0);
                                while ($order = $kanban_orders->fetch_assoc()): 
                                    if ($order['status'] == 'confirmed'):
                                ?>
                                <div class="order-card" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">
                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                    <div class="customer-info"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div class="order-date">Order #<?php echo $order['id']; ?></div>
                                    <div class="status-badge status-confirmed">Confirmed</div>
                                    <div class="text-center">
                                        <small class="text-white-50">Click to ship</small>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                            </div>

                            <!-- Shipped Column -->
                            <div class="kanban-column">
                                <div class="kanban-header">
                                    <i class="fas fa-shipping-fast me-2"></i>Shipped
                                    <span class="kanban-count"><?php echo $shipped_orders; ?></span>
                                </div>
                                <?php 
                                $kanban_orders->data_seek(0);
                                while ($order = $kanban_orders->fetch_assoc()): 
                                    if ($order['status'] == 'shipped'):
                                ?>
                                <div class="order-card" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                    <div class="customer-info"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div class="order-date">Order #<?php echo $order['id']; ?></div>
                                    <div class="status-badge status-shipped">Shipped</div>
                                    <div class="text-center">
                                        <small class="text-white-50">Click to deliver</small>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                            </div>

                            <!-- Delivered Column -->
                            <div class="kanban-column">
                                <div class="kanban-header">
                                    <i class="fas fa-check-circle me-2"></i>Delivered
                                    <span class="kanban-count"><?php echo $delivered_orders; ?></span>
                                </div>
                                <?php 
                                $kanban_orders->data_seek(0);
                                while ($order = $kanban_orders->fetch_assoc()): 
                                    if ($order['status'] == 'delivered'):
                                ?>
                                <div class="order-card">
                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                    <div class="customer-info"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div class="order-date">Order #<?php echo $order['id']; ?></div>
                                    <div class="status-badge status-delivered">Delivered</div>
                                    <div class="text-center">
                                        <small class="text-success">✓ Completed</small>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div id="list-view" class="loading" style="animation-delay: 0.9s; display: none;">
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-card mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <div class="order-id">#<?php echo $order['id']; ?></div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="customer-info">
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="order-date">Order #<?php echo $order['id']; ?></div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="dropdown">
                                                <button class="action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending')">Mark Pending</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">Mark Confirmed</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">Mark Shipped</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">Mark Delivered</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">Mark Cancelled</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="glass-card p-5 text-center">
                                <i class="fas fa-shopping-bag fa-4x text-white-50 mb-3"></i>
                                <h4 class="text-white mb-2">No Orders Found</h4>
                                <p class="text-white-50">Try adjusting your search criteria or filters.</p>
                                <a href="orders.php" class="btn glass-card text-white border-0">
                                    <i class="fas fa-refresh me-2"></i>Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Results Summary -->
                    <?php if ($orders_result->num_rows > 0): ?>
                        <div class="mt-4 loading" style="animation-delay: 1.0s;">
                            <div class="glass-card p-3 text-center">
                                <p class="text-white-50 mb-0">
                                    Showing <?php echo $orders_result->num_rows; ?> order(s)
                                    <?php if (!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
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

    <!-- Hidden form for status updates -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="order_id" id="orderId">
        <input type="hidden" name="new_status" id="newStatus">
        <input type="hidden" name="update_status" value="1">
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

        // Add hover effects to order cards
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
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

        // View toggle functionality
        function showKanban() {
            document.getElementById('kanban-view').style.display = 'block';
            document.getElementById('list-view').style.display = 'none';
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showList() {
            document.getElementById('kanban-view').style.display = 'none';
            document.getElementById('list-view').style.display = 'block';
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Update order status
        function updateOrderStatus(orderId, status) {
            if (confirm('Are you sure you want to update the order status to ' + status + '?')) {
                document.getElementById('orderId').value = orderId;
                document.getElementById('newStatus').value = status;
                document.getElementById('statusForm').submit();
            }
        }

        // Add click effects to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>
