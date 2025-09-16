<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (!empty($category_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $description);
            
            if ($stmt->execute()) {
                $success_message = "Category '$category_name' added successfully!";
            } else {
                $error_message = "Error adding category: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "Category name cannot be empty!";
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (!empty($category_name)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $category_name, $description, $category_id);
            
            if ($stmt->execute()) {
                $success_message = "Category updated successfully!";
            } else {
                $error_message = "Error updating category: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "Category name cannot be empty!";
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        // Check if category is used by any products
        $check_products = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check_products->bind_param("i", $category_id);
        $check_products->execute();
        $product_count = $check_products->get_result()->fetch_assoc()['count'];
        $check_products->close();
        
        if ($product_count > 0) {
            $error_message = "Cannot delete category. It is used by $product_count product(s).";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                $success_message = "Category deleted successfully!";
            } else {
                $error_message = "Error deleting category: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get categories with product counts
$categories_query = "SELECT 
    c.*,
    COUNT(p.id) as product_count,
    COALESCE(SUM(p.stock), 0) as total_stock,
    COALESCE(SUM(p.price * p.stock), 0) as inventory_value
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name";

$categories_result = $conn->query($categories_query);

// Get statistics
$total_categories = $categories_result->num_rows;
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$categories_with_products = $conn->query("SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL")->fetch_assoc()['count'];
$empty_categories = $total_categories - $categories_with_products;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Bazario Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a;
            --secondary-bg: #1a1a1a;
            --accent-neon: #00d4ff;
            --success-neon: #00ff88;
            --warning-neon: #ffaa00;
            --danger-neon: #ff4757;
            --primary-glow: #667eea;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
        }

        body {
            background: linear-gradient(135deg, var(--primary-bg) 0%, var(--secondary-bg) 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            color: var(--text-secondary);
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-neon);
            transform: translateX(5px);
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
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Animated Statistics */
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
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

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--accent-neon), var(--success-neon));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.8;
            transition: all 0.3s;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            opacity: 1;
        }

        /* Category Cards */
        .category-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-neon), var(--success-neon));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .category-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent-neon);
            margin-bottom: 10px;
        }

        .category-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--success-neon);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Action Buttons */
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            margin: 2px;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--accent-neon);
            color: var(--accent-neon);
            transform: translateY(-2px);
        }

        .action-btn.text-danger:hover {
            border-color: var(--danger-neon);
            color: var(--danger-neon);
        }

        .action-btn.text-warning:hover {
            border-color: var(--warning-neon);
            color: var(--warning-neon);
        }

        /* Form Styling */
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent-neon);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--accent-neon), var(--primary-glow));
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.3);
        }

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

        /* Modal Styling */
        .modal-content {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            color: var(--text-primary);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-close {
            filter: invert(1);
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
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i>All Products
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag me-2"></i>All Orders
                        </a>
                        <a class="nav-link" href="vendors.php">
                            <i class="fas fa-store me-2"></i>Vendor Management
                        </a>
                        <a class="nav-link active" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
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
                                <i class="fas fa-tags me-2"></i>Categories Management
                            </h2>
                            <p class="text-white-50 mb-0">Organize your product catalog with categories</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
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

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3 loading" style="animation-delay: 0.1s;">
                            <div class="stat-card">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="stat-number"><?php echo $total_categories; ?></div>
                                <p class="text-white-50 mb-0">Total Categories</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.2s;">
                            <div class="stat-card">
                                <div class="stat-icon text-success">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stat-number"><?php echo $total_products; ?></div>
                                <p class="text-white-50 mb-0">Total Products</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.3s;">
                            <div class="stat-card">
                                <div class="stat-icon text-info">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="stat-number"><?php echo $categories_with_products; ?></div>
                                <p class="text-white-50 mb-0">Active Categories</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.4s;">
                            <div class="stat-card">
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="stat-number"><?php echo $empty_categories; ?></div>
                                <p class="text-white-50 mb-0">Empty Categories</p>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Grid -->
                    <div class="row g-4">
                        <?php if ($categories_result->num_rows > 0): ?>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <div class="col-lg-4 col-md-6 loading" style="animation-delay: 0.2s;">
                                    <div class="category-card">
                                        <div class="category-name">
                                            <i class="fas fa-tag me-2"></i>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                        
                                        <?php if (!empty($category['description'])): ?>
                                            <div class="category-description">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="category-stats">
                                            <div class="stat-item">
                                                <div class="stat-value"><?php echo $category['product_count']; ?></div>
                                                <div class="stat-label">Products</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-value"><?php echo $category['total_stock']; ?></div>
                                                <div class="stat-label">Stock</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-value">₹<?php echo number_format($category['inventory_value'], 2); ?></div>
                                                <div class="stat-label">Value</div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button class="action-btn text-warning" 
                                                    onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description'] ?? ''); ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </button>
                                            
                                            <?php if ($category['product_count'] == 0): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" name="delete_category" class="action-btn text-danger">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="action-btn text-secondary" style="cursor: not-allowed;">
                                                    <i class="fas fa-lock me-2"></i>In Use
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 loading" style="animation-delay: 0.2s;">
                                <div class="glass-card p-5 text-center">
                                    <i class="fas fa-tags fa-4x text-white-50 mb-3"></i>
                                    <h4 class="text-white mb-2">No Categories Found</h4>
                                    <p class="text-white-50 mb-3">Start organizing your products by creating categories.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus me-2"></i>Create First Category
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_category_id" name="category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_category" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit category function
        function editCategory(id, name, description) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_description').value = description;
        }

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

        // Animated counters
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                const increment = target / 100;
                let current = 0;
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateCounters();
        });
    </script>
</body>
</html>
