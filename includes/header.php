<?php
/**
 * includes/header.php
 * ─────────────────────────────────────────────────────────────
 * WHAT CHANGED FROM ORIGINAL:
 *   • Replaced the two right-side buttons (Sign In + Get Started)
 *     with a single "Account ▾" dropdown containing:
 *       — Student Login   → login.php
 *       — Create Account  → register.php
 *       — divider
 *       — Admin Portal    → admin/login.php
 *   • Added .nav-account-drop CSS block (self-contained, no conflict)
 *   • All existing nav links (Home, Scholarships, About, Contact)
 *     are UNTOUCHED.
 *   • Bootstrap 5 Dropdown (already loaded) powers the menu.
 * ─────────────────────────────────────────────────────────────
 */
$pageTitle  = $pageTitle  ?? 'Scholarship Management System';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Pakistan's trusted scholarship management platform. Find, apply and track scholarships for Matric, Intermediate, and University students.">
  <title><?= htmlspecialchars($pageTitle) ?> — ScholarPK</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;0,9..144,800;1,9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- ── Account Dropdown styles (added — does not touch existing CSS) ── -->
  <style>
    /* Dropdown trigger button */
    .nav-account-btn {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: .42rem 1rem !important;
      border-radius: 6px;
      font-weight: 600;
      font-size: .88rem;
      color: rgba(255,255,255,.88) !important;
      background: rgba(255,255,255,.1);
      border: 1.5px solid rgba(255,255,255,.22);
      cursor: pointer;
      transition: all .22s ease;
      text-decoration: none;
      white-space: nowrap;
    }
    .nav-account-btn::after { display: none !important; } /* remove BS caret default */
    .nav-account-btn .caret-icon {
      font-size: .6rem;
      transition: transform .22s ease;
      opacity: .7;
    }
    .nav-account-btn:hover,
    .nav-account-btn.show {
      background: rgba(255,255,255,.18);
      border-color: rgba(255,255,255,.45);
      color: #fff !important;
    }
    .nav-account-btn.show .caret-icon { transform: rotate(180deg); }

    /* Scrolled state */
    .site-nav.scrolled .nav-account-btn {
      color: var(--ink) !important;
      background: var(--blue-50);
      border-color: var(--blue-100);
    }
    .site-nav.scrolled .nav-account-btn:hover,
    .site-nav.scrolled .nav-account-btn.show {
      background: var(--blue-100);
      border-color: var(--blue-300);
      color: var(--blue-700) !important;
    }

    /* Dropdown menu */
    .nav-account-menu {
      min-width: 210px;
      border: 1px solid var(--border);
      border-radius: 10px;
      box-shadow: 0 8px 30px rgba(0,0,0,.13);
      padding: .5rem;
      margin-top: .55rem !important;
      background: var(--white);
    }

    /* Section label inside dropdown */
    .nav-drop-label {
      font-size: .65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--muted);
      padding: .3rem .75rem .15rem;
      display: block;
    }

    /* Each dropdown item */
    .nav-account-menu .dropdown-item {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: .6rem .75rem;
      border-radius: 6px;
      font-size: .86rem;
      font-weight: 500;
      color: var(--body);
      transition: background .18s ease, color .18s ease;
    }
    .nav-account-menu .dropdown-item:hover {
      background: var(--blue-50);
      color: var(--blue-700);
    }
    .nav-account-menu .dropdown-item .di-icon {
      width: 28px;
      height: 28px;
      border-radius: 7px;
      display: grid;
      place-items: center;
      font-size: .78rem;
      flex-shrink: 0;
    }

    /* Student items */
    .nav-account-menu .item-student .di-icon {
      background: var(--blue-100);
      color: var(--blue-600);
    }
    .nav-account-menu .item-register .di-icon {
      background: #fef3c7;
      color: var(--accent-dk);
    }

    /* Admin item — visually distinct */
    .nav-account-menu .item-admin {
      color: var(--body);
    }
    .nav-account-menu .item-admin .di-icon {
      background: #f0fdf4;
      color: #16a34a;
    }
    .nav-account-menu .item-admin:hover {
      background: #f0fdf4;
      color: #15803d;
    }

    /* CTA — Get Started button kept as accent */
    .nav-cta-accent {
      background: var(--accent);
      color: var(--ink) !important;
      font-weight: 700;
      padding: .45rem 1.1rem !important;
      border-radius: 6px;
      font-size: .88rem;
      transition: all .22s ease;
      white-space: nowrap;
    }
    .nav-cta-accent:hover {
      background: var(--accent-dk);
      transform: translateY(-1px);
    }
    /* Remove underline indicator on CTA */
    .site-nav .nav-cta-accent::after { display: none !important; }
  </style>
</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<nav class="site-nav navbar navbar-expand-lg">
  <div class="container">

    <!-- Brand (UNCHANGED) -->
    <a class="navbar-brand" href="index.php">
      <span class="brand-icon">S</span>
      Scholar<span style="color:var(--accent)">PK</span>
    </a>

    <!-- Mobile Toggle (UNCHANGED) -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMenu"
            aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
      <div class="navbar-toggler-icon-custom">
        <span></span><span></span><span></span>
      </div>
    </button>

    <!-- Links -->
    <div class="collapse navbar-collapse" id="navMenu">

      <!-- Main nav links — UNCHANGED -->
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $activePage==='home'?'active':'' ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activePage==='scholarships'?'active':'' ?>" href="scholarships.php">Scholarships</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activePage==='about'?'active':'' ?>" href="about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $activePage==='contact'?'active':'' ?>" href="contact.php">Contact</a>
        </li>
      </ul>

      <!-- ── RIGHT SIDE: Account dropdown + CTA ──
           REPLACES the old two buttons:
             <a href="login.php" ...>Sign In</a>
             <a href="register.php" class="nav-cta ...">Get Started</a>
      ── -->
      <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">

        <!-- Account Dropdown (Bootstrap 5 dropdown) -->
        <div class="dropdown">
          <a href="#"
             class="nav-account-btn dropdown-toggle"
             id="accountDropdown"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             role="button">
            <i class="fas fa-user-circle"></i>
            Account
            <i class="fas fa-chevron-down caret-icon"></i>
          </a>

          <ul class="dropdown-menu nav-account-menu" aria-labelledby="accountDropdown">

            <!-- Student section -->
            <span class="nav-drop-label">Student</span>

            <li>
              <a class="dropdown-item item-student" href="login.php">
                <span class="di-icon"><i class="fas fa-sign-in-alt"></i></span>
                Student Login
              </a>
            </li>
            <li>
              <a class="dropdown-item item-register" href="register.php">
                <span class="di-icon"><i class="fas fa-user-plus"></i></span>
                Create Account
              </a>
            </li>

            <!-- Divider -->
            <li><hr class="dropdown-divider" style="margin:.4rem .5rem;border-color:var(--border)"></li>

            <!-- Admin section -->
            <span class="nav-drop-label">Administration</span>

            <li>
              <a class="dropdown-item item-admin" href="admin/login.php">
                <span class="di-icon"><i class="fas fa-shield-alt"></i></span>
                Admin Portal
              </a>
            </li>

          </ul>
        </div><!-- /dropdown -->

        <!-- Get Started CTA — kept exactly as before, just new class name -->
        <a href="register.php" class="nav-link nav-cta-accent">Get Started</a>

      </div><!-- /right buttons -->

    </div><!-- /navbar-collapse -->
  </div><!-- /container -->
</nav>
<!-- ══════════ END NAVBAR ══════════ -->
