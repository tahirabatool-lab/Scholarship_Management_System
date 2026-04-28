<?php
/**
 * admin/login.php — Admin Portal Login
 * ─────────────────────────────────────────────────────────────
 * NEW FILE — place at:  your-project/admin/login.php
 *
 * Logic:
 *  1. If already logged in as admin  → redirect to admin/dashboard.php
 *  2. If already logged in as student → show "access denied" message
 *  3. On POST: validate email + password
 *  4. Check role === 'admin' — if student tries, show "Access Denied"
 *  5. On success: create session, redirect to admin/dashboard.php
 *  6. Uses same users table, same password_verify() as student login
 *
 * Dependencies (already exist in your project):
 *  → ../db.php           (database connection, $conn)
 *  → ../auth_helper.php  (clean(), is_logged_in(), get_role())
 * ─────────────────────────────────────────────────────────────
 */

session_start();

// ── Load project dependencies ──
require_once '../db.php';         // provides $conn (mysqli)
require_once '../auth_helper.php'; // provides clean(), is_logged_in(), get_role()

// ── Already logged in? ──
if (is_logged_in()) {
    if (get_role() === 'admin') {
        // Admin already authenticated → go to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // A student somehow landed here — don't auto-redirect, show a message
        // They can log out from their own panel
        $already_student = true;
    }
}

$error   = '';
$success = '';
$already_student = $already_student ?? false;

// Show any message passed via URL (e.g. after logout)
if (!empty($_GET['success'])) $success = clean($_GET['success']);

