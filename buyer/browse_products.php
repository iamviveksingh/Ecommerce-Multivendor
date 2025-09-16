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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;

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

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price < 999999) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content {
            background: transparent;
            min-height: 100vh;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .image-placeholder {
            width: 100%;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 15px 15px 0 0;
        }
        .filter-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
                        <a class="nav-link active" href="browse_products.php">
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
                        <h2><i class="fas fa-search me-2"></i>Browse Products</h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <!-- Search and Filters -->
                    <div class="filter-section">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                <?php echo $category == $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="min_price" class="form-label">Min Price (₹)</label>
                                <input type="number" class="form-control" id="min_price" name="min_price" 
                                       step="0.01" min="0" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="max_price" class="form-label">Max Price (₹)</label>
                                <input type="number" class="form-control" id="max_price" name="max_price" 
                                       step="0.01" min="0" value="<?php echo $max_price < 999999 ? $max_price : ''; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100 me-2">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="browse_products.php" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Results Summary -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Products (<?php echo $products->num_rows; ?> found)</h4>
                        <?php if (!empty($search) || !empty($category) || $min_price > 0 || $max_price < 999999): ?>
                            <small class="text-muted">
                                Filtered by: 
                                <?php 
                                $filters = [];
                                if (!empty($search)) $filters[] = "Search: '$search'";
                                if (!empty($category)) $filters[] = "Category: $category";
                                if ($min_price > 0) $filters[] = "Min Price: ₹$min_price";
                                if ($max_price < 999999) $filters[] = "Max Price: ₹$max_price";
                                echo implode(', ', $filters);
                                ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <!-- Products Grid -->
                    <?php if ($products->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="card product-card h-100">
                                        <!-- Product Image -->
                                        <?php if (!empty($product['image']) && file_exists("../assets/uploads/" . $product['image'])): ?>
                                            <img src="../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="image-placeholder">
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-3x mb-2"></i>
                                                    <p class="mb-0">No Image</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="card-text text-muted flex-grow-1">
                                                <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...
                                            </p>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category']); ?></span>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($product['vendor_name']); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="h5 text-primary mb-0">₹<?php echo number_format($product['price'], 2); ?></span>
                                                <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                                </span>
                                            </div>
                                            
                                                                            <div class="mt-auto">
                                    <?php if ($product['stock'] > 0): ?>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="number" class="form-control form-control-sm" 
                                                       id="qty_<?php echo $product['id']; ?>" 
                                                       value="1" min="1" max="<?php echo $product['stock']; ?>">
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-primary btn-sm w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-cart-plus me-1"></i>Add
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
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
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Products Found</h4>
                            <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                            <a href="browse_products.php" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>View All Products
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            const quantity = document.getElementById('qty_' + productId).value;
            
            if (quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            // Send AJAX request
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Reset quantity to 1
                    document.getElementById('qty_' + productId).value = 1;
                    // Optionally refresh cart count in navigation
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }
    </script>
</body>
</html>
