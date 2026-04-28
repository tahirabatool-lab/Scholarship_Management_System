<?php
// ============================================================
// student/includes/layout.php
// Shared Sidebar + Topbar for every Student Panel page
// ============================================================
// HOW TO USE (at the top of every student page):
//
//   require_once '../db.php';
//   require_once '../auth_helper.php';
//   require_login();
//   require_role('student');
//   $pageTitle  = 'My Page Title';
//   $activePage = 'dashboard'; // matches sidebar link
//   include 'includes/layout.php';
//   // ...your page HTML here...
//   include 'includes/layout_end.php';
//
// LOGOUT LOCATIONS IN THIS FILE:
//   1. Sidebar bottom  — "Sign Out" link with icon
//   2. Topbar dropdown — Profile menu with "Sign Out" option
//
// BACK-BUTTON SECURITY:
//   No-cache HTTP headers are set here so that after logout,
//   pressing the browser back button reloads from the server
//   (not from cache), triggering the session check + redirect.
// ============================================================


// ============================================================
// BACK-BUTTON SECURITY: No-Cache Headers
// ============================================================
// These headers tell the browser: "Do NOT save this page
// in cache." After logout, if the user presses Back, the
// browser must request the page fresh from the server.
// The server then checks the session, finds it empty,
// and redirects to login.php automatically.
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");


// ============================================================
// PREPARE DATA for sidebar and topbar
// ============================================================

// Count unread notifications — shown as a red badge in sidebar
$unread_count = 0;
$notif_stmt = $conn->prepare(
    "SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = FALSE"
);
$notif_stmt->bind_param("i", $_SESSION['user_id']);
$notif_stmt->execute();
$unread_count = (int)($notif_stmt->get_result()->fetch_assoc()['c'] ?? 0);

// First letter of name for avatar circle (e.g. "Ali" → "A")
$avatar_letter = strtoupper(substr($_SESSION['full_name'] ?? 'S', 0, 1));

// Full name for dropdown header
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'Student');

// First name only for topbar greeting
$first_name = htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'Student')[0]);

// Email for dropdown header
$user_email = htmlspecialchars($_SESSION['email'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- HTML-level no-cache (backup for browsers that ignore HTTP headers) -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — Student Panel</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Our custom dashboard CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">

    <style>
        /* ============================================================
           Topbar Profile Dropdown Styles
           These are self-contained and do not touch dashboard.css
        ============================================================ */

        /* Wrapper that holds the trigger + the dropdown menu */
        .topbar-profile-wrap {
            position: relative;
        }

        /* The clickable trigger in the topbar */
        .topbar-user {
            cursor: pointer;
            user-select: none;  /* prevent text highlight on click */
        }

        /* Small caret (chevron) arrow next to the name */
        .topbar-caret {
            font-size: .55rem;
            color: var(--muted);
            margin-left: .25rem;
            transition: transform .2s ease;
        }

        /* Rotate caret when dropdown is open */
        .topbar-profile-wrap.open .topbar-caret {
            transform: rotate(180deg);
        }

        /* ── The dropdown menu panel ── */
        .topbar-drop-menu {
            display: none;                    /* hidden by default */
            position: absolute;
            top: calc(100% + 10px);           /* just below the trigger */
            right: 0;
            min-width: 210px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .13);
            z-index: 9999;
            overflow: hidden;
        }

        /* Show the menu when parent has .open class */
        .topbar-profile-wrap.open .topbar-drop-menu {
            display: block;
            animation: fadeSlideDown .18s ease;
        }

        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0);    }
        }

        /* Top section: shows user name, email, role */
        .drop-header {
            padding: .9rem 1.1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .drop-header-name {
            font-size: .88rem;
            font-weight: 700;
            color: #0f172a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .drop-header-email {
            font-size: .72rem;
            color: #64748b;
            margin-top: .1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .drop-header-role {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #0d9488;           /* teal colour = student role */
            margin-top: .35rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* Each clickable item in the dropdown */
        .drop-menu-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem 1.1rem;
            font-size: .84rem;
            color: #334155;
            text-decoration: none;
            transition: background .15s ease;
            cursor: pointer;
        }
        .drop-menu-item:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        /* Small icon box inside each item */
        .drop-menu-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: grid;
            place-items: center;
            font-size: .78rem;
            flex-shrink: 0;
        }

        /* Regular item icon colours */
        .drop-menu-icon.icon-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* Logout item — red colour scheme */
        .drop-menu-item.item-logout:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .drop-menu-icon.icon-red {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Thin divider line between groups */
        .drop-divider {
            height: 1px;
            background: #e2e8f0;
            margin: .3rem 0;
        }


        /* ============================================================
           Sidebar Logout Button
           Styled consistently with existing .sidebar-footer a rules
           but with a dedicated class for clarity
        ============================================================ */
        .sidebar-logout-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem .9rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, .5);
            font-size: .84rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .22s ease;
            width: 100%;
        }
        .sidebar-logout-link:hover {
            color: #fca5a5;                          /* light red on hover */
            background: rgba(248, 113, 113, .1);     /* soft red background */
        }

        /* Icon box inside the sidebar logout link */
        .sidebar-logout-link .s-logout-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: rgba(255, 255, 255, .06);
            display: grid;
            place-items: center;
            font-size: .82rem;
            flex-shrink: 0;
            transition: background .22s ease;
        }
        .sidebar-logout-link:hover .s-logout-icon {
            background: rgba(252, 165, 165, .15);
        }

    </style>
</head>

<body>

<!-- ============================================================
     Mobile sidebar overlay — tapping this closes the sidebar
============================================================ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>


