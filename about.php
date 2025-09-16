<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Bazario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        .main-content { background: transparent; min-height: 100vh; }
        .card { background: rgba(255,255,255,0.1) !important; backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2) !important; border-radius: 20px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.1) !important; }
        .section-title { color: #fff; font-weight: 800; }
        .lead { color: rgba(255,255,255,0.9); }
        .value-item { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 16px; padding: 18px; color: #fff; }
        .team-card { text-align: center; padding: 24px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(45deg, var(--neon-accent), #00ff88); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 28px; box-shadow: 0 8px 24px rgba(0,212,255,0.25); }
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
                    <a class="nav-link active" href="about.php"><i class="fas fa-info-circle me-1"></i>About</a>
                    <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i>Contact</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content py-5">
        <div class="pt-5"></div>
        <div class="row g-4">
            <div class="col-12">
                <div class="card p-5">
                    <h1 class="section-title mb-3"><i class="fas fa-heart me-2"></i>About Bazario</h1>
                    <p class="lead mb-0">Bazario is a modern multi‑vendor marketplace built to connect passionate sellers with savvy buyers. We focus on quality, trust, and a delightful shopping experience powered by a clean design and smooth performance.</p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card p-4 h-100">
                    <h4 class="text-white mb-3">Our Mission</h4>
                    <p class="mb-0 text-white-50">Empower local and global vendors to reach customers everywhere, while giving buyers a curated catalog of high‑quality products. We prioritize transparency, fair fees, and tools that help vendors grow.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card p-4 h-100">
                    <h4 class="text-white mb-3">Quick Facts</h4>
                    <ul class="mb-0 text-white-50">
                        <li>Secure payments and reliable order tracking</li>
                        <li>Vendor dashboards and insightful reports</li>
                        <li>Glass‑morphism Bazario theme for cohesive UX</li>
                    </ul>
                </div>
            </div>

            <div class="col-12">
                <div class="card p-4">
                    <h4 class="text-white mb-3">Our Values</h4>
                    <div class="row g-3">
                        <div class="col-md-4"><div class="value-item"><i class="fas fa-shield-check me-2"></i>Trust & Safety</div></div>
                        <div class="col-md-4"><div class="value-item"><i class="fas fa-leaf me-2"></i>Simplicity</div></div>
                        <div class="col-md-4"><div class="value-item"><i class="fas fa-bolt me-2"></i>Performance</div></div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card p-4 team-card">
                    <h4 class="text-white mb-4">The Team</h4>
                    <div class="d-flex justify-content-center gap-4 flex-wrap">
                        <div>
                            <div class="avatar">B</div>
                            <div class="mt-2 text-white">Bazario Team</div>
                            <div class="text-white-50 small">Product & Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>


