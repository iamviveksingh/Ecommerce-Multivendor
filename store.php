<?php
session_start();
require_once 'config/database.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? floatval($_GET['min_price']) : null;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? floatval($_GET['max_price']) : null;

// Build the query with filters
$where_conditions = ["p.status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($min_price !== null && $min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

// Get products with vendor information
$query = "SELECT p.*, u.name as vendor_name 
          FROM products p 
          JOIN users u ON p.vendor_id = u.id 
          WHERE $where_clause 
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Get unique categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE status = 'active' AND category IS NOT NULL ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Get total products count for statistics
$total_products = $products->num_rows;
$total_vendors = $conn->query("SELECT COUNT(DISTINCT vendor_id) as count FROM products WHERE status = 'active'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        /* Page-specific styles */

        /* Page-specific styles */

        /* Hero Section */
        .hero-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 3rem 2rem;
            margin: 2rem 0;
            color: white;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Search Box */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--glass-border);
            border-radius: 50px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            border-color: var(--neon-accent);
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
        }

        .search-input {
            background: transparent;
            border: none;
            color: white;
            flex: 1;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .search-input:focus {
            outline: none;
        }

        .search-btn {
            background: var(--neon-accent);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #00b8e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
        }

        /* Statistics */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            color: white;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-accent);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--neon-accent);
            margin-bottom: 1rem;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Filters Section */
        .filter-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 2rem;
            margin: 2rem 0;
            color: white;
        }

        .filter-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--neon-accent);
        }

        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-select, .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: white;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--neon-accent);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
            color: white;
        }

        .form-select option {
            background: #2c3e50 !important;
            color: var(--text-primary) !important;
            padding: 10px !important;
        }

        .btn-filter {
            background: var(--neon-accent);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background: #00b8e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
        }

        .btn-clear {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-clear:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--neon-accent);
            transform: translateY(-2px);
        }

        /* Results Summary */
        .results-summary {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin: 2rem 0;
            color: white;
        }

        .results-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .filter-tag {
            background: var(--neon-accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Products Grid */
        .product-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            overflow: hidden;
            transition: all 0.4s ease;
            height: 100%;
            color: white;
            display: flex;
            flex-direction: column;
            min-height: 450px;
        }

        .product-card:hover {
            transform: translateY(-10px);
            border-color: var(--neon-accent);
            box-shadow: 0 20px 50px rgba(0, 212, 255, 0.2);
        }

        .product-image-container {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            flex-shrink: 0;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border: 2px dashed var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 3rem;
        }

        .product-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 170px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
            line-height: 1.3;
            height: 2.2rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            font-size: 0.85rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 2.8rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .product-category {
            background: rgba(255, 255, 255, 0.1);
            color: var(--neon-accent);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .vendor-name {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--neon-accent);
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
        }

        .stock-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .stock-in {
            background: var(--success-color);
            color: white;
        }

        .stock-out {
            background: var(--danger-color);
            color: white;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-top: auto;
        }

        .quantity-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: white;
            padding: 0.4rem;
            text-align: center;
            width: 60px;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--neon-accent);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
        }

        .add-to-cart-btn {
            background: var(--neon-accent);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            font-size: 0.8rem;
        }

        .add-to-cart-btn:hover {
            background: #00b8e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
        }

        .add-to-cart-btn:disabled {
            background: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Empty State */
        .empty-state {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }

        .empty-icon {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 2rem;
        }

        .empty-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-description {
            font-size: 1.1rem;
            opacity: 0.8;
            margin-bottom: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .product-card {
                min-height: 400px;
            }
            
            .product-image-container {
                height: 220px;
            }
            
            .product-body {
                min-height: 180px;
                padding: 1rem;
            }
            
            .product-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .quantity-input {
                width: 100%;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Success Message */
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .success-message.show {
            transform: translateX(0);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Bazario
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                    <a class="nav-link active" href="store.php">
                        <i class="fas fa-shopping-bag me-1"></i>Store
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'buyer'): ?>
                            <a class="nav-link" href="buyer/dashboard.php">
                                <i class="fas fa-user me-1"></i>My Account
                            </a>
                        <?php elseif ($_SESSION['role'] == 'vendor'): ?>
                            <a class="nav-link" href="vendor/dashboard.php">
                                <i class="fas fa-store me-1"></i>Vendor
                            </a>
                        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-cog me-1"></i>Admin
                            </a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="brand-badge mb-3">
                <i class="fas fa-star me-2"></i>Premium Marketplace
            </div>
            <h1 class="hero-title">Discover Amazing Products on <span class="brand-highlight">Bazario</span></h1>
            <p class="hero-subtitle">Explore our curated collection from trusted vendors worldwide</p>
            
            <!-- Search Box -->
            <div class="search-container">
                <form method="GET" class="search-box">
                    <input type="text" class="search-input" name="search" 
                           placeholder="Search for products..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?= number_format($total_products) ?></div>
                <div class="stat-label">Products Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-number"><?= number_format($total_vendors) ?></div>
                <div class="stat-label">Active Vendors</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?= number_format($total_products) ?></div>
                <div class="stat-label">Items to Choose From</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <h3 class="filter-title"><i class="fas fa-filter me-2"></i>Refine Your Search</h3>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $category == $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="min_price" class="form-label">Min Price (₹)</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" 
                           step="0.01" min="0" value="<?= ($min_price !== null && $min_price > 0) ? $min_price : '' ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="max_price" class="form-label">Max Price (₹)</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" 
                           step="0.01" min="0" value="<?= ($max_price !== null) ? $max_price : '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-filter">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="store.php" class="btn btn-clear">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <h3 class="results-title">Products Found: <?= $total_products ?></h3>
            <?php if (!empty($search) || !empty($category) || ($min_price !== null && $min_price > 0) || ($max_price !== null)): ?>
                <p class="mb-2">Filtered by:</p>
                <div class="filter-tags">
                    <?php 
                    if (!empty($search)) echo '<span class="filter-tag">Search: "' . htmlspecialchars($search) . '"</span>';
                    if (!empty($category)) echo '<span class="filter-tag">Category: ' . htmlspecialchars($category) . '</span>';
                    if ($min_price !== null && $min_price > 0) echo '<span class="filter-tag">Min Price: ₹' . number_format($min_price, 2) . '</span>';
                    if ($max_price !== null) echo '<span class="filter-tag">Max Price: ₹' . number_format($max_price, 2) . '</span>';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Grid -->
        <?php if ($total_products > 0): ?>
            <div class="row g-4">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-6 col-xl-3">
                        <div class="product-card">
                            <!-- Product Image -->
                            <div class="product-image-container">
                                <?php if (!empty($product['image']) && file_exists("assets/uploads/" . $product['image'])): ?>
                                    <img src="assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-body">
                                <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="product-description">
                                    <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...
                                </p>
                                
                                <div class="product-meta">
                                    <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                                    <small class="vendor-name">
                                        <i class="fas fa-store me-1"></i><?= htmlspecialchars($product['vendor_name']) ?>
                                    </small>
                                </div>
                                
                                <div class="product-price">₹<?= number_format($product['price'], 2) ?></div>
                                
                                <span class="stock-status <?= $product['stock'] > 0 ? 'stock-in' : 'stock-out' ?>">
                                    <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                                </span>
                                
                                <div class="product-actions">
                                    <?php if ($product['stock'] > 0): ?>
                                        <input type="number" class="quantity-input" 
                                               id="qty_<?= $product['id'] ?>" 
                                               value="1" min="1" max="<?= $product['stock'] ?>">
                                        <button class="add-to-cart-btn" onclick="addToCart(<?= $product['id'] ?>)">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="add-to-cart-btn" disabled>
                                            <i class="fas fa-times me-2"></i>Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h4 class="empty-title">No Products Found</h4>
                <p class="empty-description">Try adjusting your search criteria or browse all products.</p>
                <a href="store.php" class="btn btn-filter">
                    <i class="fas fa-eye me-2"></i>View All Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Success Message -->
    <div id="successMessage" class="success-message">
        <i class="fas fa-check-circle me-2"></i>
        <span id="successText">Product added to cart successfully!</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer'): ?>
                alert('Please login as a buyer to add items to cart.');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            const quantityInput = document.getElementById('qty_' + productId);
            const quantity = quantityInput.value;
            
            if (quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }
            
            // Disable button and show loading state
            const button = quantityInput.nextElementSibling;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            // Send AJAX request
            fetch('buyer/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    // Reset quantity to 1
                    quantityInput.value = 1;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            })
            .finally(() => {
                // Re-enable button
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
            });
        }

        function showSuccessMessage(message) {
            const successDiv = document.getElementById('successMessage');
            const successText = document.getElementById('successText');
            
            successText.textContent = message;
            successDiv.classList.add('show');
            
            setTimeout(() => {
                successDiv.classList.remove('show');
            }, 3000);
        }

        // Add smooth animations to product cards
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.product-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease';
                card.style.transitionDelay = (index * 0.1) + 's';
                
                observer.observe(card);
            });
        });

        // Add hover effects to filter inputs
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
