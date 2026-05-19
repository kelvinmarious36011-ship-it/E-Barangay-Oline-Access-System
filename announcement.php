<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Announcements';
$basePath  = '';
$isAdmin   = $_SESSION['role'] === 'admin';
$userId    = (int)$_SESSION['user_id'];

$success = getFlash('success');
$error   = getFlash('error');

// ADMIN ACTIONS
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'post' || $action === 'edit') {
        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $pinned   = isset($_POST['is_pinned']) ? 1 : 0;

        if (empty($title) || empty($content)) {
            setFlash('error', 'Title and content are required.');
        } else {
            if ($action === 'post') {
                $st = $conn->prepare("INSERT INTO announcements (admin_id,title,content,category,is_pinned) VALUES (?,?,?,?,?)");
                $st->bind_param('isssi', $userId, $title, $content, $category, $pinned);
                $st->execute();
                $newId = $conn->insert_id;
                // Notify all residents
                notifyAllResidents("New Announcement: $title", mb_strimwidth($content, 0, 100, '…'), 'announcement', $newId);
                setFlash('success', 'Announcement posted successfully.');
            } else {
                $annId = (int)$_POST['ann_id'];
                $st = $conn->prepare("UPDATE announcements SET title=?,content=?,category=?,is_pinned=? WHERE id=?");
                $st->bind_param('sssii', $title, $content, $category, $pinned, $annId);
                $st->execute();
                setFlash('success', 'Announcement updated.');
            }
        }
        redirect('announcement.php');
    }

    if ($action === 'delete') {
        $annId = (int)$_POST['ann_id'];
        $conn->prepare("DELETE FROM announcements WHERE id=?")->execute() || true;
        $st = $conn->prepare("DELETE FROM announcements WHERE id=?");
        $st->bind_param('i', $annId);
        $st->execute();
        setFlash('success', 'Announcement deleted.');
        redirect('announcement.php');
    }

    if ($action === 'toggle_pin') {
        $annId = (int)$_POST['ann_id'];
        $conn->query("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id=$annId");
        setFlash('success', 'Pin status updated.');
        redirect('announcement.php');
    }
}

// Fetch for edit
$editAnn = null;
if ($isAdmin && isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $es = $conn->prepare("SELECT * FROM announcements WHERE id=?");
    $es->bind_param('i', (int)$_GET['edit']);
    $es->execute();
    $editAnn = $es->get_result()->fetch_assoc();
}

// Fetch all announcements
$announcements = $conn->query("SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.admin_id=u.id ORDER BY a.is_pinned DESC, a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Announcements</h1>
        <p class="page-desc"><?= $isAdmin ? 'Post, edit, and manage barangay announcements.' : 'Stay informed with the latest news and updates from your barangay.' ?></p>
    </div>
    <?php if ($isAdmin): ?>
    <div class="page-actions">
        <button class="btn btn-primary" id="newAnnBtn">+ New Announcement</button>
    </div>
    <?php endif; ?>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible"><?= e($success) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error alert-dismissible"><?= e($error) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>

<!-- ADMIN: POST / EDIT FORM -->
<?php if ($isAdmin): ?>
<div class="card announcement-form-card" id="annFormCard" style="<?= ($editAnn || isset($_GET['new'])) ? '' : 'display:none' ?>">
    <div class="card-header">
        <h3><?= $editAnn ? 'Edit Announcement' : 'New Announcement' ?></h3>
        <button class="btn btn-ghost btn-xs" id="closeAnnForm">Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="announcement.php">
            <input type="hidden" name="action" value="<?= $editAnn ? 'edit' : 'post' ?>">
            <?php if ($editAnn): ?>
            <input type="hidden" name="ann_id" value="<?= $editAnn['id'] ?>">
            <?php endif; ?>
            <div class="form-grid-2">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Title <span class="req">*</span></label>
                    <input type="text" name="title" class="form-input" value="<?= e($editAnn['title'] ?? '') ?>" required placeholder="Announcement title…">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-input form-select">
                        <?php foreach (['General','Health','Social Services','Infrastructure','Events','Emergency','Others'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($editAnn['category'] ?? 'General') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:.5rem;padding-top:1.8rem">
                    <input type="checkbox" id="is_pinned" name="is_pinned" value="1" <?= !empty($editAnn['is_pinned']) ? 'checked' : '' ?>>
                    <label for="is_pinned" class="form-label" style="margin:0;cursor:pointer">📌 Pin this announcement</label>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Content <span class="req">*</span></label>
                    <textarea name="content" class="form-input form-textarea" rows="6" required placeholder="Write your announcement here…"><?= e($editAnn['content'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editAnn ? 'Save Changes' : 'Post Announcement' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ANNOUNCEMENT LIST -->
<?php if (empty($announcements)): ?>
<div class="empty-state">
    <div class="empty-icon">📢</div>
    <h3>No Announcements Yet</h3>
    <p>Check back later for updates from your barangay.</p>
</div>
<?php else: ?>
<div class="announcements-list">
    <?php foreach ($announcements as $ann): ?>
    <div class="ann-full-card <?= $ann['is_pinned'] ? 'ann-pinned' : '' ?>">
        <div class="ann-full-header">
            <div class="ann-full-meta">
                <?php if ($ann['is_pinned']): ?>
                <span class="ann-pin-tag">📌 Pinned</span>
                <?php endif; ?>
                <span class="ann-cat-tag"><?= e($ann['category']) ?></span>
                <span class="ann-date"><?= formatDateTime($ann['created_at']) ?></span>
                <span class="text-muted">by <?= e($ann['full_name']) ?></span>
            </div>
            <?php if ($isAdmin): ?>
            <div class="ann-actions">
                <a href="announcement.php?edit=<?= $ann['id'] ?>" class="btn btn-ghost btn-xs">Edit</a>
                <form method="POST" action="announcement.php" style="display:inline">
                    <input type="hidden" name="action" value="toggle_pin">
                    <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-xs"><?= $ann['is_pinned'] ? 'Unpin' : 'Pin' ?></button>
                </form>
                <form method="POST" action="announcement.php" style="display:inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-xs confirm-btn" data-confirm="Delete this announcement?">Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <h2 class="ann-full-title"><?= e($ann['title']) ?></h2>
        <div class="ann-full-content"><?= nl2br(e($ann['content'])) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function(){
    const btn  = document.getElementById('newAnnBtn');
    const card = document.getElementById('annFormCard');
    const close = document.getElementById('closeAnnForm');
    if (btn)  btn.addEventListener('click',  () => { card.style.display=''; card.scrollIntoView({behavior:'smooth'}); });
    if (close) close.addEventListener('click', () => { card.style.display='none'; });
})();
</script>

<?php include_once 'includes/footer.php'; ?>