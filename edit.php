<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'My Profile';
$user = getCurrentUser();
$userId = (int)$_SESSION['user_id'];

$success = getFlash('success');
$error   = getFlash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $data = [
            'full_name'      => trim($_POST['full_name'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'address'        => trim($_POST['address'] ?? ''),
            'date_of_birth'  => $_POST['date_of_birth'] ?? '',
            'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
            'civil_status'   => $_POST['civil_status'] ?? 'Single',
            'gender'         => $_POST['gender'] ?? 'Male',
            'citizenship'    => trim($_POST['citizenship'] ?? 'Filipino'),
            'cedula_number'  => trim($_POST['cedula_number'] ?? ''),
        ];
        $dob = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
        $stmt = $conn->prepare("UPDATE users SET full_name=?,phone=?,address=?,date_of_birth=?,place_of_birth=?,civil_status=?,gender=?,citizenship=?,cedula_number=? WHERE id=?");
        $stmt->bind_param('sssssssssi',
            $data['full_name'], $data['phone'], $data['address'], $dob,
            $data['place_of_birth'], $data['civil_status'],
            $data['gender'], $data['citizenship'], $data['cedula_number'], $userId
        );
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $data['full_name'];
            setFlash('success', 'Profile updated successfully.');
        } else {
            setFlash('error', 'Failed to update profile. Please try again.');
        }
        redirect('edit.php');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newpw   = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newpw) < 6) {
            setFlash('error', 'New password must be at least 6 characters.');
        } elseif ($newpw !== $confirm) {
            setFlash('error', 'New passwords do not match.');
        } else {
            $hashed = password_hash($newpw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hashed, $userId);
            $stmt->execute();
            setFlash('success', 'Password changed successfully.');
        }
        redirect('edit.php');
    }
}

// Re-fetch after potential update
$user = getCurrentUser();
$basePath = '';
include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-desc">Update your personal information and account settings.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible"><?= e($success) ?> <button class="alert-close">&times;</button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error alert-dismissible"><?= e($error) ?> <button class="alert-close">&times;</button></div>
<?php endif; ?>

<div class="tabs-wrapper">
    <div class="tabs-nav">
        <button class="tab-btn active" data-tab="profile">Personal Info</button>
        <button class="tab-btn" data-tab="password">Change Password</button>
    </div>

    <!-- PROFILE TAB -->
    <div class="tab-panel active" id="tab-profile">
        <div class="card">
            <div class="card-header"><h3>Personal Information</h3></div>
            <div class="card-body">
                <form method="POST" action="edit.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Full Name <span class="req">*</span></label>
                            <input type="text" name="full_name" class="form-input" value="<?= e($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
                            <small class="form-hint">Email cannot be changed.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-input" value="<?= e($user['phone']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-input form-select">
                                <?php foreach (['Male','Female','Other'] as $g): ?>
                                <option value="<?= $g ?>" <?= $user['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-input" value="<?= e($user['date_of_birth']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Place of Birth</label>
                            <input type="text" name="place_of_birth" class="form-input" value="<?= e($user['place_of_birth']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Civil Status</label>
                            <select name="civil_status" class="form-input form-select">
                                <?php foreach (['Single','Married','Widowed','Separated','Annulled'] as $cs): ?>
                                <option value="<?= $cs ?>" <?= $user['civil_status'] === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Citizenship</label>
                            <input type="text" name="citizenship" class="form-input" value="<?= e($user['citizenship']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cedula Number</label>
                            <input type="text" name="cedula_number" class="form-input" value="<?= e($user['cedula_number']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Complete Address</label>
                        <textarea name="address" class="form-input form-textarea" rows="2"><?= e($user['address']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PASSWORD TAB -->
    <div class="tab-panel" id="tab-password">
        <div class="card">
            <div class="card-header"><h3>Change Password</h3></div>
            <div class="card-body">
                <form method="POST" action="edit.php" style="max-width:480px">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="current_password" id="cur_pw" class="form-input" required placeholder="Enter current password">
                            <button type="button" class="pw-toggle" data-target="cur_pw" tabindex="-1">
                                <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="new_password" id="new_pw" class="form-input" required placeholder="Min. 6 characters">
                            <button type="button" class="pw-toggle" data-target="new_pw" tabindex="-1">
                                <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="confirm_password" id="conf_pw" class="form-input" required placeholder="Re-enter new password">
                            <button type="button" class="pw-toggle" data-target="conf_pw" tabindex="-1">
                                <svg class="eye-icon eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-icon eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>