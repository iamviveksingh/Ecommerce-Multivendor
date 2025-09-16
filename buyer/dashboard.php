<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get buyer statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id")->fetch_assoc()['count'];
$total_spent = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = $user_id AND status != 'cancelled'")->fetch_assoc()['total'];
$cart_items = $conn->query("SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id")->fetch_assoc()['count'];

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, oi.quantity, oi.price as item_price, p.name as product_name, u.name as vendor_name
                               FROM orders o 
                               JOIN order_items oi ON o.id = oi.order_id 
                               JOIN products p ON oi.product_id = p.id 
                               JOIN users u ON oi.vendor_id = u.id
                               WHERE o.user_id = $user_id 
                               ORDER BY o.created_at DESC 
                               LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
        }
        .card-header {
            background: rgba(255, 255, 255, 0.1) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px 20px 0 0 !important;
        }
        .card-body {
            background: transparent !important;
        }
        .table {
            color: white !important;
            background: transparent !important;
        }
        .table thead th {
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        .table tbody tr {
            background: transparent !important;
        }
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05) !important;
        }
        .table tbody td {
            background: transparent !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
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
                        <a class="nav-link" href="browse_products.php">
                            <i class="fas fa-search me-2"></i>Browse Products
                        </a>
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                            <?php if ($cart_items > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $cart_items; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
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
                        <h1 class="page-title">Welcome to <span class="brand-highlight">Bazario</span></h1>
                        <p class="page-subtitle">Discover amazing products and manage your shopping experience</p>
                        <div class="mt-3">
                            <a href="../store.php" class="btn btn-brand">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card-modern">
                                <div class="stat-icon-modern">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="stat-number-modern"><?php echo $total_orders; ?></div>
                                <p class="text-white-50 mb-0">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card-modern">
                                <div class="stat-icon-modern">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                                <div class="stat-number-modern">₹<?php echo number_format($total_spent, 2); ?></div>
                                <p class="text-white-50 mb-0">Total Spent</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card-modern">
                                <div class="stat-icon-modern">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-number-modern"><?php echo $cart_items; ?></div>
                                <p class="text-white-50 mb-0">Cart Items</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4 mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <a href="browse_products.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-search me-2"></i>Browse Products
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="cart.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-shopping-cart me-2"></i>View Cart
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="orders.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-list me-2"></i>My Orders
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="profile.php" class="btn btn-outline-warning w-100">
                                                <i class="fas fa-user-edit me-2"></i>Update Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_orders->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Product</th>
                                                        <th>Vendor</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['vendor_name']); ?></td>
                                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $order['status'] == 'delivered' ? 'success' : 
                                                                        ($order['status'] == 'shipped' ? 'info' : 
                                                                        ($order['status'] == 'confirmed' ? 'warning' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo ucfirst($order['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="orders.php" class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>View All Orders
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Orders Yet</h5>
                                            <p class="text-muted">Start shopping to see your orders here!</p>
                                            <a href="../store.php" class="btn btn-primary">
                                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                                            </a>
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
