<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Get vendor ID from URL
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

if ($vendor_id <= 0) {
    header('Location: vendors.php');
    exit();
}

// Get vendor information
$vendor_query = "SELECT * FROM users WHERE id = ? AND role = 'vendor'";
$vendor_stmt = $conn->prepare($vendor_query);
$vendor_stmt->bind_param("i", $vendor_id);
$vendor_stmt->execute();
$vendor_result = $vendor_stmt->get_result();

if ($vendor_result->num_rows == 0) {
    header('Location: vendors.php');
    exit();
}

$vendor = $vendor_result->fetch_assoc();

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Get product info for image deletion
    $get_product = $conn->prepare("SELECT name, image FROM products WHERE id = ? AND vendor_id = ?");
    $get_product->bind_param("ii", $product_id, $vendor_id);
    $get_product->execute();
    $product_result = $get_product->get_result();
    
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->bind_param("ii", $product_id, $vendor_id);
        
        if ($stmt->execute()) {
            // Delete product image if exists
            if (!empty($product['image']) && file_exists("../assets/uploads/" . $product['image'])) {
                unlink("../assets/uploads/" . $product['image']);
            }
            $success_message = "Product '" . htmlspecialchars($product['name']) . "' deleted successfully!";
        } else {
            $error_message = "Error deleting product: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Product not found!";
    }
    $get_product->close();
}

// Get all products for this vendor
$products_query = "SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("i", $vendor_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Products - Bazario Admin</title>
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

        /* Product Cards */
        .product-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .product-card::after {
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

        .product-card:hover::after {
            opacity: 1;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .product-category {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success-neon);
            margin-bottom: 10px;
        }

        .product-stock {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 15px;
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
    </style>
</head>
<body>
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
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i>All Products
                        </a>
                        <a class="nav-link" href="orders.php">
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
                                <i class="fas fa-box me-2"></i>Vendor Products
                            </h2>
                            <p class="text-white-50 mb-0">Products by <?php echo htmlspecialchars($vendor['name']); ?></p>
                        </div>
                        <a href="vendors.php" class="btn glass-card text-white border-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Vendors
                        </a>
                    </div>

                    <!-- Vendor Info Card -->
                    <div class="glass-card p-4 mb-4 loading" style="animation-delay: 0.1s;">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-white mb-2"><?php echo htmlspecialchars($vendor['name']); ?></h4>
                                <p class="text-white-50 mb-1"><?php echo htmlspecialchars($vendor['email']); ?></p>
                                <p class="text-white-50 mb-0">Vendor since <?php echo date('F Y', strtotime($vendor['created_at'])); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-white-50">Total Products</div>
                                <div class="text-white h3 mb-0"><?php echo $products_result->num_rows; ?></div>
                            </div>
                        </div>
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

                    <!-- Products Grid -->
                    <?php if ($products_result->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <div class="col-lg-4 col-md-6 loading" style="animation-delay: 0.2s;">
                                    <div class="product-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if ($product['image']): ?>
                                                <img src="../assets/uploads/<?php echo $product['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-image me-3">
                                            <?php else: ?>
                                                <div class="product-image bg-secondary d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-image text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="product-category"><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>

                                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                        <div class="product-stock">
                                            Stock: <span class="<?php echo $product['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $product['stock']; ?> units
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="../store.php?product=<?php echo $product['id']; ?>" class="action-btn" target="_blank">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="action-btn text-danger" title="Delete Product">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="loading" style="animation-delay: 0.2s;">
                            <div class="glass-card p-5 text-center">
                                <i class="fas fa-box fa-4x text-white-50 mb-3"></i>
                                <h4 class="text-white mb-2">No Products Found</h4>
                                <p class="text-white-50">This vendor hasn't added any products yet.</p>
                                <a href="vendors.php" class="btn glass-card text-white border-0">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Vendors
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add hover effects to product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

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
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            console.log('Delete form found:', form);
            const deleteBtn = form.querySelector('button[name*="delete"]');
            if (deleteBtn) {
                console.log('Delete button found:', deleteBtn);
                deleteBtn.addEventListener('click', function(e) {
                    console.log('Delete button clicked:', this);
                });
            }
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

        // Delete product function
        function deleteProduct(productId, productName) {
            if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="product_id" value="${productId}">
                    <input type="hidden" name="delete_product" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Animate cards on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelectorAll('.product-card').forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        card.style.transition = 'all 0.6s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 500);
        });
    </script>
</body>
</html>
