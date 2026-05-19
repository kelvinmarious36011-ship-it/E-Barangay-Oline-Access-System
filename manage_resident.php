<?php
require_once 'config.php';
requireAdmin();
$pageTitle = 'Manage Residents';
$basePath  = '';

$success = getFlash('success');
$error   = getFlash('error');

// Toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $rid  = (int)$_GET['toggle'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id=? AND role='resident'");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
        $conn->prepare("UPDATE users SET status=? WHERE id=?")->execute() || true;
        $st = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $st->bind_param('si', $newStatus, $rid);
        $st->execute();
        setFlash('success', "Resident account $newStatus.");
    }
    redirect('manage_resident.php');
}

// Edit save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resident'])) {
    $rid  = (int)$_POST['resident_id'];
    $data = [
        'full_name'     => trim($_POST['full_name'] ?? ''),
        'phone'         => trim($_POST['phone'] ?? ''),
        'address'       => trim($_POST['address'] ?? ''),
        'civil_status'  => $_POST['civil_status'] ?? 'Single',
        'gender'        => $_POST['gender'] ?? 'Male',
        'cedula_number' => trim($_POST['cedula_number'] ?? ''),
    ];
    $st = $conn->prepare("UPDATE users SET full_name=?,phone=?,address=?,civil_status=?,gender=?,cedula_number=? WHERE id=? AND role='resident'");
    $st->bind_param('ssssssi', $data['full_name'], $data['phone'], $data['address'], $data['civil_status'], $data['gender'], $data['cedula_number'], $rid);
    if ($st->execute()) {
        setFlash('success', 'Resident profile updated.');
    } else {
        setFlash('error', 'Update failed.');
    }
    redirect('manage_resident.php');
}

// Search/filter
$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$where  = ["role='resident'"];
$params = []; $types = '';
if ($search) { $where[] = '(full_name LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
if ($filterStatus) { $where[] = 'status=?'; $params[] = $filterStatus; $types .= 's'; }
$whereStr = implode(' AND ', $where);
$stmt = $conn->prepare("SELECT * FROM users WHERE $whereStr ORDER BY created_at DESC");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$residents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// View single for edit
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $es = $conn->prepare("SELECT * FROM users WHERE id=? AND role='resident'");
    $es->bind_param('i', (int)$_GET['edit']);
    $es->execute();
    $editUser = $es->get_result()->fetch_assoc();
}

include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Residents</h1>
        <p class="page-desc">View, edit, and manage resident accounts.</p>
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
        <input type="text" name="q" class="form-input filter-search" placeholder="Search name or email…" value="<?= e($search) ?>">
        <select name="status" class="form-input form-select filter-select">
            <option value="">All Statuses</option>
            <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="manage_resident.php" class="btn btn-ghost">Reset</a>
    </form>
</div>

<div class="split-layout <?= $editUser ? 'has-detail' : '' ?>">
    <div class="split-list">
        <div class="card">
            <div class="card-header">
                <h3>Residents <span class="badge badge-info"><?= count($residents) ?></span></h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($residents)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No residents found.</td></tr>
                            <?php else: foreach ($residents as $r): ?>
                            <tr class="<?= ($editUser && $editUser['id'] == $r['id']) ? 'row-selected' : '' ?>">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-sm"><?= strtoupper(substr($r['full_name'],0,1)) ?></div>
                                        <div>
                                            <div><?= e($r['full_name']) ?></div>
                                            <small class="text-muted"><?= e($r['gender']) ?> &bull; <?= e($r['civil_status']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($r['email']) ?></td>
                                <td><?= e($r['phone'] ?: '—') ?></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td><?= formatDate($r['created_at']) ?></td>
                                <td class="action-td">
                                    <a href="manage_resident.php?edit=<?= $r['id'] ?>" class="btn btn-primary btn-xs">Edit</a>
                                    <a href="manage_resident.php?toggle=<?= $r['id'] ?>" class="btn <?= $r['status']==='active' ? 'btn-warning' : 'btn-success' ?> btn-xs confirm-link"
                                       data-confirm="<?= $r['status']==='active' ? 'Deactivate this resident account?' : 'Activate this resident account?' ?>">
                                       <?= $r['status']==='active' ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT PANEL -->
    <?php if ($editUser): ?>
    <div class="split-detail">
        <div class="card detail-card">
            <div class="card-header">
                <h3>Edit Resident</h3>
                <a href="manage_resident.php" class="btn btn-ghost btn-xs">✕ Close</a>
            </div>
            <div class="card-body">
                <form method="POST" action="manage_resident.php">
                    <input type="hidden" name="resident_id" value="<?= $editUser['id'] ?>">
                    <input type="hidden" name="edit_resident" value="1">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" value="<?= e($editUser['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email (read-only)</label>
                        <input type="email" class="form-input" value="<?= e($editUser['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="<?= e($editUser['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-input form-select">
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $editUser['gender']===$g?'selected':'' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Status</label>
                        <select name="civil_status" class="form-input form-select">
                            <?php foreach (['Single','Married','Widowed','Separated','Annulled'] as $cs): ?>
                            <option value="<?= $cs ?>" <?= $editUser['civil_status']===$cs?'selected':'' ?>><?= $cs ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cedula Number</label>
                        <input type="text" name="cedula_number" class="form-input" value="<?= e($editUser['cedula_number']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-input form-textarea" rows="3"><?= e($editUser['address']) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>

                <!-- Request History for this resident -->
                <?php
                $rqs = $conn->prepare("SELECT * FROM document_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
                $rqs->bind_param('i', $editUser['id']);
                $rqs->execute();
                $rqList = $rqs->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                <div class="detail-section" style="margin-top:1.5rem">
                    <div class="detail-section-title">Recent Requests</div>
                    <?php if (empty($rqList)): ?>
                    <p class="text-muted">No requests yet.</p>
                    <?php else: foreach ($rqList as $rq): ?>
                    <div class="mini-req-row">
                        <span>#<?= $rq['id'] ?> <?= documentTypeLabel($rq['document_type']) ?></span>
                        <?= statusBadge($rq['status']) ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>