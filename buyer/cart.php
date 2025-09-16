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

// Get cart items with product details
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.stock, u.name as vendor_name 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
        .cart-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        .image-placeholder {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            font-size: 0.8rem;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .total-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .card {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
        }
        .card-header {
            background: rgba(255, 255, 255, 0.05) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }
        .card-body {
            color: white !important;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border-radius: 10px !important;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: #00d4ff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25) !important;
            color: white !important;
        }
        .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }
        .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
            color: white !important;
        }
        .btn-outline-danger {
            background: rgba(220, 53, 69, 0.1) !important;
            border-color: rgba(220, 53, 69, 0.5) !important;
            color: #dc3545 !important;
        }
        .btn-outline-danger:hover {
            background: rgba(220, 53, 69, 0.2) !important;
            border-color: #dc3545 !important;
            color: white !important;
        }
        .btn-outline-primary {
            background: rgba(0, 212, 255, 0.1) !important;
            border-color: rgba(0, 212, 255, 0.5) !important;
            color: #00d4ff !important;
        }
        .btn-outline-primary:hover {
            background: rgba(0, 212, 255, 0.2) !important;
            border-color: #00d4ff !important;
            color: white !important;
        }
        .text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .border-bottom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        hr {
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
                        <a class="nav-link" href="browse_products.php">
                            <i class="fas fa-search me-2"></i>Browse Products
                        </a>
                        <a class="nav-link active" href="cart.php">
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
                        <h2><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h2>
                        <a href="browse_products.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Continue Shopping
                        </a>
                    </div>

                    <?php if (count($cart_data) > 0): ?>
                        <div class="row">
                            <!-- Cart Items -->
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Cart Items (<?php echo count($cart_data); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($cart_data as $item): ?>
                                            <div class="cart-item border-bottom pb-3 mb-3">
                                                <div class="row align-items-center">
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
                                                    
                                                    <div class="col-md-3">
                                                        <div class="input-group input-group-sm" style="width: 120px;">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                                                            <input type="number" class="form-control text-center" id="cart_qty_<?php echo $item['product_id']; ?>" 
                                                                   value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" 
                                                                   onchange="updateQuantity(<?php echo $item['product_id']; ?>, 0)">
                                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">+</button>
                                                        </div>
                                                        <small class="text-muted">Stock: <?php echo $item['stock']; ?></small>
                                                    </div>
                                                    
                                                    <div class="col-md-2 text-end">
                                                        <span class="fw-bold">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                    </div>
                                                    
                                                    <div class="col-md-1 text-end">
                                                        <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Summary -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Order Summary</h5>
                                    </div>
                                    <div class="card-body">
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
                                        
                                        <button class="btn btn-success w-100 mb-2" onclick="proceedToCheckout()">
                                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                        </button>
                                        
                                        <a href="browse_products.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Your Cart is Empty</h4>
                            <p class="text-muted">Start shopping to add items to your cart!</p>
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
    <script>
        function updateQuantity(productId, change) {
            let currentQty = parseInt(document.getElementById('cart_qty_' + productId).value);
            
            if (change === 0) {
                // Direct input change
                currentQty = parseInt(document.getElementById('cart_qty_' + productId).value);
            } else {
                // Button click (+ or -)
                currentQty += change;
            }
            
            if (currentQty <= 0) {
                alert('Quantity must be greater than 0');
                document.getElementById('cart_qty_' + productId).value = 1;
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', currentQty);
            
            // Send AJAX request
            fetch('update_cart_quantity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data); // Debug log
                // Always refresh the page to show updated quantities
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                // Even if there's an error, refresh to show current state
                location.reload();
            });
        }
        
        function removeFromCart(productId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                // Create form data
                const formData = new FormData();
                formData.append('product_id', productId);
                
                // Send AJAX request
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item from cart');
                });
            }
        }
        
        function proceedToCheckout() {
            // Redirect to checkout page
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>
