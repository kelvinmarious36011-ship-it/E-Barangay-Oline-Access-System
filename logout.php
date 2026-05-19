<?php
require_once 'config.php';
// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out — <?= SITE_FULL ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- Prevent browser back cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card logout-card">
        <div class="logout-icon">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>
        <h1 class="auth-title">You've Been Logged Out</h1>
        <p class="auth-subtitle">Your session has been securely ended. Thank you for using <?= SITE_FULL ?>.</p>
        <div class="logout-countdown">
            Redirecting to login in <span id="countdownTimer">5</span> seconds…
        </div>
        <div class="logout-progress-bar">
            <div class="logout-progress-fill" id="logoutProgress"></div>
        </div>
        <a href="login.php" class="btn btn-primary btn-block" style="margin-top:1.5rem;">
            Login Again
        </a>
        <div class="auth-footer-text" style="margin-top:1rem;">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</div>
<script>
    // Prevent back navigation
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function() {
        history.pushState(null, null, location.href);
    });

    // Countdown redirect
    let seconds = 5;
    const timerEl = document.getElementById('countdownTimer');
    const progressEl = document.getElementById('logoutProgress');

    const tick = setInterval(() => {
        seconds--;
        timerEl.textContent = seconds;
        const pct = ((5 - seconds) / 5) * 100;
        progressEl.style.width = pct + '%';
        if (seconds <= 0) {
            clearInterval(tick);
            window.location.replace('login.php');
        }
    }, 1000);
</script>
</body>
</html>