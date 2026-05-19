<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Notifications';
$basePath  = '';
$userId    = (int)$_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all'])) {
    $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute() || true;
    $ms = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $ms->bind_param('i', $userId);
    $ms->execute();
    redirect('notification.php');
}

// Mark single read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $ms = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $ms->bind_param('ii', $nid, $userId);
    $ms->execute();
    redirect('notification.php');
}

// Delete single
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $nid = (int)$_GET['delete'];
    $ds = $conn->prepare("DELETE FROM notifications WHERE id=? AND user_id=?");
    $ds->bind_param('ii', $nid, $userId);
    $ds->execute();
    redirect('notification.php');
}

// Delete all
if (isset($_GET['delete_all'])) {
    $da = $conn->prepare("DELETE FROM notifications WHERE user_id=?");
    $da->bind_param('i', $userId);
    $da->execute();
    redirect('notification.php');
}

// Filter
$filterType = $_GET['type'] ?? '';
$filterRead = $_GET['read_status'] ?? '';

$where = ['user_id = ?'];
$params = [$userId]; $types = 'i';
if ($filterType) { $where[] = 'type = ?'; $params[] = $filterType; $types .= 's'; }
if ($filterRead === 'unread') { $where[] = 'is_read = 0'; }
if ($filterRead === 'read')   { $where[] = 'is_read = 1'; }
$whereStr = implode(' AND ', $where);
$stmt = $conn->prepare("SELECT * FROM notifications WHERE $whereStr ORDER BY created_at DESC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark all as read when page is loaded
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$userId");

$unreadCount = 0; // already marked above

include_once 'includes/layout.php';

$typeIconMap = [
    'request'       => '📋',
    'announcement'  => '📢',
    'system'        => '⚙️',
    'status_update' => '🔄',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-desc">View all your system notifications and updates.</p>
    </div>
    <div class="page-actions">
        <?php if (!empty($notifications)): ?>
        <a href="notification.php?delete_all=1" class="btn btn-danger confirm-link" data-confirm="Delete all notifications?">Clear All</a>
        <?php endif; ?>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <select name="type" class="form-input form-select filter-select">
            <option value="">All Types</option>
            <option value="request"       <?= $filterType==='request'?'selected':'' ?>>Document Requests</option>
            <option value="status_update" <?= $filterType==='status_update'?'selected':'' ?>>Status Updates</option>
            <option value="announcement"  <?= $filterType==='announcement'?'selected':'' ?>>Announcements</option>
            <option value="system"        <?= $filterType==='system'?'selected':'' ?>>System</option>
        </select>
        <select name="read_status" class="form-input form-select filter-select">
            <option value="">All</option>
            <option value="unread" <?= $filterRead==='unread'?'selected':'' ?>>Unread</option>
            <option value="read"   <?= $filterRead==='read'?'selected':'' ?>>Read</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="notification.php" class="btn btn-ghost">Reset</a>
    </form>
</div>

<!-- NOTIFICATIONS LIST -->
<?php if (empty($notifications)): ?>
<div class="empty-state">
    <div class="empty-icon">🔔</div>
    <h3>No Notifications</h3>
    <p>You're all caught up! New notifications will appear here.</p>
</div>
<?php else: ?>
<div class="notif-full-list">
    <?php foreach ($notifications as $n):
        $icon = $typeIconMap[$n['type']] ?? '🔔';
        $isUnread = !$n['is_read'];
    ?>
    <div class="notif-full-item <?= $isUnread ? 'notif-unread' : '' ?>">
        <div class="notif-full-icon"><?= $icon ?></div>
        <div class="notif-full-body">
            <div class="notif-full-header">
                <strong class="notif-full-title"><?= e($n['title']) ?></strong>
                <span class="notif-full-time"><?= timeAgo($n['created_at']) ?></span>
            </div>
            <p class="notif-full-msg"><?= e($n['message']) ?></p>
            <div class="notif-full-meta">
                <span class="notif-type-tag"><?= ucwords(str_replace('_', ' ', $n['type'])) ?></span>
                <span class="text-muted"><?= formatDateTime($n['created_at']) ?></span>
            </div>
        </div>
        <div class="notif-full-actions">
            <?php if ($n['type'] === 'request' || $n['type'] === 'status_update'): ?>
            <a href="<?= $_SESSION['role']==='admin' ? 'manage_request.php?view='.$n['reference_id'] : 'view_request.php?view='.$n['reference_id'] ?>" class="btn btn-ghost btn-xs">View</a>
            <?php elseif ($n['type'] === 'announcement' && $n['reference_id']): ?>
            <a href="announcement.php" class="btn btn-ghost btn-xs">View</a>
            <?php endif; ?>
            <a href="notification.php?delete=<?= $n['id'] ?>" class="btn btn-ghost btn-xs text-danger confirm-link" data-confirm="Delete this notification?">×</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>