<?php
/**
 * admin/notifications.php — Send Notifications to Users
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Send Notifications';
$activePage = 'notifications';

$error = $success = '';

// ── Send notification ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target  = clean($_POST['target']  ?? '');  // 'all' | 'single'
    $user_id = (int)($_POST['user_id'] ?? 0);
    $message = clean($_POST['message'] ?? '');
    $type    = clean($_POST['type']    ?? 'info');
    $valid_types = ['info','warning','success','application','payment'];

    if (empty($message)) {
        $error = "Message cannot be empty.";
    } elseif (!in_array($type, $valid_types)) {
        $error = "Invalid notification type.";
    } elseif ($target === 'single' && !$user_id) {
        $error = "Please select a user to notify.";
    } else {
        $sent = 0;
        if ($target === 'all') {
            $all_users = $conn->query("SELECT user_id FROM users WHERE role='student' AND status='active'");
            $stmt = $conn->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)");
            while ($u = $all_users->fetch_assoc()) {
                $uid = $u['user_id'];
                $stmt->bind_param("iss", $uid, $message, $type);
                $stmt->execute();
                $sent++;
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)");
            $stmt->bind_param("iss", $user_id, $message, $type);
            if ($stmt->execute()) $sent = 1;
        }

        if ($sent > 0) {
            $success = "Notification sent to $sent student".($sent > 1 ? 's' : '').".";
        } else {
            $error = "No notifications sent. Check your selection.";
        }
    }
}

// Students for dropdown
$students = $conn->query("SELECT user_id,full_name,email FROM users WHERE role='student' AND status='active' ORDER BY full_name");

// Recent notifications (last 30)
$recent_notifs = $conn->query("
    SELECT n.*, u.full_name, u.email
    FROM notifications n
    JOIN users u ON u.user_id=n.user_id
    ORDER BY n.created_at DESC
    LIMIT 30
");

include 'includes/layout.php';
?>

<div class="page-hd">
  <h1>Send Notifications</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Notifications</div>
</div>

<?php if ($error):   ?><div class="alert-a alert-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">

  <!-- Compose Form -->
  <div class="col-lg-4">
    <div class="panel" style="position:sticky;top:76px">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-bell me-2" style="color:var(--amber)"></i>Compose Notification</span>
      </div>
      <div class="panel-body">
        <form method="POST" action="notifications.php" novalidate>

          <div style="margin-bottom:1rem">
            <label class="form-lbl">Send To <span style="color:var(--red)">*</span></label>
            <select name="target" id="targetSelect" class="form-sel"
                    onchange="document.getElementById('singleRow').style.display = this.value==='single' ? 'block' : 'none'">
              <option value="all">All Active Students</option>
              <option value="single">Specific Student</option>
            </select>
          </div>

          <div id="singleRow" style="display:none;margin-bottom:1rem">
            <label class="form-lbl">Select Student</label>
            <select name="user_id" class="form-sel">
              <option value="">— Choose student —</option>
              <?php
              if ($students && $students->num_rows > 0) {
                  $students->data_seek(0);
                  while ($s = $students->fetch_assoc()):
              ?>
              <option value="<?= $s['user_id'] ?>">
                <?= htmlspecialchars($s['full_name']) ?> — <?= htmlspecialchars($s['email']) ?>
              </option>
              <?php endwhile; } ?>
            </select>
          </div>

          <div style="margin-bottom:1rem">
            <label class="form-lbl">Type</label>
            <select name="type" class="form-sel">
              <?php
              $types = [
                'info'        => '💬 Info',
                'success'     => '✅ Success',
                'warning'     => '⚠️ Warning',
                'application' => '📋 Application Update',
                'payment'     => '💰 Payment',
              ];
              foreach ($types as $val => $lbl): ?>
              <option value="<?= $val ?>"><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="margin-bottom:1.2rem">
            <label class="form-lbl">Message <span style="color:var(--red)">*</span></label>
            <textarea name="message" class="form-ctrl" rows="5"
                      placeholder="Write your notification message here…"
                      required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
          </div>

          <button type="submit" class="btn-a btn-primary" style="width:100%;justify-content:center">
            <i class="fas fa-paper-plane"></i> Send Notification
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Recent Notifications -->
  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-history me-2" style="color:var(--em)"></i>Recently Sent (last 30)</span>
      </div>
      <?php
        $type_config = [
          'info'        => ['fa-info-circle',       'var(--blue-lt)',    'var(--blue)',   'Info'],
          'warning'     => ['fa-exclamation-triangle','var(--amber-lt)', 'var(--amber)',  'Warning'],
          'success'     => ['fa-check-circle',       'var(--green-lt)', 'var(--green)', 'Success'],
          'application' => ['fa-file-alt',           '#ccfbf1',          'var(--em)',    'Application'],
          'payment'     => ['fa-money-bill-wave',     'var(--purple-lt)','var(--purple)','Payment'],
        ];
      ?>
      <?php if ($recent_notifs && $recent_notifs->num_rows > 0):
        while ($n = $recent_notifs->fetch_assoc()):
          [$icon,$bg,$col,$lbl] = $type_config[$n['type']] ?? ['fa-bell','var(--blue-lt)','var(--blue)','Info'];
      ?>
      <div style="display:flex;gap:.9rem;align-items:flex-start;
                  padding:.9rem 1.4rem;border-bottom:1px solid var(--border)">
        <div style="width:34px;height:34px;border-radius:9px;
                    background:<?= $bg ?>;display:grid;place-items:center;
                    font-size:.85rem;flex-shrink:0">
          <i class="fas <?= $icon ?>" style="color:<?= $col ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
            <div>
              <span style="font-size:.82rem;font-weight:600;color:var(--ink)">
                <?= htmlspecialchars($n['full_name']) ?>
              </span>
              <span style="font-size:.72rem;color:var(--muted);margin-left:.5rem">
                <?= htmlspecialchars($n['email']) ?>
              </span>
            </div>
            <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
                         padding:.15rem .5rem;border-radius:999px;background:<?= $bg ?>;color:<?= $col ?>;
                         white-space:nowrap;flex-shrink:0"><?= $lbl ?></span>
          </div>
          <div style="font-size:.83rem;color:var(--body);margin-top:.2rem;line-height:1.5">
            <?= htmlspecialchars($n['message']) ?>
          </div>
          <div style="font-size:.72rem;color:var(--muted);margin-top:.25rem;display:flex;align-items:center;gap:.35rem">
            <i class="fas fa-clock"></i>
            <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
            <?php if (!$n['is_read']): ?>
              · <span style="color:var(--em);font-weight:600">Unread</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
      <?php else: ?>
      <div class="empty-tbl">
        <span class="ei">🔔</span>
        <h5>No notifications sent yet</h5>
        <p>Use the form to send your first notification.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include 'includes/layout_end.php'; ?>
