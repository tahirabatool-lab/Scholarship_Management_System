<?php
/**
 * admin/messages.php — View Contact Messages
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Contact Messages';
$activePage = 'messages';

// Delete message
if (isset($_GET['delete'])) {
    $mid = (int)$_GET['delete'];
    $conn->query("DELETE FROM contact_messages WHERE message_id=$mid");
    header("Location: messages.php?success=Message+deleted.");
    exit();
}

// Delete all
if (isset($_GET['clear_all'])) {
    $conn->query("TRUNCATE TABLE contact_messages");
    header("Location: messages.php?success=All+messages+cleared.");
    exit();
}

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$total    = $messages ? $messages->num_rows : 0;

include 'includes/layout.php';
?>

<div class="page-hd">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <h1>Contact Messages</h1>
      <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Messages</div>
    </div>
    <?php if ($total > 0): ?>
    <a href="messages.php?clear_all=1"
       class="btn-a btn-danger btn-sm"
       data-confirm="Clear all contact messages? This cannot be undone.">
      <i class="fas fa-trash-alt"></i> Clear All
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= clean($_GET['success']) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-hd">
    <span class="panel-title"><i class="fas fa-envelope me-2" style="color:var(--em)"></i>Inbox</span>
    <span style="font-size:.78rem;color:var(--muted)"><?= $total ?> message<?= $total !== 1 ? 's' : '' ?></span>
  </div>
  <?php if ($messages && $messages->num_rows > 0):
    while ($msg = $messages->fetch_assoc()):
  ?>
  <div style="padding:1.2rem 1.4rem;border-bottom:1px solid var(--border)">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:.75rem">
        <div style="width:40px;height:40px;border-radius:50%;
                    background:linear-gradient(135deg,var(--em),#0d9488);
                    display:grid;place-items:center;
                    color:#fff;font-size:.9rem;font-weight:700;flex-shrink:0">
          <?= strtoupper(substr($msg['name']??'?',0,1)) ?>
        </div>
        <div>
          <div style="font-size:.88rem;font-weight:700;color:var(--ink)">
            <?= htmlspecialchars($msg['name']) ?>
          </div>
          <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"
             style="font-size:.75rem;color:var(--em)">
            <?= htmlspecialchars($msg['email']) ?>
          </a>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem">
        <span style="font-size:.73rem;color:var(--muted)">
          <i class="fas fa-clock me-1"></i>
          <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
        </span>
        <div style="display:flex;gap:.3rem">
          <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"
             class="btn-a btn-primary btn-sm" title="Reply by email">
            <i class="fas fa-reply"></i> Reply
          </a>
          <a href="messages.php?delete=<?= $msg['message_id'] ?>"
             class="btn-a btn-danger btn-sm"
             data-confirm="Delete this message?">
            <i class="fas fa-trash"></i>
          </a>
        </div>
      </div>
    </div>
    <div style="margin-top:.85rem;padding:.9rem 1rem;
                background:var(--bg);border-radius:var(--r);
                font-size:.85rem;color:var(--body);line-height:1.65;
                border-left:3px solid var(--em)">
      <?= nl2br(htmlspecialchars($msg['message'])) ?>
    </div>
  </div>
  <?php endwhile; ?>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">📭</span>
    <h5>No messages yet</h5>
    <p>Contact form submissions from the public website will appear here.</p>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_end.php'; ?>
