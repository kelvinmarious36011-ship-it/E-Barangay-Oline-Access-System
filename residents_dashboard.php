<?php
require_once 'config.php';
requireResident();
$pageTitle = 'My Dashboard';
$basePath  = '';
$userId    = (int)$_SESSION['user_id'];
$user      = getCurrentUser();

// Stats
$myTotal     = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE user_id=$userId")->fetch_assoc()['c'];
$myPending   = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE user_id=$userId AND status='Pending'")->fetch_assoc()['c'];
$myApproved  = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE user_id=$userId AND (status='Approved' OR status='Released')")->fetch_assoc()['c'];
$myProcessing= $conn->query("SELECT COUNT(*) c FROM document_requests WHERE user_id=$userId AND status='Processing'")->fetch_assoc()['c'];

// Recent requests
$myReqs = $conn->query("SELECT * FROM document_requests WHERE user_id=$userId ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Latest 2 announcements
$latestAnn = $conn->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.admin_id=u.id ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 2")->fetch_all(MYSQLI_ASSOC);

include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?>! 👋</h1>
        <p class="page-desc">Manage your barangay documents and stay updated. <?= date('l, F j, Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="request_documents.php" class="btn btn-primary">+ Request Document</a>
    </div>
</div>

<!-- PROFILE COMPLETION BANNER -->
<?php
$incomplete = empty($user['date_of_birth']) || empty($user['cedula_number']) || empty($user['phone']);
if ($incomplete): ?>
<div class="alert alert-info" style="display:flex;align-items:center;gap:1rem;">
    <span>ℹ️</span>
    <span>Your profile is incomplete. <a href="edit.php">Complete your profile</a> for faster document processing.</span>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= $myTotal ?></div>
            <div class="stat-card-label">Total Requests</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= $myPending ?></div>
            <div class="stat-card-label">Pending</div>
        </div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= $myProcessing ?></div>
            <div class="stat-card-label">Processing</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= $myApproved ?></div>
            <div class="stat-card-label">Approved</div>
        </div>
    </div>
</div>

<div class="dashboard-row">
    <!-- RECENT REQUESTS -->
    <div class="card dashboard-card-wide">
        <div class="card-header">
            <h3>My Recent Requests</h3>
            <a href="view_request.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($myReqs)): ?>
            <div class="empty-state-sm">
                <p>You haven't made any requests yet.</p>
                <a href="request_documents.php" class="btn btn-primary btn-sm">Request a Document</a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Document Type</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myReqs as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><?= documentTypeLabel($r['document_type']) ?></td>
                            <td><?= e(mb_strimwidth($r['purpose'] ?? '—', 0, 40, '…')) ?></td>
                            <td><?= statusBadge($r['status']) ?></td>
                            <td><?= formatDate($r['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QUICK LINKS + ANNOUNCEMENTS -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
        <div class="card">
            <div class="card-header"><h3>Quick Links</h3></div>
            <div class="card-body">
                <div class="quick-links-list">
                    <a href="request_documents.php?type=barangay_clearance" class="quick-link-item">
                        <span class="ql-icon">📋</span>
                        <span>Barangay Clearance</span>
                        <span class="ql-arrow">›</span>
                    </a>
                    <a href="request_documents.php?type=certificate_of_indigency" class="quick-link-item">
                        <span class="ql-icon">🤝</span>
                        <span>Certificate of Indigency</span>
                        <span class="ql-arrow">›</span>
                    </a>
                    <a href="request_documents.php?type=business_clearance" class="quick-link-item">
                        <span class="ql-icon">🏪</span>
                        <span>Business Clearance</span>
                        <span class="ql-arrow">›</span>
                    </a>
                    <a href="request_documents.php?type=barangay_blotter" class="quick-link-item">
                        <span class="ql-icon">⚖️</span>
                        <span>Barangay Blotter</span>
                        <span class="ql-arrow">›</span>
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($latestAnn)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Latest Announcements</h3>
                <a href="announcement.php" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php foreach ($latestAnn as $ann): ?>
                <div class="mini-announcement">
                    <?php if ($ann['is_pinned']): ?><span class="pin-sm">📌</span><?php endif; ?>
                    <div class="mini-ann-title"><?= e($ann['title']) ?></div>
                    <div class="mini-ann-body"><?= e(mb_strimwidth($ann['content'], 0, 90, '…')) ?></div>
                    <div class="mini-ann-date"><?= timeAgo($ann['created_at']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MY PROFILE CARD -->
<div class="card" style="margin-top:1.5rem">
    <div class="card-header">
        <h3>My Profile Summary</h3>
        <a href="edit.php" class="btn btn-ghost btn-sm">Edit Profile</a>
    </div>
    <div class="card-body">
        <div class="profile-grid">
            <div class="profile-field"><span>Full Name</span><strong><?= e($user['full_name']) ?></strong></div>
            <div class="profile-field"><span>Email</span><strong><?= e($user['email']) ?></strong></div>
            <div class="profile-field"><span>Phone</span><strong><?= e($user['phone'] ?: '—') ?></strong></div>
            <div class="profile-field"><span>Gender</span><strong><?= e($user['gender'] ?: '—') ?></strong></div>
            <div class="profile-field"><span>Civil Status</span><strong><?= e($user['civil_status'] ?: '—') ?></strong></div>
            <div class="profile-field"><span>Date of Birth</span><strong><?= formatDate($user['date_of_birth']) ?></strong></div>
            <div class="profile-field"><span>Citizenship</span><strong><?= e($user['citizenship'] ?: '—') ?></strong></div>
            <div class="profile-field"><span>Cedula #</span><strong><?= e($user['cedula_number'] ?: '—') ?></strong></div>
            <div class="profile-field profile-field-wide"><span>Address</span><strong><?= e($user['address'] ?: '—') ?></strong></div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>