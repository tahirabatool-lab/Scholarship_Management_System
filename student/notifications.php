<?php
/**
 * student/notifications.php — View & manage notifications
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'Notifications';
$activePage = 'notifications';

// Mark all as read
if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read=TRUE WHERE user_id=$user_id");
    header("Location: notifications.php?success=1");
    exit();
}

// Mark single as read
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $conn->prepare("UPDATE notifications SET is_read=TRUE WHERE notification_id=? AND user_id=?")->execute();
    $stmt = $conn->prepare("UPDATE notifications SET is_read=TRUE WHERE notification_id=? AND user_id=?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

$success = isset($_GET['success']);

// Fetch notifications
$notifications = $conn->query("
    SELECT * FROM notifications
    WHERE user_id=$user_id
    ORDER BY created_at DESC
");

$unread_total = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$user_id AND is_read=FALSE")->fetch_assoc()['c'] ?? 0;

include 'includes/layout.php';
?>

<div class="page-header">
  <h1>Notifications</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="sep">›</span>Notifications</div>
</div>

<?php if ($success): ?>
<div class="alert-s alert-s-success"><i class="fas fa-check-circle"></i>All notifications marked as read.</div>
<?php endif; ?>

<!-- Controls -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
  <div style="font-size:.88rem;color:var(--muted)">
    <?php if ($unread_total > 0): ?>
      <span style="font-weight:600;color:var(--primary)"><?= $unread_total ?> unread</span> notification<?= $unread_total > 1 ? 's' : '' ?>
    <?php else: ?>
      All caught up — no unread notifications
    <?php endif; ?>
  </div>
  <?php if ($unread_total > 0): ?>
  <a href="notifications.php?mark_all=1" class="btn-s-outline" style="padding:.45rem 1rem;font-size:.82rem">
    <i class="fas fa-check-double"></i> Mark All as Read
  </a>
  <?php endif; ?>
</div>

<!-- Notifications list -->
<div class="panel">
  <?php if ($notifications && $notifications->num_rows > 0):

    $type_config = [
      'info'        => ['fa-info-circle',       'var(--primary-lt)',  'var(--primary)', 'Info'],
      'warning'     => ['fa-exclamation-triangle','var(--amber-lt)',   'var(--amber)',   'Warning'],
      'success'     => ['fa-check-circle',       'var(--green-lt)',   'var(--green)',   'Success'],
      'application' => ['fa-file-alt',           'var(--teal-lt)',    'var(--teal)',    'Application'],
      'payment'     => ['fa-money-bill-wave',     'var(--purple-lt)', 'var(--purple)', 'Payment'],
    ];

    while ($n = $notifications->fetch_assoc()):
      [$icon, $bg, $col, $label] = $type_config[$n['type']] ?? ['fa-bell','var(--primary-lt)','var(--primary)','Info'];
  ?>
  <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
    <?php if (!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>

    <div class="notif-icon" style="background:<?= $bg ?>">
      <i class="fas <?= $icon ?>" style="color:<?= $col ?>"></i>
    </div>

    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
        <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
        <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;
                     letter-spacing:.04em;padding:.18rem .55rem;border-radius:999px;
                     background:<?= $bg ?>;color:<?= $col ?>;white-space:nowrap;flex-shrink:0">
          <?= $label ?>
        </span>
      </div>
      <div class="notif-time">
        <i class="fas fa-clock"></i>
        <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
        <?php if (!$n['is_read']): ?>
          · <a href="notifications.php?read=<?= $n['notification_id'] ?>"
               style="color:var(--primary);font-size:.72rem">Mark read</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
  <?php else: ?>
  <div class="empty-state">
    <span class="empty-icon">🔔</span>
    <h4>No notifications yet</h4>
    <p>We'll notify you about application updates, approvals, and payments here.</p>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_end.php'; ?>
