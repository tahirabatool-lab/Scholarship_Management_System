<?php
/**
 * student/change_password.php — Change account password
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'Change Password';
$activePage = 'password';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pw  = $_POST['current_password']  ?? '';
    $new_pw      = $_POST['new_password']      ?? '';
    $confirm_pw  = $_POST['confirm_password']  ?? '';

    // Fetch stored hash
    $user = $conn->query("SELECT password FROM users WHERE user_id=$user_id")->fetch_assoc();

    if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
        $error = "All three fields are required.";
    } elseif (!password_verify($current_pw, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pw) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $new_pw)) {
        $error = "New password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $new_pw)) {
        $error = "New password must contain at least one number.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "New passwords do not match.";
    } elseif (password_verify($new_pw, $user['password'])) {
        $error = "New password must be different from your current password.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $stmt   = $conn->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE user_id=?");
        $stmt->bind_param("si", $hashed, $user_id);

        if ($stmt->execute()) {
            // Log activity
            $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
            $log = $conn->prepare("INSERT INTO activity_logs (user_id,action,table_name,record_id,description,ip_address) VALUES (?,?,?,?,?,?)");
            $log->bind_param("ississ", $user_id, 'PASSWORD_CHANGE', 'users', $user_id, 'Password changed by student', $ip);
            $log->execute();

            // Create notification
            $nstmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, 'Your account password was changed successfully. If this wasn\\'t you, contact support immediately.', 'warning')");
            $nstmt->bind_param("i", $user_id);
            $nstmt->execute();

            $success = "Password changed successfully! Please use your new password on next login.";
        } else {
            $error = "Password change failed. Please try again.";
        }
    }
}

include 'includes/layout.php';
?>

<div class="page-header">
  <h1>Change Password</h1>
  <div class="breadcrumb">
    <a href="dashboard.php">Dashboard</a><span class="sep">›</span>
    <a href="profile.php">Profile</a><span class="sep">›</span>Change Password
  </div>
</div>

<?php if ($error):   ?><div class="alert-s alert-s-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-s alert-s-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">

    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-lock me-2" style="color:var(--primary)"></i>Update Your Password</span>
      </div>
      <div class="panel-body">

        <!-- Security tips -->
        <div class="alert-s alert-s-info" style="margin-bottom:1.5rem">
          <i class="fas fa-shield-alt"></i>
          <div>
            <strong>Password Requirements:</strong>
            <ul style="margin:.4rem 0 0;padding-left:1.2rem;font-size:.82rem">
              <li>At least 8 characters long</li>
              <li>One uppercase letter (A–Z)</li>
              <li>One number (0–9)</li>
              <li>Must differ from current password</li>
            </ul>
          </div>
        </div>

        <form method="POST" action="change_password.php" novalidate>

          <!-- Current Password -->
          <div style="margin-bottom:1.2rem">
            <label class="form-label-s">Current Password <span style="color:var(--red)">*</span></label>
            <div style="position:relative">
              <input type="password" name="current_password" id="currentPw"
                     class="form-control-s" style="padding-right:2.8rem"
                     placeholder="Enter current password" required>
              <i class="fas fa-eye" id="toggleCurrent"
                 style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:var(--muted);font-size:.9rem;transition:color .2s"
                 onclick="togglePw('currentPw','toggleCurrent')"></i>
            </div>
            <div style="margin-top:.5rem">
              <a href="../forgot_password.php" style="font-size:.75rem;color:var(--primary)">
                <i class="fas fa-question-circle"></i> Forgot current password?
              </a>
            </div>
          </div>

          <hr style="border-color:var(--border);margin:1.2rem 0">

          <!-- New Password -->
          <div style="margin-bottom:1.2rem">
            <label class="form-label-s">New Password <span style="color:var(--red)">*</span></label>
            <div style="position:relative">
              <input type="password" name="new_password" id="newPw"
                     class="form-control-s" style="padding-right:2.8rem"
                     placeholder="Enter new password" required oninput="checkStrength(this.value)">
              <i class="fas fa-eye" id="toggleNew"
                 style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:var(--muted);font-size:.9rem"
                 onclick="togglePw('newPw','toggleNew')"></i>
            </div>
            <!-- Strength bars -->
            <div style="display:flex;gap:4px;margin-top:.55rem">
              <div class="strength-bar" id="bar1" style="height:3px;flex:1;border-radius:999px;background:var(--border);transition:background .3s"></div>
              <div class="strength-bar" id="bar2" style="height:3px;flex:1;border-radius:999px;background:var(--border);transition:background .3s"></div>
              <div class="strength-bar" id="bar3" style="height:3px;flex:1;border-radius:999px;background:var(--border);transition:background .3s"></div>
              <div class="strength-bar" id="bar4" style="height:3px;flex:1;border-radius:999px;background:var(--border);transition:background .3s"></div>
            </div>
            <div id="strengthLabel" style="font-size:.72rem;color:var(--muted);margin-top:.3rem"></div>
          </div>

          <!-- Confirm -->
          <div style="margin-bottom:1.5rem">
            <label class="form-label-s">Confirm New Password <span style="color:var(--red)">*</span></label>
            <div style="position:relative">
              <input type="password" name="confirm_password" id="confirmPw"
                     class="form-control-s" style="padding-right:2.8rem"
                     placeholder="Re-enter new password" required oninput="checkMatch()">
              <i class="fas fa-eye" id="toggleConfirm"
                 style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
                        cursor:pointer;color:var(--muted);font-size:.9rem"
                 onclick="togglePw('confirmPw','toggleConfirm')"></i>
            </div>
            <div id="matchMsg" style="font-size:.75rem;margin-top:.3rem"></div>
          </div>

          <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <button type="submit" class="btn-s-primary">
              <i class="fas fa-key"></i> Update Password
            </button>
            <a href="profile.php" class="btn-s-outline">
              <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
          </div>
        </form>

      </div>
    </div>

    <!-- Recent activity note -->
    <div class="panel mt-3">
      <div class="panel-body" style="padding:1rem">
        <p style="font-size:.82rem;color:var(--muted);margin:0">
          <i class="fas fa-info-circle me-2" style="color:var(--primary)"></i>
          After changing your password, you'll remain logged in. All other active sessions will be invalidated.
          If you did not request this change, contact support immediately.
        </p>
      </div>
    </div>

  </div>
</div>

<script>
function togglePw(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
}

function checkStrength(val) {
  let score = 0;
  if (val.length >= 8)             score++;
  if (/[A-Z]/.test(val))           score++;
  if (/[0-9]/.test(val))           score++;
  if (/[^A-Za-z0-9]/.test(val))   score++;

  const colors  = ['','#ef4444','#f59e0b','#f59e0b','#16a34a'];
  const labels  = ['','Weak','Fair','Good','Strong'];
  const bars    = ['bar1','bar2','bar3','bar4'];

  bars.forEach((id, i) => {
    const bar = document.getElementById(id);
    bar.style.background = i < score ? colors[score] : 'var(--border)';
  });

  const lbl = document.getElementById('strengthLabel');
  lbl.textContent = val.length ? 'Strength: ' + (labels[score] || '') : '';
  lbl.style.color = colors[score] || 'var(--muted)';
}

function checkMatch() {
  const nw = document.getElementById('newPw').value;
  const cf = document.getElementById('confirmPw').value;
  const el = document.getElementById('matchMsg');
  if (!cf) { el.textContent = ''; return; }
  if (nw === cf) {
    el.textContent = '✓ Passwords match';
    el.style.color = 'var(--green)';
  } else {
    el.textContent = '✗ Passwords do not match';
    el.style.color = 'var(--red)';
  }
}
</script>

<?php include 'includes/layout_end.php'; ?>
