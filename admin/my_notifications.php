<?php
/**
 * admin/my_notifications.php — View notifications received by admin
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'Notifications';
$activePage = 'my_notifications';

// Mark all as read
if (isset($_GET['mark_all'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=TRUE WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_notifications.php?success=1");
    exit();
}

// Mark single as read
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read=TRUE WHERE notification_id=? AND user_id=?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_notifications.php");
    exit();
}

// Delete notification
if (isset($_GET['delete'])) {
    $nid = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id=? AND user_id=?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_notifications.php?deleted=1");
    exit();
}

$success = isset($_GET['success']);
$deleted = isset($_GET['deleted']);

// Fetch notifications
$notifications = $conn->query("
    SELECT * FROM notifications
    WHERE user_id=$user_id
    ORDER BY created_at DESC
");

$unread_total = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$user_id AND is_read=FALSE")->fetch_assoc()['c'] ?? 0;

include 'includes/layout.php';
?>

<div class="page-hd">
  <h1>Notifications</h1>
  <div class="breadcrumb">
    <i class="fas fa-home" style="color:var(--em)"></i>
    Admin Panel <span class="bc-sep">›</span> Notifications
  </div>
</div>

<?php if ($success): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i>All notifications marked as read.</div>
<?php endif; ?>
<?php if ($deleted): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i>Notification deleted.</div>
<?php endif; ?>

<!-- Controls -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
  <div style="font-size:.88rem;color:var(--muted)">
    <?php if ($unread_total > 0): ?>
      <span style="font-weight:600;color:var(--em)"><?= $unread_total ?> unread</span> notification<?= $unread_total > 1 ? 's' : '' ?>
    <?php else: ?>
      All caught up — no unread notifications
    <?php endif; ?>
  </div>
  <?php if ($unread_total > 0): ?>
  <a href="my_notifications.php?mark_all=1" class="btn-a btn-secondary btn-sm">
    <i class="fas fa-check-double"></i> Mark All as Read
  </a>
  <?php endif; ?>
</div>

<!-- Notifications list -->
<div class="panel">
  <?php if ($notifications && $notifications->num_rows > 0):

    $type_config = [
      'info'        => ['fa-info-circle',        'var(--blue-lt)',     'var(--blue)',    'Info'],
      'warning'     => ['fa-exclamation-triangle','var(--amber-lt)',    'var(--amber)',   'Warning'],
      'success'     => ['fa-check-circle',       'var(--green-lt)',    'var(--green)',   'Success'],
      'application' => ['fa-file-alt',           '#ccfbf1',            'var(--em)',      'Application'],
      'payment'     => ['fa-money-bill-wave',    'var(--purple-lt)',   'var(--purple)',  'Payment'],
    ];

    while ($n = $notifications->fetch_assoc()):
      [$icon, $bg, $col, $label] = $type_config[$n['type']] ?? ['fa-bell','var(--blue-lt)','var(--blue)','Info'];
  ?>
  <div style="display:flex;gap:.9rem;align-items:flex-start;
              padding:.9rem 1.4rem;border-bottom:1px solid var(--border);
              <?= !$n['is_read'] ? 'background:rgba(30,77,155,.04)' : '' ?>">
    
    <?php if (!$n['is_read']): ?>
    <div style="width:8px;height:8px;background:var(--em);border-radius:50%;margin-top:.35rem;flex-shrink:0"></div>
    <?php endif; ?>

    <div style="width:34px;height:34px;border-radius:9px;
                background:<?= $bg ?>;display:grid;place-items:center;
                font-size:.85rem;flex-shrink:0;margin-top:.05rem">
      <i class="fas <?= $icon ?>" style="color:<?= $col ?>"></i>
    </div>

    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
        <div style="flex:1">
          <div style="font-size:.83rem;color:var(--body);line-height:1.5">
            <?= htmlspecialchars($n['message']) ?>
          </div>
        </div>
        <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
                     padding:.15rem .5rem;border-radius:999px;background:<?= $bg ?>;color:<?= $col ?>;
                     white-space:nowrap;flex-shrink:0"><?= $label ?></span>
      </div>
      
      <div style="font-size:.72rem;color:var(--muted);margin-top:.35rem;display:flex;align-items:center;gap:.5rem">
        <i class="fas fa-clock"></i>
        <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
        <?php if (!$n['is_read']): ?>
          · <a href="my_notifications.php?read=<?= $n['notification_id'] ?>"
               style="color:var(--em);font-weight:600;text-decoration:none">Mark read</a>
        <?php endif; ?>
        · <a href="my_notifications.php?delete=<?= $n['notification_id'] ?>"
             style="color:var(--muted);font-weight:600;text-decoration:none"
             onclick="return confirm('Delete this notification?')">Delete</a>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
  <?php else: ?>
  <div style="text-align:center;padding:3rem 1.5rem">
    <div style="font-size:2.5rem;margin-bottom:.75rem">🔔</div>
    <h4 style="color:var(--ink);margin-bottom:.25rem">No notifications yet</h4>
    <p style="color:var(--muted);font-size:.88rem">You'll receive notifications about application updates, payments, and system events here.</p>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_end.php'; ?>