<div class="dash-layout">

    <!-- ============================================================
         SIDEBAR
    ============================================================ -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand / Logo -->
        <div class="sidebar-brand">
            <div class="logo-box">S</div>
            <div>
                <div class="brand-name">ScholarPK</div>
                <div class="brand-sub">Student Portal</div>
            </div>
        </div>

        <!-- Student Info Card -->
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $avatar_letter ?></div>
            <div>
                <div class="sidebar-user-name">
                    <?php
                    // Truncate long names so they fit the sidebar width
                    $display_name = $_SESSION['full_name'] ?? 'Student';
                    echo htmlspecialchars(
                        strlen($display_name) > 18
                            ? substr($display_name, 0, 18) . '…'
                            : $display_name
                    );
                    ?>
                </div>
                <div class="sidebar-user-role">Student</div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="sidebar-nav">

            <div class="nav-section-label">Main</div>

            <a href="dashboard.php"
               class="sidebar-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-th-large"></i></span>
                Dashboard
            </a>

            <a href="scholarships.php"
               class="sidebar-link <?= ($activePage === 'scholarships') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span>
                Browse Scholarships
            </a>

            <a href="applications.php"
               class="sidebar-link <?= ($activePage === 'applications') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                My Applications
            </a>

            <a href="documents.php"
               class="sidebar-link <?= ($activePage === 'documents') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
                My Documents
            </a>

            <div class="nav-section-label" style="margin-top: .5rem;">Account</div>

            <a href="notifications.php"
               class="sidebar-link <?= ($activePage === 'notifications') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>

            <a href="profile.php"
               class="sidebar-link <?= ($activePage === 'profile') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                My Profile
            </a>

            <a href="change_password.php"
               class="sidebar-link <?= ($activePage === 'password') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-lock"></i></span>
                Change Password
            </a>

        </nav>

        <!-- ============================================================
             SIDEBAR FOOTER — LOGOUT BUTTON
             Location: Bottom of sidebar, always visible
             Links to: student/logout.php (same directory)
        ============================================================ -->
        <div class="sidebar-footer">
            <a href="logout.php"
               class="sidebar-logout-link"
               onclick="return askBeforeLogout()">
                <span class="s-logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </span>
                Sign Out
            </a>
        </div>

    </aside>
    <!-- END SIDEBAR -->


    <!-- ============================================================
         MAIN CONTENT AREA
    ============================================================ -->
    <div class="main-content">

        <!-- ============================================================
             TOPBAR
             Contains: page title | notifications bell | profile dropdown
        ============================================================ -->
        <header class="topbar">

            <!-- Left side: hamburger menu (mobile) + page title -->
            <div class="topbar-left">
                <button class="topbar-toggle"
                        id="topbarToggle"
                        onclick="toggleSidebar()"
                        aria-label="Open menu">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="page-title-bar">
                    <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
                </span>
            </div>

            <!-- Right side: notifications + profile dropdown -->
            <div class="topbar-right">

                <!-- Notifications bell button -->
                <a href="notifications.php"
                   class="topbar-btn"
                   aria-label="View Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge-dot"></span>
                    <?php endif; ?>
                </a>

                <!-- ============================================================
                     TOPBAR PROFILE DROPDOWN
                     Contains: user info | profile link | password link | logout
                ============================================================ -->
                <div class="topbar-profile-wrap" id="profileDropWrap">

                    <!-- Clickable trigger: avatar + name + caret -->
                    <div class="topbar-user"
                         onclick="toggleProfileDropdown()"
                         role="button"
                         tabindex="0"
                         aria-label="Open profile menu"
                         aria-haspopup="true"
                         aria-expanded="false"
                         id="profileTrigger">
                        <div class="topbar-avatar"><?= $avatar_letter ?></div>
                        <span class="topbar-name d-none d-sm-inline">
                            <?= $first_name ?>
                        </span>
                        <i class="fas fa-chevron-down topbar-caret"></i>
                    </div>

                    <!-- Dropdown menu panel -->
                    <div class="topbar-drop-menu" id="topbarDropMenu">

                        <!-- User info section at top of dropdown -->
                        <div class="drop-header">
                            <div class="drop-header-name"><?= $full_name ?></div>
                            <div class="drop-header-email"><?= $user_email ?></div>
                            <div class="drop-header-role">
                                <i class="fas fa-circle" style="font-size: .4rem;"></i>
                                Student Account
                            </div>
                        </div>

                        <!-- Edit Profile -->
                        <a href="profile.php" class="drop-menu-item">
                            <span class="drop-menu-icon icon-blue">
                                <i class="fas fa-user-pen"></i>
                            </span>
                            Edit Profile
                        </a>

                        <!-- Change Password -->
                        <a href="change_password.php" class="drop-menu-item">
                            <span class="drop-menu-icon icon-blue">
                                <i class="fas fa-key"></i>
                            </span>
                            Change Password
                        </a>

                        <!-- Divider line -->
                        <div class="drop-divider"></div>

                        <!-- LOGOUT — red styled, asks for confirmation -->
                        <a href="logout.php"
                           class="drop-menu-item item-logout"
                           onclick="return askBeforeLogout()">
                            <span class="drop-menu-icon icon-red">
                                <i class="fas fa-right-from-bracket"></i>
                            </span>
                            Sign Out
                        </a>

                    </div>
                    <!-- END topbar-drop-menu -->

                </div>
                <!-- END topbar-profile-wrap -->

            </div>
            <!-- END topbar-right -->

        </header>
        <!-- END TOPBAR -->


        <!-- ============================================================
             PAGE BODY
             Each student page injects its content here.
             Closed by layout_end.php
        ============================================================ -->
        <div class="page-body">
