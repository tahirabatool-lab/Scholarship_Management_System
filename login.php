<?php
/**
 * login.php — Public Login Page
 * Split-panel layout: brand left, form right
 */
require_once 'db.php';
require_once 'auth_helper.php';

redirect_if_logged_in();

$pageTitle = 'Sign In';
$error     = '';
$success   = '';

if (!empty($_GET['error']))   $error   = clean($_GET['error']);
if (!empty($_GET['success'])) $success = clean($_GET['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['status'] !== 'active') {
                $error = "Your account has been deactivated. Contact support.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "Incorrect password. Please try again.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['email']     = $email;

                $conn->query("UPDATE users SET last_login = NOW() WHERE user_id = {$user['user_id']}");

                header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
                exit();
            }
        } else {
            $error = "No account found with that email.";
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
  <title><?= $pageTitle ?> — ScholarPK</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,700;0,9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">

  <!-- LEFT PANEL -->
  <div class="auth-panel-left">
    <a href="index.php" class="auth-brand">
      <span class="brand-icon">S</span>
      Scholar<span style="color:var(--accent)">PK</span>
    </a>

    <h2 class="auth-tagline">
      Unlock Your<br><span>Academic Potential</span>
    </h2>
    <p class="auth-desc">
      Sign in to access your scholarship dashboard, track applications,
      and discover new opportunities matched to your profile.
    </p>

    <ul class="auth-feature-list">
      <li>Track all your applications in one place</li>
      <li>Get deadline reminders and status updates</li>
      <li>Apply to multiple scholarships with ease</li>
      <li>Receive payment notifications directly</li>
    </ul>

    <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,.1)">
      <p style="color:rgba(255,255,255,.4);font-size:.78rem">
        Trusted by 12,000+ students across Pakistan
      </p>
    </div>
  </div>

  <!-- RIGHT PANEL (Form) -->
  <div class="auth-panel-right">
    <div style="max-width:400px;width:100%;margin:0 auto">

      <p style="font-size:.82rem;color:var(--muted);margin-bottom:2.5rem">
        <a href="index.php" style="color:var(--muted)"><i class="fas fa-arrow-left me-1"></i> Back to home</a>
      </p>

      <h2 class="auth-form-title">Welcome Back</h2>
      <p class="auth-form-sub">Sign in to your student account</p>

      <?php if ($error): ?>
      <div class="alert alert-danger alert-auto-dismiss alert-dismissible fade show" role="alert" style="font-size:.875rem">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success alert-auto-dismiss alert-dismissible fade show" role="alert" style="font-size:.875rem">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>

        <div class="mb-3">
          <label class="form-label-custom">Email Address</label>
          <input type="email" name="email" class="form-control-custom"
                 placeholder="you@example.com"
                 value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                 required>
        </div>

        <div class="mb-1">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label-custom mb-0">Password</label>
            <a href="forgot_password.php" style="font-size:.78rem;color:var(--blue-500)">Forgot password?</a>
          </div>
          <div class="password-toggle">
            <input type="password" name="password" id="password" class="form-control-custom"
                   placeholder="••••••••" required>
            <i class="fas fa-eye toggle-icon" title="Show/hide password"></i>
          </div>
        </div>

        <div class="form-check mt-3 mb-4">
          <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
          <label class="form-check-label" for="rememberMe" style="font-size:.85rem;color:var(--muted)">
            Keep me signed in
          </label>
        </div>

        <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:.9rem">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>

      </form>

      <div class="auth-divider">or</div>

      <p class="text-center" style="font-size:.88rem;color:var(--muted)">
        Don't have an account?
        <a href="register.php" style="color:var(--blue-500);font-weight:600">Create one free</a>
      </p>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
