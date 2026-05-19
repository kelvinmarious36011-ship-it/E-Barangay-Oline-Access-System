<?php
require_once 'config.php';
requireAdmin();
$pageTitle = 'Admin Dashboard';
$basePath  = '';

// Stats
$totalResidents  = $conn->query("SELECT COUNT(*) c FROM users WHERE role='resident'")->fetch_assoc()['c'];
$activeResidents = $conn->query("SELECT COUNT(*) c FROM users WHERE role='resident' AND status='active'")->fetch_assoc()['c'];
$totalRequests   = $conn->query("SELECT COUNT(*) c FROM document_requests")->fetch_assoc()['c'];
$pendingReqs     = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE status='Pending'")->fetch_assoc()['c'];
$processingReqs  = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE status='Processing'")->fetch_assoc()['c'];
$approvedReqs    = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE status='Approved'")->fetch_assoc()['c'];
$releasedReqs    = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE status='Released'")->fetch_assoc()['c'];
$rejectedReqs    = $conn->query("SELECT COUNT(*) c FROM document_requests WHERE status='Rejected'")->fetch_assoc()['c'];
$totalAnnounce   = $conn->query("SELECT COUNT(*) c FROM announcements")->fetch_assoc()['c'];

// Recent requests
$recentReqs = $conn->query("SELECT dr.*, u.full_name FROM document_requests dr JOIN users u ON dr.user_id=u.id ORDER BY dr.created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

// Document type breakdown
$typeStats = $conn->query("SELECT document_type, COUNT(*) cnt FROM document_requests GROUP BY document_type")->fetch_all(MYSQLI_ASSOC);

// Monthly requests (last 6 months)
$monthlyData = $conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') mo, COUNT(*) cnt FROM document_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at")->fetch_all(MYSQLI_ASSOC);

include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-desc">Overview of barangay online services — <?= date('l, F j, Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="manage_request.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Manage Requests
        </a>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= number_format($totalResidents) ?></div>
            <div class="stat-card-label">Total Residents</div>
            <div class="stat-card-sub"><?= $activeResidents ?> active</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= number_format($pendingReqs) ?></div>
            <div class="stat-card-label">Pending Requests</div>
            <div class="stat-card-sub"><?= $processingReqs ?> processing</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= number_format($approvedReqs + $releasedReqs) ?></div>
            <div class="stat-card-label">Approved / Released</div>
            <div class="stat-card-sub"><?= $releasedReqs ?> released</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-number"><?= number_format($rejectedReqs) ?></div>
            <div class="stat-card-label">Rejected</div>
            <div class="stat-card-sub"><?= $totalRequests ?> total requests</div>
        </div>
    </div>
</div>

<!-- STATUS BREAKDOWN -->
<div class="dashboard-row">
    <div class="card dashboard-card-wide">
        <div class="card-header">
            <h3>Request Status Breakdown</h3>
        </div>
        <div class="card-body">
            <?php
            $statusMap = [
                'Pending'    => [$pendingReqs,    'progress-warning'],
                'Processing' => [$processingReqs, 'progress-info'],
                'Approved'   => [$approvedReqs,   'progress-success'],
                'Released'   => [$releasedReqs,   'progress-primary'],
                'Rejected'   => [$rejectedReqs,   'progress-danger'],
            ];
            foreach ($statusMap as $label => [$count, $cls]):
                $pct = $totalRequests > 0 ? round(($count / $totalRequests) * 100) : 0;
            ?>
            <div class="progress-row">
                <div class="progress-label-row">
                    <span><?= $label ?></span>
                    <span><?= $count ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Document Type Requests</h3></div>
        <div class="card-body">
            <?php if (empty($typeStats)): ?>
            <div class="empty-state-sm">No requests yet.</div>
            <?php else: foreach ($typeStats as $ts):
                $pct = $totalRequests > 0 ? round(($ts['cnt'] / $totalRequests) * 100) : 0;
            ?>
            <div class="progress-row">
                <div class="progress-label-row">
                    <span><?= documentTypeLabel($ts['document_type']) ?></span>
                    <span><?= $ts['cnt'] ?></span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill progress-primary" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="quick-actions-row">
    <h3 class="section-title-sm">Quick Actions</h3>
    <div class="quick-actions-grid">
        <a href="manage_request.php?status=Pending" class="quick-action-card">
            <div class="qa-icon qa-orange">📋</div>
            <div class="qa-label">Review Pending</div>
            <div class="qa-count"><?= $pendingReqs ?></div>
        </a>
        <a href="manage_resident.php" class="quick-action-card">
            <div class="qa-icon qa-blue">👥</div>
            <div class="qa-label">Manage Residents</div>
            <div class="qa-count"><?= $totalResidents ?></div>
        </a>
        <a href="announcement.php" class="quick-action-card">
            <div class="qa-icon qa-green">📢</div>
            <div class="qa-label">Announcements</div>
            <div class="qa-count"><?= $totalAnnounce ?></div>
        </a>
        <a href="notification.php" class="quick-action-card">
            <div class="qa-icon qa-purple">🔔</div>
            <div class="qa-label">Notifications</div>
            <div class="qa-count"><?= getUnreadCount((int)$_SESSION['user_id']) ?></div>
        </a>
    </div>
</div>

<!-- RECENT REQUESTS TABLE -->
<div class="card" style="margin-top:1.5rem">
    <div class="card-header">
        <h3>Recent Document Requests</h3>
        <a href="manage_request.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Resident</th>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentReqs)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No requests found.</td></tr>
                    <?php else: foreach ($recentReqs as $r): ?>
                    <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><?= e($r['full_name']) ?></td>
                        <td><?= documentTypeLabel($r['document_type']) ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                        <td><?= formatDateTime($r['created_at']) ?></td>
                        <td>
                            <a href="manage_request.php?view=<?= $r['id'] ?>" class="btn btn-primary btn-xs">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>