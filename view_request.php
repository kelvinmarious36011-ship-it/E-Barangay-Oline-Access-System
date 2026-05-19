<?php
require_once 'config.php';
requireResident();
$pageTitle = 'My Requests';
$basePath  = '';
$userId    = (int)$_SESSION['user_id'];

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type']   ?? '';
$search       = trim($_GET['q'] ?? '');

$where  = ['user_id = ?'];
$params = [$userId];
$types  = 'i';
if ($filterStatus) { $where[] = 'status = ?'; $params[] = $filterStatus; $types .= 's'; }
if ($filterType)   { $where[] = 'document_type = ?'; $params[] = $filterType; $types .= 's'; }
if ($search)       { $where[] = '(purpose LIKE ? OR full_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
$whereStr = implode(' AND ', $where);

$stmt = $conn->prepare("SELECT * FROM document_requests WHERE $whereStr ORDER BY created_at DESC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Single view
$viewReq = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $vs = $conn->prepare("SELECT * FROM document_requests WHERE id=? AND user_id=?");
    $vs->bind_param('ii', (int)$_GET['view'], $userId);
    $vs->execute();
    $viewReq = $vs->get_result()->fetch_assoc();
}

include_once 'includes/layout.php';

// Status flow for tracker
$statusFlow = ['Pending','Processing','Approved','Released'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Requests</h1>
        <p class="page-desc">Track the status of your submitted document requests.</p>
    </div>
    <div class="page-actions">
        <a href="request_documents.php" class="btn btn-primary">+ New Request</a>
    </div>
</div>

<!-- FILTERS -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <select name="status" class="form-input form-select filter-select">
            <option value="">All Statuses</option>
            <?php foreach (['Pending','Processing','Approved','Released','Rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-input form-select filter-select">
            <option value="">All Types</option>
            <option value="barangay_clearance" <?= $filterType==='barangay_clearance'?'selected':'' ?>>Barangay Clearance</option>
            <option value="certificate_of_indigency" <?= $filterType==='certificate_of_indigency'?'selected':'' ?>>Certificate of Indigency</option>
            <option value="business_clearance" <?= $filterType==='business_clearance'?'selected':'' ?>>Business Clearance</option>
            <option value="barangay_blotter" <?= $filterType==='barangay_blotter'?'selected':'' ?>>Barangay Blotter</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="view_request.php" class="btn btn-ghost">Reset</a>
    </form>
</div>

<div class="split-layout <?= $viewReq ? 'has-detail' : '' ?>">
    <div class="split-list">
        <div class="card">
            <div class="card-header">
                <h3>Submitted Requests <span class="badge badge-info"><?= count($requests) ?></span></h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($requests)): ?>
                <div class="empty-state-sm">
                    <p>No requests found.</p>
                    <a href="request_documents.php" class="btn btn-primary btn-sm">Submit Your First Request</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>#</th><th>Document</th><th>Purpose</th><th>Status</th><th>Date</th><th>View</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $r): ?>
                            <tr class="<?= ($viewReq && $viewReq['id']==$r['id']) ? 'row-selected' : '' ?>">
                                <td>#<?= $r['id'] ?></td>
                                <td><?= documentTypeLabel($r['document_type']) ?></td>
                                <td><?= e(mb_strimwidth($r['purpose'] ?? '—', 0, 35, '…')) ?></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td><?= formatDate($r['created_at']) ?></td>
                                <td><a href="view_request.php?view=<?= $r['id'] ?>" class="btn btn-ghost btn-xs">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DETAIL -->
    <?php if ($viewReq): ?>
    <div class="split-detail">
        <div class="card detail-card">
            <div class="card-header">
                <h3>Request #<?= $viewReq['id'] ?></h3>
                <a href="view_request.php" class="btn btn-ghost btn-xs">✕</a>
            </div>
            <div class="card-body">
                <!-- STATUS TRACKER -->
                <div class="status-tracker">
                    <?php if ($viewReq['status'] !== 'Rejected'):
                        foreach ($statusFlow as $i => $s):
                            $done    = array_search($viewReq['status'], $statusFlow) >= $i;
                            $current = $viewReq['status'] === $s;
                    ?>
                    <div class="tracker-step <?= $done ? 'done' : '' ?> <?= $current ? 'current' : '' ?>">
                        <div class="tracker-dot"><?= $done ? '✓' : ($i + 1) ?></div>
                        <div class="tracker-label"><?= $s ?></div>
                    </div>
                    <?php if ($i < count($statusFlow)-1): ?>
                    <div class="tracker-line <?= $done ? 'done' : '' ?>"></div>
                    <?php endif; endforeach;
                    else: ?>
                    <div class="rejected-banner">❌ This request has been Rejected.</div>
                    <?php endif; ?>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Request Summary</div>
                    <div class="detail-grid">
                        <div class="detail-row"><span>Document</span><strong><?= documentTypeLabel($viewReq['document_type']) ?></strong></div>
                        <div class="detail-row"><span>Purpose</span><strong><?= e($viewReq['purpose'] ?: '—') ?></strong></div>
                        <div class="detail-row"><span>Status</span><?= statusBadge($viewReq['status']) ?></div>
                        <div class="detail-row"><span>Submitted</span><strong><?= formatDateTime($viewReq['created_at']) ?></strong></div>
                        <div class="detail-row"><span>Last Updated</span><strong><?= formatDateTime($viewReq['updated_at']) ?></strong></div>
                    </div>
                </div>

                <?php if (!empty($viewReq['admin_remarks'])): ?>
                <div class="detail-section">
                    <div class="detail-section-title">Admin Remarks</div>
                    <div class="alert alert-info"><?= e($viewReq['admin_remarks']) ?></div>
                </div>
                <?php endif; ?>

                <div class="detail-section">
                    <div class="detail-section-title">Personal Details Submitted</div>
                    <div class="detail-grid">
                        <div class="detail-row"><span>Name</span><strong><?= e($viewReq['full_name']) ?></strong></div>
                        <div class="detail-row"><span>Address</span><strong><?= e($viewReq['complete_address'] ?: '—') ?></strong></div>
                        <div class="detail-row"><span>Date of Birth</span><strong><?= formatDate($viewReq['date_of_birth']) ?></strong></div>
                        <div class="detail-row"><span>Civil Status</span><strong><?= e($viewReq['civil_status'] ?: '—') ?></strong></div>
                        <div class="detail-row"><span>Cedula #</span><strong><?= e($viewReq['cedula_number'] ?: '—') ?></strong></div>
                        <?php if (!empty($viewReq['period_of_residency'])): ?>
                        <div class="detail-row"><span>Residency Period</span><strong><?= e($viewReq['period_of_residency']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($viewReq['status'] === 'Approved' || $viewReq['status'] === 'Released'): ?>
                <div class="alert alert-success">
                    ✅ Your document is <strong><?= $viewReq['status'] ?></strong>. Please visit the barangay hall to claim your document. Bring a valid ID.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>