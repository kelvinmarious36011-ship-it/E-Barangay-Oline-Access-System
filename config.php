<?php
// ============================================================
// config.php — Database connection & helper functions
// ============================================================

define('DB_HOST', 'sql206.infinityfree.com');
define('DB_USER', 'if0_41960192');
define('DB_PASS', 'Tue5tFbp5rn2IF');
define('DB_NAME', 'if0_41960192_ebarangay');

define('SITE_NAME', 'E-Barangay');
define('SITE_FULL', 'E-Barangay Online Access System');
define('BARANGAY_NAME', 'Barangay Sample');
define('BARANGAY_CITY', 'Sample City');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Database connection (mysqli)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c00;background:#fff1f1;border:1px solid #c00;border-radius:8px;max-width:500px;margin:50px auto;">
        <h2>Database Connection Error</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Please check your database settings in <strong>config.php</strong>.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

// ============================================================
// AUTHENTICATION HELPERS
// ============================================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: residents_dashboard.php');
        exit;
    }
}

function requireResident(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'resident') {
        header('Location: admin_dashboard.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    global $conn;
    if (!isLoggedIn()) return null;
    $id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc();
}

// ============================================================
// NOTIFICATION HELPERS
// ============================================================

function createNotification(int $userId, string $title, string $message, string $type = 'system', ?int $refId = null): void {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isssi', $userId, $title, $message, $type, $refId);
    $stmt->execute();
}

function notifyAllAdmins(string $title, string $message, string $type = 'system', ?int $refId = null): void {
    global $conn;
    $res = $conn->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
    while ($row = $res->fetch_assoc()) {
        createNotification($row['id'], $title, $message, $type, $refId);
    }
}

function notifyAllResidents(string $title, string $message, string $type = 'announcement', ?int $refId = null): void {
    global $conn;
    $res = $conn->query("SELECT id FROM users WHERE role = 'resident' AND status = 'active'");
    while ($row = $res->fetch_assoc()) {
        createNotification($row['id'], $title, $message, $type, $refId);
    }
}

function getUnreadCount(int $userId): int {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}

function getRecentNotifications(int $userId, int $limit = 5): array {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ============================================================
// FLASH MESSAGE HELPERS
// ============================================================

function setFlash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// ============================================================
// UTILITY HELPERS
// ============================================================

function e(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function old(string $key, mixed $default = ''): string {
    return e($_SESSION['old'][$key] ?? $default);
}

function clearOld(): void {
    unset($_SESSION['old']);
}

function saveOld(array $data): void {
    $_SESSION['old'] = $data;
}

function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    return date($format, strtotime($date));
}

function formatDateTime(?string $dt): string {
    if (empty($dt) || $dt === '0000-00-00 00:00:00') return '—';
    return date('M d, Y h:i A', strtotime($dt));
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->i < 1 && $diff->h == 0 && $diff->d == 0) return 'Just now';
    if ($diff->h == 0 && $diff->d == 0) return $diff->i . ' min ago';
    if ($diff->d == 0) return $diff->h . ' hr ago';
    if ($diff->d == 1) return 'Yesterday';
    if ($diff->d < 7) return $diff->d . ' days ago';
    return formatDate($datetime);
}

function documentTypeLabel(string $type): string {
    return match($type) {
        'barangay_clearance'      => 'Barangay Clearance',
        'certificate_of_indigency' => 'Certificate of Indigency',
        'business_clearance'      => 'Business Clearance',
        'barangay_blotter'        => 'Barangay Blotter',
        default                   => ucwords(str_replace('_', ' ', $type)),
    };
}

function statusBadge(string $status): string {
    $map = [
        'Pending'    => 'badge-warning',
        'Processing' => 'badge-info',
        'Approved'   => 'badge-success',
        'Released'   => 'badge-primary',
        'Rejected'   => 'badge-danger',
        'active'     => 'badge-success',
        'inactive'   => 'badge-danger',
    ];
    $cls = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function verifyToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function ensureToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}