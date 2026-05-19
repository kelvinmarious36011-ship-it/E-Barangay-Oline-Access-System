<?php
require_once 'config.php';
// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') redirect('admin_dashboard.php');
    else redirect('residents_dashboard.php');
}

// Fetch pinned announcements for homepage
$announcements = $conn->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.admin_id = u.id ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_FULL ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="landing-body">

<nav class="landing-nav">
    <div class="landing-nav-inner">
        <div class="brand-logo">
            <div class="brand-icon-sm">
                <svg width="24" height="24" viewBox="0 0 28 28" fill="none">
                    <path d="M14 2L3 8v12l11 6 11-6V8L14 2z" fill="var(--accent)" opacity="0.9"/>
                    <path d="M14 2v24M3 8l11 6 11-6" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                </svg>
            </div>
            <span class="brand-name-lg"><?= SITE_NAME ?></span>
        </div>
        <div class="landing-nav-links">
            <a href="#services">Services</a>
            <a href="#announcements">Announcements</a>
            <a href="login.php" class="btn btn-ghost btn-sm">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="landing-hero">
    <div class="hero-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">🏛️ Official Digital Portal</div>
        <h1 class="hero-title">
            <span class="serif-italic">Your Barangay,</span><br>
            Now Online
        </h1>
        <p class="hero-sub">Request documents, stay informed with announcements, and access barangay services from the comfort of your home. Fast, transparent, and accessible 24/7.</p>
        <div class="hero-actions">
            <a href="register.php" class="btn btn-primary btn-lg">Get Started — Register Now</a>
            <a href="login.php" class="btn btn-ghost btn-lg">I Already Have an Account</a>
        </div>
        <div class="hero-stats">
            <?php
            $totalResidents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='resident'")->fetch_assoc()['c'];
            $totalRequests  = $conn->query("SELECT COUNT(*) as c FROM document_requests")->fetch_assoc()['c'];
            $totalApproved  = $conn->query("SELECT COUNT(*) as c FROM document_requests WHERE status='Approved' OR status='Released'")->fetch_assoc()['c'];
            ?>
            <div class="hero-stat">
                <span class="stat-number"><?= number_format($totalResidents) ?></span>
                <span class="stat-label">Registered Residents</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-number"><?= number_format($totalRequests) ?></span>
                <span class="stat-label">Requests Processed</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-number"><?= number_format($totalApproved) ?></span>
                <span class="stat-label">Documents Released</span>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="landing-section" id="services">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">Online Services</h2>
            <p class="section-sub">Request the following documents online — no need to fall in line.</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon sc-blue">📋</div>
                <h3>Barangay Clearance</h3>
                <p>Certification of good moral character and residency for employment, travel, and other purposes.</p>
                <a href="register.php" class="service-link">Request Now →</a>
            </div>
            <div class="service-card">
                <div class="service-icon sc-green">🤝</div>
                <h3>Certificate of Indigency</h3>
                <p>For residents applying for DSWD, PhilHealth, PAO, scholarship, burial assistance, and other benefits.</p>
                <a href="register.php" class="service-link">Request Now →</a>
            </div>
            <div class="service-card">
                <div class="service-icon sc-orange">🏪</div>
                <h3>Business Clearance</h3>
                <p>Barangay clearance for new or renewing business establishments operating within the barangay.</p>
                <a href="register.php" class="service-link">Request Now →</a>
            </div>
            <div class="service-card">
                <div class="service-icon sc-red">⚖️</div>
                <h3>Barangay Blotter</h3>
                <p>Official reporting and recording of incidents, complaints, and disputes within the community.</p>
                <a href="register.php" class="service-link">Request Now →</a>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="landing-section bg-tinted">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">How It Works</h2>
            <p class="section-sub">Three simple steps to get your barangay documents.</p>
        </div>
        <div class="steps-row">
            <div class="step-item">
                <div class="step-number">01</div>
                <h3>Create an Account</h3>
                <p>Register with your personal information and valid email address to create your resident account.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-item">
                <div class="step-number">02</div>
                <h3>Submit a Request</h3>
                <p>Choose the document type you need, fill in the required details, and submit your request online.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-item">
                <div class="step-number">03</div>
                <h3>Claim Your Document</h3>
                <p>Receive email and portal notifications when your document is ready. Pick it up at the barangay hall.</p>
            </div>
        </div>
    </div>
</section>

<!-- ANNOUNCEMENTS -->
<?php if (!empty($announcements)): ?>
<section class="landing-section" id="announcements">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">Latest Announcements</h2>
            <p class="section-sub">Stay updated with the latest news from the barangay.</p>
        </div>
        <div class="announcements-grid">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card <?= $ann['is_pinned'] ? 'pinned' : '' ?>">
                <?php if ($ann['is_pinned']): ?>
                <div class="pin-badge">📌 Pinned</div>
                <?php endif; ?>
                <div class="ann-category"><?= e($ann['category']) ?></div>
                <h3><?= e($ann['title']) ?></h3>
                <p><?= e(mb_strimwidth($ann['content'], 0, 150, '…')) ?></p>
                <div class="ann-meta"><?= formatDate($ann['created_at'], 'F j, Y') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="landing-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="brand-icon-sm">
                <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                    <path d="M14 2L3 8v12l11 6 11-6V8L14 2z" fill="var(--accent)" opacity="0.9"/>
                    <path d="M14 2v24M3 8l11 6 11-6" stroke="white" stroke-width="1.5"/>
                </svg>
            </div>
            <span><?= SITE_FULL ?></span>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= BARANGAY_NAME ?>, <?= BARANGAY_CITY ?>. All rights reserved.</p>
    </div>
</footer>

<script src="js/script.js"></script>
</body>
</html>