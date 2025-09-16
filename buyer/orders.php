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

// Get buyer's orders
$orders_query = "SELECT o.*, oi.quantity, oi.price as item_price, p.name as product_name, p.image as product_image, u.name as vendor_name
                 FROM orders o 
                 JOIN order_items oi ON o.id = oi.order_id 
                 JOIN products p ON oi.product_id = p.id 
                 JOIN users u ON oi.vendor_id = u.id
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }
        .image-placeholder {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            font-size: 0.7rem;
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
                        <a class="nav-link" href="browse_products.php">
                            <i class="fas fa-search me-2"></i>Browse Products
                        </a>
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                        </a>
                        <a class="nav-link active" href="orders.php">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-shopping-bag me-2"></i>My Orders</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <?php if ($orders->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card order-card h-100">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>Order #<?php echo $order['id']; ?></strong>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'delivered' ? 'success' : 
                                                        ($order['status'] == 'shipped' ? 'info' : 
                                                        ($order['status'] == 'confirmed' ? 'warning' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row align-items-center mb-3">
                                                <div class="col-md-3">
                                                    <?php if (!empty($order['product_image']) && file_exists("../assets/uploads/" . $order['product_image'])): ?>
                                                        <img src="../assets/uploads/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                                             class="product-image">
                                                    <?php else: ?>
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-9">
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($order['product_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($order['vendor_name']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <strong>Quantity:</strong><br>
                                                    <span class="text-primary"><?php echo $order['quantity']; ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Price:</strong><br>
                                                    <span class="text-success">₹<?php echo number_format($order['item_price'], 2); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <strong>Total:</strong><br>
                                                    <span class="text-info">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Date:</strong><br>
                                                    <small><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <?php if ($order['shipping_address']): ?>
                                                <div class="mb-2">
                                                    <strong>Shipping Address:</strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['shipping_address']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-3">
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Orders Yet</h4>
                            <p class="text-muted">Start shopping to see your orders here!</p>
                            <a href="browse_products.php" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Browse Products
                            </a>
                            <a href="../store.php" class="btn btn-outline-primary">
                                <i class="fas fa-store me-2"></i>View Store
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
