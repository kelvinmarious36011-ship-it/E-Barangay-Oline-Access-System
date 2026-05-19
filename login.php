<?php
require_once 'config.php';
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'residents_dashboard.php');
}

$error = getFlash('error');
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    saveOld(['email' => $email]);

    if (empty($email) || empty($password)) {
        setFlash('error', 'Please enter both email and password.');
        redirect('login.php');
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            setFlash('error', 'Your account has been deactivated. Please contact the barangay office.');
            redirect('login.php');
        }
        // Regenerate session on login
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['fullname'] = $user['full_name'];
        clearOld();

        if ($user['role'] === 'admin') redirect('admin_dashboard.php');
        else redirect('residents_dashboard.php');
    } else {
        setFlash('error', 'Incorrect email or password. Please try again.');
        redirect('login.php');
    }
}

$oldEmail = old('email');
clearOld();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= SITE_FULL ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
                    <path d="M14 2L3 8v12l11 6 11-6V8L14 2z" fill="var(--accent)"/>
                    <path d="M14 2v24M3 8l11 6 11-6" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <div class="auth-brand-name"><?= SITE_NAME ?></div>
                <div class="auth-brand-sub"><?= BARANGAY_NAME ?></div>
            </div>
        </div>

        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to access your resident portal</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form" autocomplete="off" novalidate>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                    placeholder="your@email.com"
                    value="<?= $oldEmail ?>"
                    required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password" name="password" class="form-input"
                        placeholder="Enter your password"
                        required autocomplete="current-password">
                    <button type="button" class="pw-toggle" data-target="password" tabindex="-1" aria-label="Show/Hide Password">
                        <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>

        <div class="auth-footer-text">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        <div class="auth-footer-text">
            <a href="index.php">← Back to Home</a>
        </div>

        <div class="auth-demo-box">
            <strong>Demo Accounts</strong>
            <div>Admin: admin@ebarangay.gov.ph / password</div>
            <div>Resident: juan@example.com / password</div>
        </div>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>