<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content { background: transparent; min-height: 100vh; }
        .card { background: rgba(255,255,255,0.1) !important; backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2) !important; border-radius: 20px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.1) !important; }
        .form-control, .form-select { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; color: #fff !important; border-radius: 12px !important; }
        .form-control::placeholder { color: rgba(255,255,255,0.7) !important; }
        .form-control:focus { background: rgba(255,255,255,0.15) !important; border-color: #00d4ff !important; box-shadow: 0 0 0 0.2rem rgba(0,212,255,0.25) !important; color: #fff !important; }
        .info-item { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 16px; padding: 18px; color: #fff; }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-store me-2"></i>Bazario</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="store.php"><i class="fas fa-shopping-bag me-1"></i>Store</a>
                    <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i>About</a>
                    <a class="nav-link active" href="contact.php"><i class="fas fa-envelope me-1"></i>Contact</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content py-5">
        <div class="pt-5"></div>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card p-4 h-100">
                    <h2 class="text-white mb-3"><i class="fas fa-envelope me-2"></i>Get in Touch</h2>
                    <form method="post" action="#" onsubmit="event.preventDefault(); this.reset(); alert('Thanks! We will get back to you.');">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">Your Name</label>
                                <input type="text" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">Email</label>
                                <input type="email" class="form-control" placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Subject</label>
                            <input type="text" class="form-control" placeholder="How can we help?" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white">Message</label>
                            <textarea class="form-control" rows="6" placeholder="Write your message..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card p-4 h-100">
                    <h4 class="text-white mb-3">Contact Information</h4>
                    <div class="info-item mb-3"><i class="fas fa-map-marker-alt me-2"></i>123 Bazario Street, Commerce City</div>
                    <div class="info-item mb-3"><i class="fas fa-phone me-2"></i>+1 555 123 4567</div>
                    <div class="info-item mb-3"><i class="fas fa-envelope me-2"></i>support@bazario.com</div>
                    <div class="info-item"><i class="fas fa-clock me-2"></i>Mon - Fri: 9:00 AM - 6:00 PM</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


