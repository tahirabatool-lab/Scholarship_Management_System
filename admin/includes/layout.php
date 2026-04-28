<?php
/**
 * admin/includes/layout.php
 * Opens shared sidebar + topbar for every admin page.
 *
 * USAGE (at top of every admin page):
 *   require_once '../db.php';
 *   require_once '../auth_helper.php';
 *   require_login();
 *   require_role('admin');
 *   $pageTitle  = 'Dashboard';
 *   $activePage = 'dashboard';
 *   include 'includes/layout.php';
 *   // ... page content ...
 *   include 'includes/layout_end.php';
 */

// Badge counts for sidebar
$pending_apps = (int)($conn->query("SELECT COUNT(*) c FROM applications WHERE status='pending'")->fetch_assoc()['c'] ?? 0);
$unread_msgs  = (int)($conn->query("SELECT COUNT(*) c FROM contact_messages WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0);
$admin_unread_notifs = (int)($conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id=".$_SESSION['user_id']." AND is_read=FALSE")->fetch_assoc()['c'] ?? 0);

$admin_initial = strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1));
$admin_name    = $_SESSION['full_name'] ?? 'Administrator';
$admin_first   = explode(' ', $admin_name)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — ScholarPK Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>

<!-- Mobile overlay -->
<div class="sb-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>

<div class="admin-layout">

<!-- ═══════ SIDEBAR ═══════ -->
<aside class="admin-sidebar" id="adminSidebar">

  <!-- Brand -->
  <div class="sb-brand">
    <div class="sb-logo">S</div>
    <div class="sb-brand-text">
      <div class="sb-brand-name">ScholarPK</div>
      <div class="sb-brand-sub">Admin Panel</div>
    </div>
  </div>

  <!-- Admin identity -->
  <div class="sb-admin-card">
    <div class="sb-admin-avatar"><?= $admin_initial ?></div>
    <div>
      <div class="sb-admin-name">
        <?= htmlspecialchars(strlen($admin_name) > 18 ? substr($admin_name,0,18).'…' : $admin_name) ?>
      </div>
      <div class="sb-admin-role">Administrator</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sb-nav">

    <div class="sb-section-lbl">Overview</div>

    <a href="dashboard.php"
       class="sb-link <?= ($activePage==='dashboard') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-chart-pie"></i></span>
      Dashboard
    </a>

    <div class="sb-section-lbl">Scholarships</div>

    <a href="scholarships.php"
       class="sb-link <?= ($activePage==='scholarships') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-graduation-cap"></i></span>
      Scholarships
    </a>

    <a href="scholarships.php?action=add"
       class="sb-link <?= ($activePage==='add_scholarship') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-plus-circle"></i></span>
      Add Scholarship
    </a>

    <div class="sb-section-lbl">Applications</div>

    <a href="applications.php"
       class="sb-link <?= ($activePage==='applications') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-file-alt"></i></span>
      All Applications
      <?php if ($pending_apps > 0): ?>
        <span class="sb-badge"><?= $pending_apps ?></span>
      <?php endif; ?>
    </a>

    <a href="payments.php"
       class="sb-link <?= ($activePage==='payments') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-money-bill-wave"></i></span>
      Payments
    </a>

    <div class="sb-section-lbl">People</div>

    <a href="users.php"
       class="sb-link <?= ($activePage==='users') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-users"></i></span>
      Users
    </a>

    <a href="notifications.php"
       class="sb-link <?= ($activePage==='notifications') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-bell"></i></span>
      Send Notifications
    </a>

    <a href="my_notifications.php"
       class="sb-link <?= ($activePage==='my_notifications') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-inbox"></i></span>
      My Notifications
      <?php if ($admin_unread_notifs > 0): ?>
        <span class="sb-badge"><?= $admin_unread_notifs ?></span>
      <?php endif; ?>
    </a>

    <a href="messages.php"
       class="sb-link <?= ($activePage==='messages') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-envelope"></i></span>
      Contact Messages
      <?php if ($unread_msgs > 0): ?>
        <span class="sb-badge green"><?= $unread_msgs ?></span>
      <?php endif; ?>
    </a>

    <div class="sb-section-lbl">Reports</div>

    <a href="reports.php"
       class="sb-link <?= ($activePage==='reports') ? 'active' : '' ?>">
      <span class="sb-icon"><i class="fas fa-chart-bar"></i></span>
      Reports
    </a>

  </nav>

  <!-- Logout button in sidebar -->
  <div class="sb-footer">
    <a href="logout.php"
       class="sb-logout"
       onclick="return confirm('Are you sure you want to sign out?')">
      <span class="sb-icon"><i class="fas fa-sign-out-alt"></i></span>
      Sign Out
    </a>
  </div>

</aside><!-- /sidebar -->

<!-- ═══════ MAIN ═══════ -->
<div class="admin-main">

  <!-- Topbar -->
  <header class="admin-topbar">
    <div class="topbar-left">
      <button class="topbar-toggle" id="topbarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </div>

    <div class="topbar-right">
      <!-- Notifications bell -->
      <a href="my_notifications.php" class="topbar-icon-btn" title="My Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($admin_unread_notifs > 0): ?><span class="notif-dot"></span><?php endif; ?>
      </a>

      <!-- Profile dropdown with Logout -->
      <div class="profile-drop" id="profileDrop">
        <div class="profile-trigger" onclick="toggleDrop()">
          <div class="profile-avatar"><?= $admin_initial ?></div>
          <span class="profile-name d-none d-sm-inline"><?= htmlspecialchars($admin_first) ?></span>
          <i class="fas fa-chevron-down" style="font-size:.6rem;color:var(--muted)"></i>
        </div>

        <div class="profile-drop-menu" id="dropMenu">
          <div class="drop-header">
            <div class="d-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="d-role">Administrator</div>
            <div style="font-size:.72rem;color:var(--muted);margin-top:.2rem">
              <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
            </div>
          </div>
          <a href="reports.php" class="drop-item text-decoration-none" style="color:inherit">
            <i class="fas fa-chart-bar"></i> Reports
          </a>
          <div class="drop-divider"></div>
          <!-- LOGOUT in dropdown -->
          <a href="logout.php"
             class="drop-item danger text-decoration-none"
             onclick="return confirm('Sign out of admin panel?')">
            <i class="fas fa-sign-out-alt"></i> Sign Out
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Page content area -->
  <div class="admin-body">
