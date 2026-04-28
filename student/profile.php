<?php
/**
 * student/profile.php — Update profile information
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'My Profile';
$activePage = 'profile';

$error   = '';
$success = '';

// Fetch current data
$user = $conn->query("SELECT * FROM users WHERE user_id=$user_id")->fetch_assoc();

// ── Process update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name'] ?? '');
    $phone     = clean($_POST['phone']     ?? '');
    $email     = clean($_POST['email']     ?? '');

    if (empty($full_name)) {
        $error = "Full name is required.";
    } elseif (strlen($full_name) < 3) {
        $error = "Full name must be at least 3 characters.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check email not taken by another user
        $email_check = $conn->prepare("SELECT user_id FROM users WHERE email=? AND user_id != ?");
        $email_check->bind_param("si", $email, $user_id);
        $email_check->execute();

        if ($email_check->get_result()->num_rows > 0) {
            $error = "This email is already used by another account.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, updated_at=NOW() WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

            if ($stmt->execute()) {
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email']     = $email;

                // Log
                $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
                $log = $conn->prepare("INSERT INTO activity_logs (user_id,action,table_name,record_id,description,ip_address) VALUES (?,?,?,?,?,?)");
                $log->bind_param("ississ", $user_id, 'PROFILE_UPDATE', 'users', $user_id, 'Student updated profile', $ip);
                $log->execute();

                $success = "Profile updated successfully!";
                // Refresh data
                $user = $conn->query("SELECT * FROM users WHERE user_id=$user_id")->fetch_assoc();
            } else {
                $error = "Update failed. Please try again.";
            }
        }
    }
}

include 'includes/layout.php';
?>

<div class="page-header">
  <h1>My Profile</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="sep">›</span>Profile</div>
</div>

<?php if ($error):   ?><div class="alert-s alert-s-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-s alert-s-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">

  <!-- Avatar / Summary Card -->
  <div class="col-lg-3">
    <div class="panel text-center">
      <div class="panel-body">
        <div class="profile-avatar-box">
          <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <h4 style="font-size:1rem;margin-bottom:.2rem"><?= htmlspecialchars($user['full_name']) ?></h4>
        <p style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem"><?= htmlspecialchars($user['email']) ?></p>
        <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;font-weight:700;
                     text-transform:uppercase;letter-spacing:.06em;
                     background:var(--green-lt);color:var(--green);
                     padding:.28rem .75rem;border-radius:999px">
          <i class="fas fa-circle" style="font-size:.45rem"></i>
          <?= ucfirst($user['status'] ?? 'Active') ?>
        </span>
      </div>
      <div class="panel-footer">
        <div style="font-size:.78rem;color:var(--muted);text-align:left">
          <div style="margin-bottom:.4rem">
            <i class="fas fa-calendar-alt me-2" style="color:var(--primary)"></i>
            Joined <?= date('M Y', strtotime($user['created_at'])) ?>
          </div>
          <div style="margin-bottom:.4rem">
            <i class="fas fa-sign-in-alt me-2" style="color:var(--teal)"></i>
            Last login: <?= $user['last_login'] ? date('d M Y', strtotime($user['last_login'])) : 'N/A' ?>
          </div>
          <div>
            <i class="fas fa-user-tag me-2" style="color:var(--amber)"></i>
            Role: <?= ucfirst($user['role']) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick stats -->
    <?php
    $apps_count = $conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id")->fetch_assoc()['c'] ?? 0;
    $approved   = $conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id AND status='approved'")->fetch_assoc()['c'] ?? 0;
    $docs_count = $conn->query("SELECT COUNT(*) as c FROM application_documents d JOIN applications a ON a.application_id=d.application_id WHERE a.user_id=$user_id")->fetch_assoc()['c'] ?? 0;
    ?>
    <div class="panel mt-3">
      <div class="panel-header"><span class="panel-title">Quick Stats</span></div>
      <div class="panel-body" style="padding:1rem">
        <?php foreach ([
          [$apps_count, 'Total Applications', 'var(--primary)',  'fa-file-alt'],
          [$approved,   'Approved',           'var(--green)',    'fa-check-circle'],
          [$docs_count, 'Documents Uploaded', 'var(--amber)',    'fa-folder'],
        ] as [$num, $label, $color, $icon]): ?>
        <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid var(--border)">
          <i class="fas <?= $icon ?>" style="color:<?= $color ?>;width:16px;text-align:center"></i>
          <span style="flex:1;font-size:.83rem;color:var(--body)"><?= $label ?></span>
          <span style="font-weight:700;color:var(--ink);font-size:.9rem"><?= $num ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="col-lg-9">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-edit me-2" style="color:var(--primary)"></i>Edit Profile Information</span>
      </div>
      <div class="panel-body">
        <form method="POST" action="profile.php" novalidate>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-s">Full Name <span style="color:var(--red)">*</span></label>
              <input type="text" name="full_name" class="form-control-s"
                     value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-s">Email Address <span style="color:var(--red)">*</span></label>
              <input type="email" name="email" class="form-control-s"
                     value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-s">Phone Number</label>
              <input type="tel" name="phone" class="form-control-s"
                     placeholder="03XX-XXXXXXX"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label-s">Role</label>
              <input type="text" class="form-control-s" value="Student" disabled
                     style="background:var(--bg);cursor:not-allowed">
            </div>
            <div class="col-md-6">
              <label class="form-label-s">Account Status</label>
              <input type="text" class="form-control-s"
                     value="<?= ucfirst($user['status'] ?? 'Active') ?>" disabled
                     style="background:var(--bg);cursor:not-allowed">
            </div>
            <div class="col-md-6">
              <label class="form-label-s">Member Since</label>
              <input type="text" class="form-control-s"
                     value="<?= date('d M Y', strtotime($user['created_at'])) ?>" disabled
                     style="background:var(--bg);cursor:not-allowed">
            </div>
          </div>

          <hr style="border-color:var(--border);margin:1.75rem 0">

          <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <button type="submit" class="btn-s-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="change_password.php" class="btn-s-outline">
              <i class="fas fa-lock"></i> Change Password
            </a>
          </div>

        </form>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="panel mt-4" style="border-color:var(--red-lt)">
      <div class="panel-header" style="background:#fff5f5">
        <span class="panel-title" style="color:var(--red)"><i class="fas fa-exclamation-triangle me-2"></i>Account Information</span>
      </div>
      <div class="panel-body">
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:.75rem">
          To change your password or deactivate your account, use the options below.
          For any other account issues, please contact support.
        </p>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
          <a href="change_password.php" class="btn-s-outline" style="font-size:.85rem">
            <i class="fas fa-key"></i> Change Password
          </a>
          <a href="../contact.php" class="btn-s-outline" style="font-size:.85rem">
            <i class="fas fa-envelope"></i> Contact Support
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/layout_end.php'; ?>
