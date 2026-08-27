<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Get product info for confirmation
    $get_product = $conn->prepare("SELECT name, image FROM products WHERE id = ?");
    $get_product->bind_param("i", $product_id);
    $get_product->execute();
    $product_result = $get_product->get_result();
    
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        
        // Delete product
        $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_product->bind_param("i", $product_id);
        
        if ($delete_product->execute()) {
            // Delete product image if exists
            if (!empty($product['image']) && file_exists("../assets/uploads/" . $product['image'])) {
                unlink("../assets/uploads/" . $product['image']);
            }
            $success_message = "Product '" . htmlspecialchars($product['name']) . "' deleted successfully!";
        } else {
            $error_message = "Error deleting product: " . $conn->error;
        }
        $delete_product->close();
    } else {
        $error_message = "Product not found!";
    }
    $get_product->close();
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$vendor_filter = isset($_GET['vendor']) ? $_GET['vendor'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($category_filter)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($vendor_filter)) {
    $where_conditions[] = "p.vendor_id = ?";
    $params[] = $vendor_filter;
    $types .= "i";
}

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($stock_filter)) {
    if ($stock_filter == 'in_stock') {
        $where_conditions[] = "p.stock > 0";
    } elseif ($stock_filter == 'out_of_stock') {
        $where_conditions[] = "p.stock = 0";
    } elseif ($stock_filter == 'low_stock') {
        $where_conditions[] = "p.stock <= 5 AND p.stock > 0";
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all products with vendor information and filters
$products_query = "SELECT p.*, u.name as vendor_name FROM products p 
                   JOIN users u ON p.vendor_id = u.id 
                   $where_clause ORDER BY p.created_at DESC";
$stmt = $conn->prepare($products_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products_result = $stmt->get_result();

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$in_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock > 0")->fetch_assoc()['count'];
$out_of_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock = 0")->fetch_assoc()['count'];
$low_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock <= 5 AND stock > 0")->fetch_assoc()['count'];

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$categories_result = $conn->query($categories_query);

// Get vendors for filter
$vendors_query = "SELECT id, name FROM users WHERE role = 'vendor' ORDER BY name";
$vendors_result = $conn->query($vendors_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - Bazario Admin</title>
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
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: 100%;
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
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: all 0.3s;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 3rem;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .product-vendor {
            color: var(--accent-neon);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success-neon);
            margin-bottom: 10px;
        }

        .product-stock {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .stock-in {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success-neon);
            border: 1px solid var(--success-neon);
        }

        .stock-out {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger-neon);
            border: 1px solid var(--danger-neon);
        }

        .stock-low {
            background: rgba(255, 170, 0, 0.2);
            color: var(--warning-neon);
            border: 1px solid var(--warning-neon);
        }

        .product-category {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
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

        .action-btn.danger {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: var(--danger-neon);
        }

        .action-btn.danger:hover {
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

        /* Masonry Grid */
        .products-grid {
            column-count: 1;
            column-gap: 20px;
        }

        @media (min-width: 768px) {
            .products-grid {
                column-count: 2;
            }
        }

        @media (min-width: 992px) {
            .products-grid {
                column-count: 3;
            }
        }

        @media (min-width: 1200px) {
            .products-grid {
                column-count: 4;
            }
        }

        .product-item {
            break-inside: avoid;
            margin-bottom: 20px;
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
                        <a class="nav-link active" href="products.php">
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
                                <i class="fas fa-box me-2"></i>All Products
                            </h2>
                            <p class="text-white-50 mb-0">Manage product catalog and inventory</p>
                        </div>
                        <a href="dashboard.php" class="btn glass-card text-white border-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3 loading" style="animation-delay: 0.1s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_products; ?></div>
                                <p class="text-white-50 mb-0">Total Products</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.2s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $in_stock_products; ?></div>
                                <p class="text-white-50 mb-0">In Stock</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.3s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $out_of_stock_products; ?></div>
                                <p class="text-white-50 mb-0">Out of Stock</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.4s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $low_stock_products; ?></div>
                                <p class="text-white-50 mb-0">Low Stock</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section loading" style="animation-delay: 0.5s;">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white mb-3">Filter Products</h5>
                                <div class="mb-3">
                                    <a href="products.php" class="filter-chip <?php echo empty($stock_filter) ? 'active' : ''; ?>">
                                        All Stock
                                    </a>
                                    <a href="products.php?stock=in_stock" class="filter-chip <?php echo $stock_filter == 'in_stock' ? 'active' : ''; ?>">
                                        In Stock
                                    </a>
                                    <a href="products.php?stock=out_of_stock" class="filter-chip <?php echo $stock_filter == 'out_of_stock' ? 'active' : ''; ?>">
                                        Out of Stock
                                    </a>
                                    <a href="products.php?stock=low_stock" class="filter-chip <?php echo $stock_filter == 'low_stock' ? 'active' : ''; ?>">
                                        Low Stock
                                    </a>
                                </div>
                                
                                <!-- Category Filter -->
                                <?php if ($categories_result->num_rows > 0): ?>
                                <div class="mb-3">
                                    <strong class="text-white me-2">Categories:</strong>
                                    <a href="products.php" class="filter-chip <?php echo empty($category_filter) ? 'active' : ''; ?>">
                                        All Categories
                                    </a>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <a href="products.php?category=<?php echo urlencode($category['category']); ?>" 
                                           class="filter-chip <?php echo $category_filter == $category['category'] ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($category['category']); ?>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" name="search" 
                                           placeholder="Search products..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                                    <input type="hidden" name="stock" value="<?php echo htmlspecialchars($stock_filter); ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <?php if ($products_result->num_rows > 0): ?>
                        <div class="products-grid loading" style="animation-delay: 0.6s;">
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <div class="product-item">
                                    <div class="product-card">
                                        <!-- Product Image -->
                                        <?php if (!empty($product['image']) && file_exists("../assets/uploads/" . $product['image'])): ?>
                                            <img src="../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-content">
                                            <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <div class="product-vendor">
                                                <i class="fas fa-store me-1"></i>
                                                <?php echo htmlspecialchars($product['vendor_name']); ?>
                                            </div>
                                            <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                            
                                            <!-- Stock Status -->
                                            <?php if ($product['stock'] > 5): ?>
                                                <div class="product-stock stock-in">
                                                    <i class="fas fa-check-circle me-1"></i>In Stock (<?php echo $product['stock']; ?>)
                                                </div>
                                            <?php elseif ($product['stock'] > 0): ?>
                                                <div class="product-stock stock-low">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Low Stock (<?php echo $product['stock']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="product-stock stock-out">
                                                    <i class="fas fa-times-circle me-1"></i>Out of Stock
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Category -->
                                            <?php if (!empty($product['category'])): ?>
                                                <div class="product-category">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo htmlspecialchars($product['category']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Actions -->
                                            <div class="product-actions">
                                                <small class="text-white-50">ID: #<?php echo $product['id']; ?></small>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn" title="Edit Product">
                                                        <i class="fas fa-pen me-1"></i>Edit
                                                    </a>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this product?')">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="delete_product" class="action-btn danger" title="Delete Product">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="loading" style="animation-delay: 0.6s;">
                            <div class="glass-card p-5 text-center">
                                <i class="fas fa-box-open fa-4x text-white-50 mb-3"></i>
                                <h4 class="text-white mb-2">No Products Found</h4>
                                <p class="text-white-50">Try adjusting your search criteria or filters.</p>
                                <a href="products.php" class="btn glass-card text-white border-0">
                                    <i class="fas fa-refresh me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Results Summary -->
                    <?php if ($products_result->num_rows > 0): ?>
                        <div class="mt-4 loading" style="animation-delay: 0.8s;">
                            <div class="glass-card p-3 text-center">
                                <p class="text-white-50 mb-0">
                                    Showing <?php echo $products_result->num_rows; ?> product(s)
                                    <?php if (!empty($search) || !empty($category_filter) || !empty($stock_filter)): ?>
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

        // Add hover effects to product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
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

        // Filter chip animations
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                // Remove active class from all chips in the same group
                const parent = this.parentElement;
                parent.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Lazy loading for images
        const images = document.querySelectorAll('.product-image');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.style.opacity = '1';
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s';
            imageObserver.observe(img);
        });
    </script>
</body>
</html>
