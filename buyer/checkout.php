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

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get cart items with product details
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, p.vendor_id, u.name as vendor_name 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               JOIN users u ON p.vendor_id = u.id
               WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();

// Calculate total
$total = 0;
$cart_data = [];
while ($item = $cart_items->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $total += $item_total;
    $cart_data[] = $item;
}

// Check if cart is empty
if (count($cart_data) == 0) {
    header('Location: cart.php');
    exit();
}

// Handle order placement
$order_placed = false;
$order_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    
    if (empty($shipping_address)) {
        $order_message = 'Please provide a shipping address.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("ids", $user_id, $total, $shipping_address);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // Create order items
            foreach ($cart_data as $item) {
                $order_item_query = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($order_item_query);
                $stmt->bind_param("iiiid", $order_id, $item['product_id'], $item['vendor_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                $stmt->close();
                
                // Update product stock
                $new_stock = $item['stock'] - $item['quantity'];
                $update_stock_query = "UPDATE products SET stock = ? WHERE id = ?";
                $stmt = $conn->prepare($update_stock_query);
                $stmt->bind_param("ii", $new_stock, $item['product_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Clear cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($clear_cart_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $order_placed = true;
            $order_message = "Order placed successfully! Order ID: #$order_id";
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $order_message = 'Error placing order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-credit-card me-2"></i>Checkout</h2>
                        <a href="cart.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cart
                        </a>
                    </div>

                    <?php if ($order_placed): ?>
                        <!-- Order Success -->
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">Order Placed Successfully!</h4>
                            <p class="text-muted"><?php echo htmlspecialchars($order_message); ?></p>
                            <div class="mt-4">
                                <a href="orders.php" class="btn btn-primary me-2">
                                    <i class="fas fa-shopping-bag me-2"></i>View My Orders
                                </a>
                                <a href="browse_products.php" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Checkout Form -->
                        <div class="row">
                            <!-- Order Summary -->
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Order Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($cart_data as $item): ?>
                                            <div class="row align-items-center border-bottom pb-3 mb-3">
                                                <div class="col-md-2">
                                                    <?php if (!empty($item['image']) && file_exists("../assets/uploads/" . $item['image'])): ?>
                                                        <img src="../assets/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                             class="product-image">
                                                    <?php else: ?>
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($item['vendor_name']); ?>
                                                    </small>
                                                    <br>
                                                    <span class="text-primary fw-bold">₹<?php echo number_format($item['price'], 2); ?></span>
                                                </div>
                                                
                                                <div class="col-md-2 text-center">
                                                    <span class="badge bg-secondary">Qty: <?php echo $item['quantity']; ?></span>
                                                </div>
                                                
                                                <div class="col-md-2 text-end">
                                                    <span class="fw-bold">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Checkout Form -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($order_message)): ?>
                                            <div class="alert alert-danger">
                                                <?php echo htmlspecialchars($order_message); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="shipping_address" class="form-label">Shipping Address *</label>
                                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required 
                                                          placeholder="Enter your complete shipping address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <hr>
                                            
                                            <!-- Order Summary -->
                                            <div class="mb-3">
                                                <h6>Order Summary</h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Subtotal:</span>
                                                    <span>₹<?php echo number_format($total, 2); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Shipping:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Tax:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between mb-3">
                                                    <strong>Total:</strong>
                                                    <strong class="text-primary">₹<?php echo number_format($total, 2); ?></strong>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success w-100 mb-2">
                                                <i class="fas fa-credit-card me-2"></i>Place Order
                                            </button>
                                            
                                            <a href="cart.php" class="btn btn-outline-secondary w-100">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
