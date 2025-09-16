<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_vendors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'];
$total_buyers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'buyer'")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// Get recent activity
$recent_orders = $conn->query("SELECT o.*, u.name as customer_name FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.id DESC LIMIT 5");

$recent_users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white"><i class="fas fa-store me-2"></i>Bazario</h4>
                        <p class="text-white-50 mb-0">Welcome, <?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a class="nav-link" href="vendors.php">
                            <i class="fas fa-store me-2"></i>Vendor Management
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i>View Products
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag me-2"></i>Orders
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../store.php">
                            <i class="fas fa-store me-2"></i>View Store
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
                    <div class="page-header">
                        <div class="brand-badge mb-3">
                            <i class="fas fa-store me-2"></i>Bazario
                        </div>
                        <h1 class="page-title">Welcome to <span class="brand-highlight">Bazario</span> Admin</h1>
                        <p class="page-subtitle">Manage your marketplace, monitor performance, and oversee operations</p>
                        <div class="mt-3">
                            <a href="reports.php" class="btn btn-brand">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card p-4 text-center">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-users fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_users; ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card p-4 text-center">
                                <div class="text-success mb-2">
                                    <i class="fas fa-store fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_vendors; ?></h3>
                                <p class="text-muted mb-0">Total Vendors</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card p-4 text-center">
                                <div class="text-info mb-2">
                                    <i class="fas fa-shopping-cart fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_buyers; ?></h3>
                                <p class="text-muted mb-0">Total Buyers</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="stat-card p-4 text-center">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-box fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_products; ?></h3>
                                <p class="text-muted mb-0">Total Products</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card p-4 text-center">
                                <div class="text-danger mb-2">
                                    <i class="fas fa-shopping-bag fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_orders; ?></h3>
                                <p class="text-muted mb-0">Total Orders</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Activity Feed -->
                    <div class="row g-4">
                        <!-- Quick Actions -->
                        <div class="col-md-8">
                            <div class="glass-card p-4">
                                <h5 class="text-white mb-3">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <a href="users.php" class="btn glass-card text-white border-0 w-100">
                                            <i class="fas fa-users me-2"></i>Manage Users
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="vendors.php" class="btn glass-card text-white border-0 w-100">
                                            <i class="fas fa-store me-2"></i>Vendor Management
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="products.php" class="btn glass-card text-white border-0 w-100">
                                            <i class="fas fa-box me-2"></i>View Products
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="orders.php" class="btn glass-card text-white border-0 w-100">
                                            <i class="fas fa-shopping-bag me-2"></i>View Orders
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity Feed -->
                        <div class="col-md-4">
                            <div class="glass-card p-4">
                                <h5 class="text-white mb-3">
                                    <i class="fas fa-broadcast-tower me-2"></i>Recent Activity
                                </h5>
                                <div class="activity-feed">
                                    <?php if ($recent_orders->num_rows > 0): ?>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="fas fa-shopping-bag text-success"></i>
                                                </div>
                                                <div>
                                                    <small class="text-white-50">Order #<?php echo $order['id']; ?></small>
                                                    <div class="text-white"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                                    <small class="text-white-50">₹<?php echo number_format($order['total_amount'], 2); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="text-center text-white-50">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No recent orders</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>