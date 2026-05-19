<?php
// includes/layout.php — Shared sidebar + header
// Usage: include_once '../includes/layout.php'; OR include_once 'includes/layout.php';
// Requires: $pageTitle, $conn, session started, user logged in
require_once (strpos(__DIR__, 'includes') !== false ? dirname(__DIR__) . '/config.php' : 'config.php');

$currentUser    = getCurrentUser();
$userId         = (int)($_SESSION['user_id'] ?? 0);
$userRole       = $_SESSION['role'] ?? 'resident';
$unreadCount    = $userId ? getUnreadCount($userId) : 0;
$recentNotifs   = $userId ? getRecentNotifications($userId, 5) : [];
$currentPage    = basename($_SERVER['PHP_SELF']);
$pageTitle      = $pageTitle ?? 'Dashboard';

// Build base path for assets
$basePath = '';
if (strpos(__DIR__, 'includes') !== false) {
    $basePath = '../';
}

// Mark notifications as read when dropdown opened via JS, no server-side here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= SITE_FULL ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>css/style.css">
</head>
<body>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <path d="M14 2L3 8v12l11 6 11-6V8L14 2z" fill="var(--accent)" opacity="0.9"/>
                <path d="M14 2v24M3 8l11 6 11-6" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= SITE_NAME ?></span>
            <span class="brand-sub"><?= BARANGAY_NAME ?></span>
        </div>
    </div>

    <div class="sidebar-user-card">
        <div class="sidebar-avatar">
            <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= e($currentUser['full_name'] ?? 'User') ?></span>
            <span class="sidebar-user-role"><?= ucfirst($userRole) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($userRole === 'admin'): ?>
        <div class="nav-section-label">Management</div>
        <a href="<?= $basePath ?>admin_dashboard.php" class="nav-item <?= $currentPage === 'admin_dashboard.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="<?= $basePath ?>manage_request.php" class="nav-item <?= $currentPage === 'manage_request.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span>Document Requests</span>
        </a>
        <a href="<?= $basePath ?>manage_resident.php" class="nav-item <?= $currentPage === 'manage_resident.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <span>Residents</span>
        </a>
        <a href="<?= $basePath ?>announcement.php" class="nav-item <?= $currentPage === 'announcement.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a3 3 0 000 6h20a3 3 0 000-6z"/><path d="M14 6H4a2 2 0 00-2 2v4a2 2 0 002 2h16"/><line x1="12" y1="3" x2="12" y2="6"/></svg>
            <span>Announcements</span>
        </a>
        <?php else: ?>
        <div class="nav-section-label">My Portal</div>
        <a href="<?= $basePath ?>residents_dashboard.php" class="nav-item <?= $currentPage === 'residents_dashboard.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="<?= $basePath ?>request_documents.php" class="nav-item <?= $currentPage === 'request_documents.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            <span>Request Document</span>
        </a>
        <a href="<?= $basePath ?>view_request.php" class="nav-item <?= $currentPage === 'view_request.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>My Requests</span>
        </a>
        <a href="<?= $basePath ?>announcement.php" class="nav-item <?= $currentPage === 'announcement.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a3 3 0 000 6h20a3 3 0 000-6z"/><path d="M14 6H4a2 2 0 00-2 2v4a2 2 0 002 2h16"/><line x1="12" y1="3" x2="12" y2="6"/></svg>
            <span>Announcements</span>
        </a>
        <?php endif; ?>

        <div class="nav-section-label">Account</div>
        <a href="<?= $basePath ?>notification.php" class="nav-item <?= $currentPage === 'notification.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            <span>Notifications</span>
            <?php if ($unreadCount > 0): ?>
            <span class="nav-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $basePath ?>edit.php" class="nav-item <?= $currentPage === 'edit.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>My Profile</span>
        </a>
        <a href="<?= $basePath ?>logout.php" class="nav-item nav-item-logout">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- MAIN CONTENT AREA -->
<div class="main-wrapper">
    <!-- TOPBAR -->
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <div class="topbar-title">
            <span><?= e($pageTitle) ?></span>
        </div>

        <div class="topbar-actions">
            <!-- Notification Bell -->
            <div class="notif-wrapper" id="notifWrapper">
                <button class="notif-btn <?= $unreadCount > 0 ? 'has-unread' : '' ?>" id="notifBtn" aria-label="Notifications">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    <?php if ($unreadCount > 0): ?>
                    <span class="notif-count"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                    <?php endif; ?>
                </button>

                <!-- Notification Dropdown -->
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span>Notifications</span>
                        <?php if ($unreadCount > 0): ?>
                        <a href="<?= $basePath ?>notification.php?mark_all=1" class="notif-mark-all">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <div class="notif-list">
                        <?php if (empty($recentNotifs)): ?>
                        <div class="notif-empty">No notifications yet</div>
                        <?php else: foreach ($recentNotifs as $n): ?>
                        <a href="<?= $basePath ?>notification.php" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                            <div class="notif-dot"></div>
                            <div class="notif-content">
                                <div class="notif-item-title"><?= e($n['title']) ?></div>
                                <div class="notif-item-msg"><?= e(mb_strimwidth($n['message'], 0, 65, '…')) ?></div>
                                <div class="notif-item-time"><?= timeAgo($n['created_at']) ?></div>
                            </div>
                        </a>
                        <?php endforeach; endif; ?>
                    </div>
                    <a href="<?= $basePath ?>notification.php" class="notif-view-all">View all notifications</a>
                </div>
            </div>

            <!-- Profile avatar -->
            <a href="<?= $basePath ?>edit.php" class="topbar-avatar" title="Edit Profile">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
            </a>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="page-content">