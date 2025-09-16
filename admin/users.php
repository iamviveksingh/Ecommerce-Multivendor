<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Get user role first
    $get_user = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $get_user->bind_param("i", $user_id);
    $get_user->execute();
    $user_result = $get_user->get_result();
    
    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
        
        if ($user['role'] == 'admin') {
            $error_message = "Cannot delete admin users!";
        } else {
            // If vendor, delete their products first
            if ($user['role'] == 'vendor') {
                $delete_products = $conn->prepare("DELETE FROM products WHERE vendor_id = ?");
                $delete_products->bind_param("i", $user_id);
                $delete_products->execute();
                $delete_products->close();
            }
            
            // Delete user
            $delete_user = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $delete_user->bind_param("i", $user_id);
            
            if ($delete_user->execute()) {
                $success_message = "User deleted successfully!";
            } else {
                $error_message = "Error deleting user: " . $conn->error;
            }
            $delete_user->close();
        }
    } else {
        $error_message = "User not found!";
    }
    $get_user->close();
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all users with filters
$users_query = "SELECT id, name, email, role, phone, address, created_at FROM users $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($users_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users_result = $stmt->get_result();

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_vendors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'];
$total_buyers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'buyer'")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Bazario Admin</title>
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

        /* User Cards */
        .user-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .user-card::after {
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

        .user-card:hover::after {
            opacity: 1;
        }

        .user-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-neon), var(--success-neon));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .role-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: linear-gradient(45deg, var(--danger-neon), #ff6b6b);
            color: white;
        }

        .role-vendor {
            background: linear-gradient(45deg, var(--success-neon), #00d4aa);
            color: white;
        }

        .role-buyer {
            background: linear-gradient(45deg, var(--accent-neon), #0099cc);
            color: white;
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

        /* Action Buttons */
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
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
                        <a class="nav-link active" href="users.php">
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
                                <i class="fas fa-users me-2"></i>Manage Users
                            </h2>
                            <p class="text-white-50 mb-0">Manage all user accounts and permissions</p>
                        </div>
                        <a href="dashboard.php" class="btn glass-card text-white border-0">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3 loading" style="animation-delay: 0.1s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_users; ?></div>
                                <p class="text-white-50 mb-0">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.2s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_vendors; ?></div>
                                <p class="text-white-50 mb-0">Vendors</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.3s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_buyers; ?></div>
                                <p class="text-white-50 mb-0">Buyers</p>
                            </div>
                        </div>
                        <div class="col-md-3 loading" style="animation-delay: 0.4s;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_admins; ?></div>
                                <p class="text-white-50 mb-0">Admins</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section loading" style="animation-delay: 0.5s;">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="text-white mb-3">Filter Users</h5>
                                <div class="mb-3">
                                    <a href="users.php" class="filter-chip <?php echo empty($role_filter) ? 'active' : ''; ?>">
                                        All Users
                                    </a>
                                    <a href="users.php?role=buyer" class="filter-chip <?php echo $role_filter == 'buyer' ? 'active' : ''; ?>">
                                        Buyers
                                    </a>
                                    <a href="users.php?role=vendor" class="filter-chip <?php echo $role_filter == 'vendor' ? 'active' : ''; ?>">
                                        Vendors
                                    </a>
                                    <a href="users.php?role=admin" class="filter-chip <?php echo $role_filter == 'admin' ? 'active' : ''; ?>">
                                        Admins
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="search-input" name="search" 
                                           placeholder="Search by name, email, or phone..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Users Grid -->
                    <div class="row">
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php $delay = 0.6; ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 loading" style="animation-delay: <?php echo $delay; ?>s;">
                                    <div class="user-card">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        
                                        <h5 class="text-white mb-2"><?php echo htmlspecialchars($user['name']); ?></h5>
                                        <p class="text-white-50 mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                                        
                                        <div class="mb-3">
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-white-50">
                                                <i class="fas fa-calendar me-1"></i>
                                                Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-white-50">ID: #<?php echo $user['id']; ?></small>
                                            
                                            <?php if ($user['role'] != 'admin'): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="action-btn danger" title="Delete User">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-white-50">
                                                    <i class="fas fa-shield-alt"></i> Protected
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $delay += 0.1; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 loading" style="animation-delay: 0.6s;">
                                <div class="glass-card p-5 text-center">
                                    <i class="fas fa-users fa-4x text-white-50 mb-3"></i>
                                    <h4 class="text-white mb-2">No Users Found</h4>
                                    <p class="text-white-50">Try adjusting your search criteria or filters.</p>
                                    <a href="users.php" class="btn glass-card text-white border-0">
                                        <i class="fas fa-refresh me-2"></i>Clear Filters
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Results Summary -->
                    <?php if ($users_result->num_rows > 0): ?>
                        <div class="mt-4 loading" style="animation-delay: 0.8s;">
                            <div class="glass-card p-3 text-center">
                                <p class="text-white-50 mb-0">
                                    Showing <?php echo $users_result->num_rows; ?> user(s)
                                    <?php if (!empty($search) || !empty($role_filter)): ?>
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

        // Add hover effects to user cards
        document.querySelectorAll('.user-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
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

        // Test delete button functionality
        console.log('Testing delete button functionality...');
        const deleteButtons = document.querySelectorAll('button[name*="delete"]');
        console.log('Found', deleteButtons.length, 'delete buttons');
        deleteButtons.forEach((btn, index) => {
            console.log(`Delete button ${index}:`, btn);
            console.log('Button text:', btn.textContent.trim());
            console.log('Button classes:', btn.className);
            console.log('Button type:', btn.type);
            console.log('Button name:', btn.name);
        });

        // Filter chip animations
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
