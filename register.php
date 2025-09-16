<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $phone, $address);
                    
                    if ($insert_stmt->execute()) {
                        $success = 'Registration successful! You can now login.';
                    } else {
                        $error = 'Registration failed: ' . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }
        /* Ensure role dropdown matches themed selects */
        .form-select {
            background: var(--glass-bg) !important;
            border: 1px solid var(--glass-border) !important;
            color: var(--text-primary) !important;
            border-radius: 15px !important;
        }
        .form-select:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: var(--neon-accent) !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25) !important;
            color: var(--text-primary) !important;
            outline: none !important;
        }
        .form-select option {
            background: #2c3e50 !important;
            color: var(--text-primary) !important;
            padding: 10px !important;
        }
 
        .register-container {
            position: relative;
            z-index: 2;
        }
        
        .register-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .register-card:hover::before {
            left: 100%;
        }
        
        .register-header {
            text-align: center;
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .register-icon {
            width: 80px;
            height: 80px;
            background: var(--neon-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }
        
        .register-title {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .register-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            border-top: 1px solid var(--glass-border);
        }
        
        .register-footer a {
            color: var(--neon-accent);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .register-footer a:hover {
            color: #00b8e6;
            text-decoration: underline;
        }
        
        .back-home {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-home:hover {
            color: var(--text-primary);
            transform: translateX(-5px);
        }
        
        /* Floating Elements */
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .register-card {
                margin: 1rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .floating-element {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    
    <!-- Floating Background Elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="register-card">
                    <div class="register-header">
                        <div class="register-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h1 class="register-title">Create Account</h1>
                        <p class="register-subtitle">Join our community today</p>
                    </div>
                    
                    <div class="register-body">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="buyer">Buyer (Customer)</option>
                                        <option value="vendor">Vendor (Seller)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="register-footer">
                        <p class="mb-3">
                            Already have an account? 
                            <a href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login here
                            </a>
                        </p>
                        <a href="index.php" class="back-home">
                            <i class="fas fa-arrow-left"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const registerCard = document.querySelector('.register-card');
            const formInputs = document.querySelectorAll('.form-control, .form-select');
            
            // Animate register card on page load
            registerCard.style.opacity = '0';
            registerCard.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                registerCard.style.transition = 'all 0.8s ease';
                registerCard.style.opacity = '1';
                registerCard.style.transform = 'translateY(0)';
            }, 100);

            // Add focus effects to form inputs
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Add typing effect to title
            const title = document.querySelector('.register-title');
            const originalText = title.textContent;
            title.textContent = '';
            
            let i = 0;
            const typeWriter = () => {
                if (i < originalText.length) {
                    title.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                }
            };
            
            setTimeout(typeWriter, 1000);
        });
    </script>
</body>
</html>
