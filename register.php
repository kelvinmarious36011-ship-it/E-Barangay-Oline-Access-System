<?php
require_once 'config.php';
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'residents_dashboard.php');
}

$error   = getFlash('error');
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'      => trim($_POST['full_name'] ?? ''),
        'email'          => trim($_POST['email'] ?? ''),
        'password'       => $_POST['password'] ?? '',
        'confirm_pw'     => $_POST['confirm_password'] ?? '',
        'phone'          => trim($_POST['phone'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
        'date_of_birth'  => $_POST['date_of_birth'] ?? '',
        'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
        'civil_status'   => $_POST['civil_status'] ?? 'Single',
        'gender'         => $_POST['gender'] ?? 'Male',
        'citizenship'    => trim($_POST['citizenship'] ?? 'Filipino'),
        'cedula_number'  => trim($_POST['cedula_number'] ?? ''),
    ];
    saveOld($data);

    $errors = [];
    if (empty($data['full_name']))   $errors[] = 'Full name is required.';
    if (empty($data['email']))       $errors[] = 'Email address is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($data['password'] !== $data['confirm_pw']) $errors[] = 'Passwords do not match.';
    if (empty($data['address']))     $errors[] = 'Address is required.';

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param('s', $data['email']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name,email,password,phone,address,date_of_birth,place_of_birth,civil_status,gender,citizenship,cedula_number,role,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'resident','active')");
        $dob = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
        $stmt->bind_param('sssssssssss',
            $data['full_name'], $data['email'], $hashed,
            $data['phone'], $data['address'], $dob,
            $data['place_of_birth'], $data['civil_status'],
            $data['gender'], $data['citizenship'], $data['cedula_number']
        );
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            createNotification($newId, 'Welcome to E-Barangay!', 'Your account has been successfully created. You can now request barangay documents online.', 'system');
            clearOld();
            setFlash('success', 'Registration successful! You can now log in.');
            redirect('login.php');
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }

    if (!empty($errors)) {
        setFlash('error', implode(' ', $errors));
        redirect('register.php');
    }
}

$oldData = $_SESSION['old'] ?? [];
clearOld();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= SITE_FULL ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
<div class="auth-container auth-wide">
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

        <h1 class="auth-title">Create Your Account</h1>
        <p class="auth-subtitle">Register as a resident to access barangay services online.</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" novalidate>
            <div class="form-section-label">Personal Information</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-input"
                        placeholder="Juan dela Cruz"
                        value="<?= e($oldData['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="form-input"
                        placeholder="your@email.com"
                        value="<?= e($oldData['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-input"
                        placeholder="09XXXXXXXXX"
                        value="<?= e($oldData['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-input form-select">
                        <option value="Male"   <?= ($oldData['gender'] ?? 'Male') === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($oldData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other"  <?= ($oldData['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-input"
                        value="<?= e($oldData['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="place_of_birth">Place of Birth</label>
                    <input type="text" id="place_of_birth" name="place_of_birth" class="form-input"
                        placeholder="City/Municipality"
                        value="<?= e($oldData['place_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="civil_status">Civil Status</label>
                    <select id="civil_status" name="civil_status" class="form-input form-select">
                        <?php foreach (['Single','Married','Widowed','Separated','Annulled'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= ($oldData['civil_status'] ?? 'Single') === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="citizenship">Citizenship</label>
                    <input type="text" id="citizenship" name="citizenship" class="form-input"
                        placeholder="Filipino"
                        value="<?= e($oldData['citizenship'] ?? 'Filipino') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cedula_number">Cedula Number</label>
                    <input type="text" id="cedula_number" name="cedula_number" class="form-input"
                        placeholder="CED-XXXX-XXXXX"
                        value="<?= e($oldData['cedula_number'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="address">Complete Address <span class="req">*</span></label>
                <textarea id="address" name="address" class="form-input form-textarea" rows="2"
                    placeholder="House No., Street, Barangay, City/Municipality"><?= e($oldData['address'] ?? '') ?></textarea>
            </div>

            <div class="form-section-label">Login Credentials</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="req">*</span></label>
                    <div class="input-password-wrapper">
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Min. 6 characters" required>
                        <button type="button" class="pw-toggle" data-target="password" tabindex="-1">
                            <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password <span class="req">*</span></label>
                    <div class="input-password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                            placeholder="Re-enter password" required>
                        <button type="button" class="pw-toggle" data-target="confirm_password" tabindex="-1">
                            <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>

        <div class="auth-footer-text">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
        <div class="auth-footer-text">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>