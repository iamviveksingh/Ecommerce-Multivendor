<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get comprehensive statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_vendors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'];
$total_buyers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'buyer'")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'];
$total_commission = $conn->query("SELECT COALESCE(SUM(oi.quantity * oi.price) * 0.1, 0) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled'")->fetch_assoc()['total'];

// Get monthly sales data for chart (between selected date range)
$sales_labels = [];
$sales_revenue = [];

// Detect if orders.created_at exists
$has_created_at = false;
if ($rs = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'created_at'")) {
    $has_created_at = (bool)$rs->fetch_assoc()['cnt'];
    $rs->close();
}

if ($has_created_at) {
    if ($stmt = $conn->prepare("SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS ym, COALESCE(SUM(o.total_amount), 0) AS revenue
                                FROM orders o
                                WHERE o.status != 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
                                GROUP BY ym
                                ORDER BY ym ASC")) {
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sales_labels[] = $row['ym'];
            $sales_revenue[] = (float)$row['revenue'];
        }
        $stmt->close();
    }
} else {
    // Fallback: aggregate total revenue without monthly breakdown
    $sales_labels = [date('Y-m')];
    $sales_revenue = [(float)$total_revenue];
}

// Format labels as "Mon YYYY"
foreach ($sales_labels as &$lbl) {
    $lbl = date('M Y', strtotime($lbl . '-01'));
}
unset($lbl);

// Get user growth data
$user_growth = $conn->query("SELECT 
    '2024-01' as month,
    COUNT(*) as new_users
    FROM users");

// Get top performing vendors
$top_vendors = $conn->query("SELECT 
    u.name, 
    COUNT(p.id) as product_count,
    COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales,
    COALESCE(SUM(oi.quantity * oi.price) * 0.1, 0) as commission_earned
    FROM users u 
    LEFT JOIN products p ON u.id = p.vendor_id 
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE u.role = 'vendor'
    GROUP BY u.id
    ORDER BY total_sales DESC 
    LIMIT 10");

// Get recent orders
$recent_orders = $conn->query("SELECT 
    o.id,
    o.total_amount,
    o.status,
    u.name as customer_name,
    COUNT(oi.id) as item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.id DESC 
    LIMIT 10");

// Get product category distribution
$category_stats = $conn->query("SELECT 
    category,
    COUNT(*) as product_count,
    SUM(stock) as total_stock,
    AVG(price) as avg_price
    FROM products 
    WHERE category IS NOT NULL 
    GROUP BY category 
    ORDER BY product_count DESC");

// Get low stock alerts
$low_stock = $conn->query("SELECT 
    p.name, 
    p.stock, 
    p.category,
    u.name as vendor_name
    FROM products p 
    JOIN users u ON p.vendor_id = u.id 
    WHERE p.stock <= 5 
    ORDER BY p.stock ASC 
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Bazario Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .table {
            color: white;
        }
        .table thead th {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="reports.php">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                        <form method="GET" class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card p-4 text-center">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-users fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_users; ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-4 text-center">
                                <div class="text-success mb-2">
                                    <i class="fas fa-shopping-bag fa-3x"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $total_orders; ?></h3>
                                <p class="text-muted mb-0">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-4 text-center">
                                <div class="text-warning mb-2">
                                    <i class="fas fa-rupee-sign fa-3x"></i>
                                </div>
                                <h3 class="mb-1">₹<?php echo number_format($total_revenue, 2); ?></h3>
                                <p class="text-muted mb-0">Total Revenue</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-4 text-center">
                                <div class="text-info mb-2">
                                    <i class="fas fa-percentage fa-3x"></i>
                                </div>
                                <h3 class="mb-1">₹<?php echo number_format($total_commission, 2); ?></h3>
                                <p class="text-muted mb-0">Commission</p>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <div class="card p-4">
                                <h5 class="text-white mb-3">Monthly Sales</h5>
                                <canvas id="salesChart" height="100"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-4">
                                <h5 class="text-white mb-3">User Growth</h5>
                                <canvas id="userChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tables Row -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card p-4">
                                <h5 class="text-white mb-3">Recent Orders</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($order['status'] == 'delivered' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info')); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>Recent Order</td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card p-4">
                                <h5 class="text-white mb-3">Top Vendors</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Vendor</th>
                                                <th>Products</th>
                                                <th>Sales</th>
                                                <th>Commission</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($vendor = $top_vendors->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                                                <td><?php echo $vendor['product_count']; ?></td>
                                                <td>₹<?php echo number_format($vendor['total_sales'], 2); ?></td>
                                                <td>₹<?php echo number_format($vendor['commission_earned'], 2); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode($sales_revenue); ?>,
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: 'white' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                }
            }
        });

        // User Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: ['Vendors', 'Buyers'],
                datasets: [{
                    data: [<?php echo $total_vendors; ?>, <?php echo $total_buyers; ?>],
                    backgroundColor: ['#00d4ff', '#00ff88']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: 'white' }
                    }
                }
            }
        });
    </script>
</body>
</html>