// ── Process Login Form ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_student) {

    $email    = clean($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';   // raw — password_verify() handles safety

    // ── Basic validation ──
    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {

        // ── Look up user by email ──
        $stmt = $conn->prepare("
            SELECT user_id, full_name, password, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // ── Check account status ──
            if ($user['status'] !== 'active') {
                $error = "This account has been deactivated. Please contact the system owner.";

            // ── ROLE GUARD: only admins may proceed ──
            } elseif ($user['role'] !== 'admin') {
                // Student tried to use the admin portal
                $error = "Access Denied. This portal is for administrators only. 
                          Please use the 
                          <a href='../login.php' class='alert-link'>Student Login</a> instead.";

            // ── Verify password ──
            } elseif (!password_verify($password, $user['password'])) {
                $error = "Incorrect password. Please try again.";

            } else {
                // ── ✅ Authentication successful ──

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['email']     = $email;

                // Update last_login timestamp
                $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $upd->bind_param("i", $user['user_id']);
                $upd->execute();

                // Log the admin login to activity_logs
                $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $uid = $user['user_id'];
                $log = $conn->prepare("
                    INSERT INTO activity_logs
                      (user_id, action, table_name, record_id, description, ip_address)
                    VALUES (?, 'ADMIN_LOGIN', 'users', ?, 'Admin logged in', ?)
                ");
                $log->bind_param("iis", $uid, $uid, $ip);
                $log->execute();

                // Redirect to admin dashboard
                header("Location: dashboard.php");
                exit();
            }

        } else {
            // No user found with that email
            $error = "No administrator account found with that email address.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal — ScholarPK</title>

  <!-- Same dependencies as public website -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,700;0,9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ── CSS Variables — matches public website ── */
    :root {
      --blue-900: #0a1628;
      --blue-800: #0f2347;
      --blue-700: #1a3a6b;
      --blue-600: #1e4d9b;
      --blue-500: #2563c4;
      --blue-100: #dce9ff;
      --blue-50:  #f0f5ff;
      --accent:   #f59e0b;
      --accent-dk:#d97706;
      --ink:      #0f172a;
      --body:     #334155;
      --muted:    #64748b;
      --border:   #e2e8f0;
      --bg:       #f8fafc;
      --white:    #ffffff;
      --green:    #16a34a;
      --green-lt: #dcfce7;
      --red:      #dc2626;
      --red-lt:   #fee2e2;
      --radius:   8px;
      --shadow:   0 4px 16px rgba(0,0,0,.10);
      --shadow-lg:0 12px 40px rgba(0,0,0,.14);
      --t:        .22s cubic-bezier(.4,0,.2,1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { height: 100%; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      min-height: 100%;
      display: flex;
      flex-direction: column;
      -webkit-font-smoothing: antialiased;
      color: var(--body);
    }

    /* ── Top mini-bar ── */
    .admin-topbar {
      background: var(--blue-900);
      padding: .55rem 0;
      border-bottom: 2px solid var(--accent);
    }
    .admin-topbar .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }
    .topbar-brand {
      font-family: 'Fraunces', serif;
      color: var(--white);
      font-size: 1.1rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: .5rem;
      text-decoration: none;
    }
    .topbar-brand:hover { color: var(--accent); }
    .topbar-brand .brand-icon {
      width: 28px; height: 28px;
      background: var(--accent);
      border-radius: 6px;
      display: grid; place-items: center;
      color: var(--ink);
      font-size: .72rem; font-weight: 900;
      flex-shrink: 0;
    }
    .topbar-back {
      font-size: .78rem;
      color: rgba(255,255,255,.55);
      text-decoration: none;
      display: flex; align-items: center; gap: .35rem;
      transition: color var(--t);
    }
    .topbar-back:hover { color: rgba(255,255,255,.9); }

    /* ── Main layout ── */
    .login-wrapper {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: calc(100vh - 48px);
    }

    /* ── Left panel ── */
    .login-panel-left {
      background: linear-gradient(160deg, var(--blue-900) 0%, var(--blue-700) 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3.5rem;
      position: relative;
      overflow: hidden;
    }
    /* Decorative blobs */
    .login-panel-left::before {
      content: '';
      position: absolute; top: -80px; right: -80px;
      width: 280px; height: 280px;
      background: rgba(255,255,255,.04);
      border-radius: 50%;
    }
    .login-panel-left::after {
      content: '';
      position: absolute; bottom: -60px; left: -40px;
      width: 220px; height: 220px;
      background: rgba(245,158,11,.08);
      border-radius: 50%;
    }

    .left-content { position: relative; z-index: 1; }

    .admin-badge {
      display: inline-flex;
      align-items: center; gap: .5rem;
      background: rgba(245,158,11,.15);
      border: 1px solid rgba(245,158,11,.3);
      color: var(--accent);
      font-size: .72rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      padding: .38rem .9rem;
      border-radius: 999px;
      margin-bottom: 1.5rem;
    }
    .admin-badge .dot {
      width: 6px; height: 6px;
      background: var(--accent);
      border-radius: 50%;
      animation: blink 2s ease infinite;
    }
    @keyframes blink {
      0%,100% { opacity:1; } 50% { opacity:.3; }
    }

    .left-heading {
      font-family: 'Fraunces', serif;
      font-size: clamp(1.7rem, 3vw, 2.2rem);
      font-weight: 800;
      color: var(--white);
      line-height: 1.2;
      margin-bottom: 1rem;
    }
    .left-heading span { color: var(--accent); }

    .left-desc {
      font-size: .9rem;
      color: rgba(255,255,255,.6);
      line-height: 1.75;
      margin-bottom: 2rem;
      max-width: 340px;
    }

    .access-list {
      list-style: none;
      padding: 0; margin: 0;
    }
    .access-list li {
      display: flex; align-items: center; gap: .75rem;
      color: rgba(255,255,255,.75);
      font-size: .86rem;
      padding: .45rem 0;
    }
    .access-list li::before {
      content: '';
      width: 20px; height: 20px;
      background: rgba(245,158,11,.18);
      border-radius: 50%;
      display: grid; place-items: center;
      flex-shrink: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2 6l3 3 5-5' stroke='%23f59e0b' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: center;
    }

    .left-warning {
      margin-top: 2.5rem;
      padding: .85rem 1.1rem;
      background: rgba(220,38,38,.1);
      border: 1px solid rgba(220,38,38,.25);
      border-radius: var(--radius);
      font-size: .78rem;
      color: rgba(255,255,255,.6);
      line-height: 1.6;
    }
    .left-warning strong { color: #fca5a5; }

    /* ── Right panel (form) ── */
    .login-panel-right {
      background: var(--white);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3.5rem;
      overflow-y: auto;
    }

    .form-area { max-width: 380px; width: 100%; margin: 0 auto; }

    .back-link {
      display: inline-flex; align-items: center; gap: .4rem;
      font-size: .78rem; color: var(--muted);
      text-decoration: none; margin-bottom: 2.5rem;
      transition: color var(--t);
    }
    .back-link:hover { color: var(--blue-600); }

    .form-title {
      font-family: 'Fraunces', serif;
      font-size: 1.7rem; font-weight: 800;
      color: var(--ink); margin-bottom: .35rem;
      line-height: 1.2;
    }
    .form-subtitle {
      font-size: .88rem; color: var(--muted);
      margin-bottom: 2rem;
    }

    /* Alert */
    .form-alert {
      padding: .78rem 1rem;
      border-radius: var(--radius);
      font-size: .85rem;
      margin-bottom: 1.4rem;
      display: flex; align-items: flex-start; gap: .6rem;
      line-height: 1.55;
    }
    .form-alert-error   { background: var(--red-lt);   color: #991b1b; border: 1px solid #fecaca; }
    .form-alert-success { background: var(--green-lt); color: #166534; border: 1px solid #bbf7d0; }
    .form-alert-warning { background: #fef9c3;         color: #92400e; border: 1px solid #fde68a; }
    .form-alert a.alert-link { color: inherit; font-weight: 700; }

    /* Field */
    .field-lbl {
      display: block;
      font-size: .74rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .07em;
      color: var(--muted); margin-bottom: .4rem;
    }
    .field-input {
      width: 100%;
      padding: .7rem 1rem;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .92rem; color: var(--ink);
      background: var(--white);
      transition: border-color var(--t), box-shadow var(--t);
      margin-bottom: 1.2rem;
    }
    .field-input:focus {
      outline: none;
      border-color: var(--blue-500);
      box-shadow: 0 0 0 3px rgba(37,99,196,.1);
    }

    /* Password toggle wrapper */
    .pw-wrap { position: relative; }
    .pw-wrap .field-input { padding-right: 2.8rem; margin-bottom: 0; }
    .pw-toggle {
      position: absolute; right: .9rem; top: 50%;
      transform: translateY(-50%);
      cursor: pointer; color: var(--muted); font-size: .9rem;
      transition: color var(--t); border: none; background: none;
      padding: 0; line-height: 1;
    }
    .pw-toggle:hover { color: var(--blue-500); }

    /* Submit button */
    .btn-submit {
      width: 100%; padding: .85rem;
      background: var(--blue-500); color: var(--white);
      border: none; border-radius: var(--radius);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .95rem; font-weight: 700;
      cursor: pointer;
      transition: all var(--t);
      display: flex; align-items: center;
      justify-content: center; gap: .5rem;
      margin-top: 1.5rem;
    }
    .btn-submit:hover {
      background: var(--blue-700);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(37,99,196,.35);
    }

    .divider-line {
      display: flex; align-items: center; gap: 1rem;
      color: var(--muted); font-size: .75rem;
      margin: 1.4rem 0;
    }
    .divider-line::before,
    .divider-line::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }

    .student-link-row {
      text-align: center;
      font-size: .86rem; color: var(--muted);
    }
    .student-link-row a {
      color: var(--blue-500);
      font-weight: 600;
      text-decoration: none;
    }
    .student-link-row a:hover { color: var(--blue-700); }

    /* Footer */
    .login-footer {
      background: var(--blue-900);
      text-align: center;
      padding: .9rem;
      font-size: .74rem;
      color: rgba(255,255,255,.3);
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .login-wrapper { grid-template-columns: 1fr; }
      .login-panel-left { display: none; }
      .login-panel-right { padding: 2.5rem 1.5rem; min-height: calc(100vh - 48px); }
    }
  </style>
</head>
<body>

<!-- ── Split Layout ── -->
<div class="login-wrapper">

  <!-- LEFT: Info panel -->
  <div class="login-panel-left">
    <div class="left-content">

      <div class="admin-badge">
        <span class="dot"></span>
        Secure Admin Access
      </div>

      <h1 class="left-heading">
        ScholarPK<br>
        <span>Admin Portal</span>
      </h1>

      <p class="left-desc">
        Full control over scholarships, applications, users,
        payments, and reports. Restricted to authorized administrators only.
      </p>

      <ul class="access-list">
        <li>Manage scholarships and applications</li>
        <li>Approve or reject student applications</li>
        <li>Disburse scholarship payments</li>
        <li>Send notifications to students</li>
        <li>View reports and analytics</li>
      </ul>

      <div class="left-warning">
        <strong>Restricted Area.</strong>
        Unauthorized access attempts are logged. If you are a student,
        please use the
        <a href="../login.php" style="color:#fca5a5;font-weight:600">Student Login</a> instead.
      </div>

    </div>
  </div>

  <!-- RIGHT: Form panel -->
  <div class="login-panel-right">
    <div class="form-area">

      <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to homepage
      </a>

      <h2 class="form-title">Admin Sign In</h2>
      <p class="form-subtitle">Enter your administrator credentials to continue.</p>

      <!-- Already logged in as student warning -->
      <?php if ($already_student): ?>
      <div class="form-alert form-alert-warning">
        <i class="fas fa-exclamation-triangle" style="margin-top:.1rem;flex-shrink:0"></i>
        <div>
          You are currently logged in as a <strong>student</strong>.
          To access the admin panel, please
          <a href="../logout.php" class="alert-link">log out first</a>,
          then sign in with your admin credentials.
        </div>
      </div>
      <?php endif; ?>

      <!-- Success (e.g. after logout) -->
      <?php if ($success): ?>
      <div class="form-alert form-alert-success">
        <i class="fas fa-check-circle" style="flex-shrink:0"></i>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <!-- Error -->
      <?php if ($error): ?>
      <div class="form-alert form-alert-error">
        <i class="fas fa-times-circle" style="margin-top:.1rem;flex-shrink:0"></i>
        <span><?= $error /* already contains safe HTML — see role guard message */ ?></span>
      </div>
      <?php endif; ?>

      <!-- Login Form -->
      <?php if (!$already_student): ?>
      <form method="POST" action="login.php" novalidate>

        <div>
          <label class="field-lbl" for="email">
            Email Address
          </label>
          <input
            type="email"
            id="email"
            name="email"
            class="field-input"
            placeholder="admin@scholarpk.edu.pk"
            value="<?= isset($_POST['email']) ? htmlspecialchars(clean($_POST['email'])) : '' ?>"
            autocomplete="email"
            required
          >
        </div>

        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
            <label class="field-lbl" for="password" style="margin-bottom:0">Password</label>
          </div>
          <div class="pw-wrap">
            <input
              type="password"
              id="password"
              name="password"
              class="field-input"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            >
            <button type="button" class="pw-toggle" id="pwToggle"
                    onclick="togglePassword()" aria-label="Show/hide password">
              <i class="fas fa-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="fas fa-shield-alt"></i>
          Sign In to Admin Panel
        </button>

      </form>
      <?php endif; ?>

      <div class="divider-line">or</div>

      <div class="student-link-row">
        Are you a student?
        <a href="../login.php">Student Login →</a>
      </div>

    </div>
  </div>

</div><!-- /login-wrapper -->

<!-- Footer -->
<!-- <div class="login-footer">
  &copy; <?= date('Y') ?> ScholarPK &nbsp;·&nbsp; Admin Portal &nbsp;·&nbsp;
  All access attempts are logged and monitored.
</div> -->

<!-- Bootstrap JS (needed for potential future enhancements) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password show/hide toggle
function togglePassword() {
  const input = document.getElementById('password');
  const icon  = document.getElementById('pwIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// Auto-hide alerts after 6 seconds
setTimeout(() => {
  document.querySelectorAll('.form-alert').forEach(el => {
    el.style.transition = 'opacity .5s ease';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 500);
  });
}, 6000);
</script>
</body>
</html>
