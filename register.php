<?php
/**
 * register.php — Public Student Registration
 * Split-panel layout with password strength meter
 */
require_once 'db.php';
require_once 'auth_helper.php';

redirect_if_logged_in();

$pageTitle = 'Create Account';
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = clean($_POST['full_name']        ?? '');
    $email            = clean($_POST['email']            ?? '');
    $phone            = clean($_POST['phone']            ?? '');
    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All required fields must be filled in.";
    } elseif (strlen($full_name) < 3) {
        $error = "Full name must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must include at least one number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'student', 'active')");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed);

            if ($stmt->execute()) {
              // Notify all active admins about the new user registration
              $admins = $conn->query("SELECT user_id FROM users WHERE role='admin' AND status='active'");
              if ($admins && $admins->num_rows > 0) {
                $ins = $conn->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)");
                $type = 'info';
                $notify_msg = "New user registered: " . $full_name . " (<" . $email . ">)";
                while ($a = $admins->fetch_assoc()) {
                  $aid = (int)$a['user_id'];
                  $ins->bind_param("iss", $aid, $notify_msg, $type);
                  $ins->execute();
                }
                $ins->close();
              }
                header("Location: login.php?success=Account+created+successfully!+Please+sign+in.");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

$old = [
    'full_name' => isset($_POST['full_name']) ? clean($_POST['full_name']) : '',
    'email'     => isset($_POST['email'])     ? clean($_POST['email'])     : '',
    'phone'     => isset($_POST['phone'])     ? clean($_POST['phone'])     : '',
];
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
  <style>
    /* Reduce top padding on left panel for register page only */
    /* Tighter top spacing to match mockup */
    .auth-panel-left { padding: 1.2rem 4rem; }
    @media (max-width: 992px) { .auth-panel-left { padding: 1rem 3rem; } }
    @media (max-width: 768px) { .auth-panel-left { padding: 0.9rem 2rem; } }
  </style>
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
      Start Your<br><span>Scholarship Journey</span><br>Today
    </h2>
    <p class="auth-desc">
      Join thousands of Pakistani students who've found and secured
      scholarship funding through ScholarPK. It's completely free.
    </p>

    <ul class="auth-feature-list">
      <li>Free registration — no hidden fees ever</li>
      <li>Apply to unlimited scholarships</li>
      <li>Real-time application tracking</li>
      <li>Instant notifications for deadlines</li>
    </ul>

    <div class="mt-4 p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
      <p style="color:rgba(255,255,255,.55);font-size:.78rem;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600">Student Success</p>
      <p style="color:var(--white);font-size:.9rem;margin:0;font-style:italic;">
        "I found and secured a Rs. 40,000 HEC scholarship through ScholarPK in just one week!"
      </p>
      <p style="color:rgba(255,255,255,.4);font-size:.78rem;margin:.5rem 0 0">— Ayesha R., Lahore</p>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="auth-panel-right">
    <div style="max-width:420px;width:100%;margin:0 auto">

      <p style="font-size:.82rem;color:var(--muted);margin-bottom:2rem">
        <a href="index.php" style="color:var(--muted)"><i class="fas fa-arrow-left me-1"></i> Back to home</a>
      </p>

      <h2 class="auth-form-title">Create Account</h2>
      <p class="auth-form-sub">Students only · Free forever</p>

      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:.875rem">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate>

        <div class="mb-3">
          <label class="form-label-custom">Full Name <span style="color:#dc2626">*</span></label>
          <input type="text" name="full_name" class="form-control-custom"
                 placeholder="Muhammad Ali Khan"
                 value="<?= $old['full_name'] ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label-custom">Email Address <span style="color:#dc2626">*</span></label>
          <input type="email" name="email" class="form-control-custom"
                 placeholder="you@example.com"
                 value="<?= $old['email'] ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label-custom">Phone <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
          <input type="tel" name="phone" class="form-control-custom"
                 placeholder="03XX-XXXXXXX"
                 value="<?= $old['phone'] ?>">
        </div>

        <div class="mb-2">
          <label class="form-label-custom">Password <span style="color:#dc2626">*</span></label>
          <div class="password-toggle">
            <input type="password" name="password" id="password" class="form-control-custom"
                   placeholder="Min. 8 characters" required>
            <i class="fas fa-eye toggle-icon" title="Show/hide"></i>
          </div>
          <!-- Strength bars -->
          <div class="password-strength mt-2">
            <div class="strength-bar"></div>
            <div class="strength-bar"></div>
            <div class="strength-bar"></div>
            <div class="strength-bar"></div>
          </div>
          <p style="font-size:.75rem;color:var(--muted);margin-top:.4rem">
            8+ characters · one uppercase · one number
          </p>
        </div>

        <div class="mb-4">
          <label class="form-label-custom">Confirm Password <span style="color:#dc2626">*</span></label>
          <div class="password-toggle">
            <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom"
                   placeholder="Re-enter password" required>
            <i class="fas fa-eye toggle-icon" title="Show/hide"></i>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="terms" required>
          <label class="form-check-label" for="terms" style="font-size:.84rem;color:var(--muted)">
            I agree to the <a href="#" style="color:var(--blue-500)">Terms of Use</a> and
            <a href="#" style="color:var(--blue-500)">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:.9rem">
          <i class="fas fa-user-plus"></i> Create My Free Account
        </button>

      </form>

      <div class="auth-divider">or</div>

      <p class="text-center" style="font-size:.88rem;color:var(--muted)">
        Already have an account?
        <a href="login.php" style="color:var(--blue-500);font-weight:600">Sign in</a>
      </p>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
