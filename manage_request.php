<?php
require_once 'config.php';
requireAdmin();
$pageTitle = 'Manage Requests';
$basePath  = '';

$success = getFlash('success');
$error   = getFlash('error');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid     = (int)$_POST['request_id'];
    $status  = $_POST['status'] ?? '';
    $remarks = trim($_POST['admin_remarks'] ?? '');
    $allowed = ['Pending','Processing','Approved','Released','Rejected'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE document_requests SET status=?, admin_remarks=? WHERE id=?");
        $stmt->bind_param('ssi', $status, $remarks, $rid);
        $stmt->execute();
        // Notify resident
        $req = $conn->query("SELECT * FROM document_requests WHERE id=$rid")->fetch_assoc();
        if ($req) {
            $docLabel = documentTypeLabel($req['document_type']);
            createNotification(
                $req['user_id'],
                'Request Status Updated',
                "Your $docLabel request (#$rid) has been updated to: $status." . ($remarks ? " Remarks: $remarks" : ''),
                'status_update',
                $rid
            );
        }
        setFlash('success', "Request #$rid status updated to $status.");
    }
    redirect('manage_request.php');
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$search       = trim($_GET['q'] ?? '');
$viewId       = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Build query
$where  = ['1=1'];
$params = [];
$types  = '';
if ($filterStatus) { $where[] = 'dr.status = ?'; $params[] = $filterStatus; $types .= 's'; }
if ($filterType)   { $where[] = 'dr.document_type = ?'; $params[] = $filterType; $types .= 's'; }
if ($search)       { $where[] = '(u.full_name LIKE ? OR dr.purpose LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
$whereStr = implode(' AND ', $where);

$stmt = $conn->prepare("SELECT dr.*, u.full_name, u.email, u.phone FROM document_requests dr JOIN users u ON dr.user_id=u.id WHERE $whereStr ORDER BY dr.created_at DESC");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Single view
$viewReq = null;
if ($viewId) {
    $vs = $conn->prepare("SELECT dr.*, u.full_name, u.email, u.phone FROM document_requests dr JOIN users u ON dr.user_id=u.id WHERE dr.id=?");
    $vs->bind_param('i', $viewId);
    $vs->execute();
    $viewReq = $vs->get_result()->fetch_assoc();
}

include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Document Requests</h1>
        <p class="page-desc">Review, process, and update all resident document requests.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible"><?= e($success) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error alert-dismissible"><?= e($error) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>

<!-- FILTERS -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <input type="text" name="q" class="form-input filter-search" placeholder="Search resident or purpose…" value="<?= e($search) ?>">
        <select name="status" class="form-input form-select filter-select">
            <option value="">All Statuses</option>
            <?php foreach (['Pending','Processing','Approved','Released','Rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-input form-select filter-select">
            <option value="">All Types</option>
            <option value="barangay_clearance" <?= $filterType === 'barangay_clearance' ? 'selected' : '' ?>>Barangay Clearance</option>
            <option value="certificate_of_indigency" <?= $filterType === 'certificate_of_indigency' ? 'selected' : '' ?>>Certificate of Indigency</option>
            <option value="business_clearance" <?= $filterType === 'business_clearance' ? 'selected' : '' ?>>Business Clearance</option>
            <option value="barangay_blotter" <?= $filterType === 'barangay_blotter' ? 'selected' : '' ?>>Barangay Blotter</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="manage_request.php" class="btn btn-ghost">Reset</a>
    </form>
</div>

<div class="split-layout <?= $viewReq ? 'has-detail' : '' ?>">
    <!-- TABLE -->
    <div class="split-list">
        <div class="card">
            <div class="card-header">
                <h3>All Requests <span class="badge badge-info"><?= count($requests) ?></span></h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Resident</th>
                                <th>Document</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No requests found.</td></tr>
                            <?php else: foreach ($requests as $r): ?>
                            <tr class="<?= $viewId == $r['id'] ? 'row-selected' : '' ?>">
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td>
                                    <div><?= e($r['full_name']) ?></div>
                                    <small class="text-muted"><?= e($r['email']) ?></small>
                                </td>
                                <td><?= documentTypeLabel($r['document_type']) ?></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td><?= formatDate($r['created_at']) ?></td>
                                <td>
                                    <a href="manage_request.php?view=<?= $r['id'] ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterType ? '&type='.$filterType : '' ?>" class="btn btn-primary btn-xs">Review</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL PANEL -->
    <?php if ($viewReq): ?>
    <div class="split-detail">
        <div class="card detail-card">
            <div class="card-header">
                <h3>Request #<?= $viewReq['id'] ?> Details</h3>
                <a href="manage_request.php" class="btn btn-ghost btn-xs">✕ Close</a>
            </div>
            <div class="card-body">
                <div class="detail-meta-row">
                    <span><?= statusBadge($viewReq['status']) ?></span>
                    <span class="text-muted"><?= formatDateTime($viewReq['created_at']) ?></span>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Resident Info</div>
                    <div class="detail-grid">
                        <div class="detail-row"><span>Name</span><strong><?= e($viewReq['full_name']) ?></strong></div>
                        <div class="detail-row"><span>Email</span><strong><?= e($viewReq['email']) ?></strong></div>
                        <div class="detail-row"><span>Phone</span><strong><?= e($viewReq['phone'] ?: '—') ?></strong></div>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">Document: <?= documentTypeLabel($viewReq['document_type']) ?></div>
                    <div class="detail-grid">
                        <?php if (!empty($viewReq['full_name'])): ?>
                        <div class="detail-row"><span>Full Name</span><strong><?= e($viewReq['full_name']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['complete_address'])): ?>
                        <div class="detail-row"><span>Address</span><strong><?= e($viewReq['complete_address']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['date_of_birth'])): ?>
                        <div class="detail-row"><span>Date of Birth</span><strong><?= formatDate($viewReq['date_of_birth']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['place_of_birth'])): ?>
                        <div class="detail-row"><span>Place of Birth</span><strong><?= e($viewReq['place_of_birth']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['civil_status'])): ?>
                        <div class="detail-row"><span>Civil Status</span><strong><?= e($viewReq['civil_status']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['citizenship'])): ?>
                        <div class="detail-row"><span>Citizenship</span><strong><?= e($viewReq['citizenship']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['period_of_residency'])): ?>
                        <div class="detail-row"><span>Residency Period</span><strong><?= e($viewReq['period_of_residency']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['cedula_number'])): ?>
                        <div class="detail-row"><span>Cedula #</span><strong><?= e($viewReq['cedula_number']) ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($viewReq['purpose'])): ?>
                        <div class="detail-row"><span>Purpose</span><strong><?= e($viewReq['purpose']) ?></strong></div>
                        <?php endif; ?>
                        <!-- Indigency -->
                        <?php if (!empty($viewReq['monthly_income'])): ?>
                        <div class="detail-row"><span>Monthly Income</span><strong>₱<?= number_format($viewReq['monthly_income'],2) ?></strong></div>
                        <div class="detail-row"><span>Annual Income</span><strong>₱<?= number_format($viewReq['annual_income'],2) ?></strong></div>
                        <div class="detail-row"><span>Institution</span><strong><?= e($viewReq['target_institution']) ?></strong></div>
                        <div class="detail-row"><span>Benefit</span><strong><?= e($viewReq['specific_benefit']) ?></strong></div>
                        <?php endif; ?>
                        <!-- Business -->
                        <?php if (!empty($viewReq['business_name'])): ?>
                        <div class="detail-row"><span>Business Name</span><strong><?= e($viewReq['business_name']) ?></strong></div>
                        <div class="detail-row"><span>Business Address</span><strong><?= e($viewReq['business_address']) ?></strong></div>
                        <div class="detail-row"><span>Ownership</span><strong><?= e($viewReq['type_of_ownership']) ?></strong></div>
                        <div class="detail-row"><span>Nature</span><strong><?= e($viewReq['nature_of_business']) ?></strong></div>
                        <div class="detail-row"><span>Capital</span><strong>₱<?= number_format($viewReq['capital_investment'],2) ?></strong></div>
                        <?php endif; ?>
                        <!-- Blotter -->
                        <?php if (!empty($viewReq['complainant_name'])): ?>
                        <div class="detail-row"><span>Complainant</span><strong><?= e($viewReq['complainant_name']) ?></strong></div>
                        <div class="detail-row"><span>Respondent</span><strong><?= e($viewReq['respondent_name']) ?></strong></div>
                        <div class="detail-row"><span>Case Type</span><strong><?= e($viewReq['case_type']) ?></strong></div>
                        <div class="detail-row"><span>Date/Time</span><strong><?= formatDateTime($viewReq['date_of_occurrence']) ?></strong></div>
                        <div class="detail-row"><span>Place</span><strong><?= e($viewReq['place_of_incident']) ?></strong></div>
                        <?php if (!empty($viewReq['narrative_of_events'])): ?>
                        <div class="detail-row detail-row-full"><span>Narrative</span><strong><?= nl2br(e($viewReq['narrative_of_events'])) ?></strong></div>
                        <?php endif; ?>
                        <div class="detail-row"><span>Witnesses</span><strong><?= e($viewReq['witnesses'] ?: '—') ?></strong></div>
                        <div class="detail-row"><span>Evidence</span><strong><?= e($viewReq['evidence_description'] ?: '—') ?></strong></div>
                        <div class="detail-row"><span>Desired Action</span><strong><?= e($viewReq['desired_action'] ?: '—') ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($viewReq['admin_remarks'])): ?>
                <div class="detail-section">
                    <div class="detail-section-title">Admin Remarks</div>
                    <div class="alert alert-info"><?= e($viewReq['admin_remarks']) ?></div>
                </div>
                <?php endif; ?>

                <!-- UPDATE STATUS FORM -->
                <div class="detail-section">
                    <div class="detail-section-title">Update Request Status</div>
                    <form method="POST" action="manage_request.php">
                        <input type="hidden" name="request_id" value="<?= $viewReq['id'] ?>">
                        <div class="form-group">
                            <label class="form-label">New Status</label>
                            <select name="status" class="form-input form-select">
                                <?php foreach (['Pending','Processing','Approved','Released','Rejected'] as $s): ?>
                                <option value="<?= $s ?>" <?= $viewReq['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Remarks (optional)</label>
                            <textarea name="admin_remarks" class="form-input form-textarea" rows="3" placeholder="Add a note for the resident…"><?= e($viewReq['admin_remarks']) ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>