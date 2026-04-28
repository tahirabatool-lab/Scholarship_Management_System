<?php
/**
 * student/dashboard.php — Student Dashboard Home
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ── Summary counts ──
$total_applied = $conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id")->fetch_assoc()['c'] ?? 0;
$total_pending = $conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id AND status='pending'")->fetch_assoc()['c'] ?? 0;
$total_approved= $conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id AND status='approved'")->fetch_assoc()['c'] ?? 0;
$total_disbursed=$conn->query("SELECT COUNT(*) as c FROM applications WHERE user_id=$user_id AND status='disbursed'")->fetch_assoc()['c'] ?? 0;

// Total amount disbursed
$disbursed_amt = $conn->query("
    SELECT COALESCE(SUM(p.amount),0) as total
    FROM payments p
    JOIN applications a ON a.application_id = p.application_id
    WHERE a.user_id=$user_id AND p.payment_status='paid'
")->fetch_assoc()['total'] ?? 0;

// Recent 5 applications
$recent_apps = $conn->query("
    SELECT a.application_id, a.status, a.applied_at,
           s.title, s.provider, s.amount, s.level
    FROM applications a
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    WHERE a.user_id=$user_id
    ORDER BY a.applied_at DESC
    LIMIT 5
");

// Available scholarships (not yet applied)
$available = $conn->query("
    SELECT s.scholarship_id, s.title, s.provider, s.type, s.amount, s.level, s.deadline, s.total_seats
    FROM scholarships s
    WHERE s.status='active'
      AND s.scholarship_id NOT IN (
          SELECT scholarship_id FROM applications WHERE user_id=$user_id
      )
    ORDER BY s.deadline ASC
    LIMIT 3
");

// Latest notifications
$notifs = $conn->query("
    SELECT message, type, is_read, created_at
    FROM notifications
    WHERE user_id=$user_id
    ORDER BY created_at DESC
    LIMIT 4
");

include 'includes/layout.php';
?>

<!-- Page Header -->
<div class="page-header">
  <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h1>
  <p style="color:var(--muted);font-size:.88rem;margin-top:.25rem">
    <?= date('l, d F Y') ?> &nbsp;·&nbsp; Here's your scholarship overview
  </p>
</div>

<!-- ── Stat Cards ── -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <a href="applications.php" class="stat-card text-decoration-none">
      <div class="stat-icon" style="background:var(--primary-lt)">
        <i class="fas fa-file-alt" style="color:var(--primary)"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_applied ?></div>
        <div class="stat-label">Total Applied</div>
      </div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="applications.php?status=pending" class="stat-card text-decoration-none">
      <div class="stat-icon" style="background:var(--amber-lt)">
        <i class="fas fa-clock" style="color:var(--amber)"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_pending ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="applications.php?status=approved" class="stat-card text-decoration-none">
      <div class="stat-icon" style="background:var(--green-lt)">
        <i class="fas fa-check-circle" style="color:var(--green)"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_approved ?></div>
        <div class="stat-label">Approved</div>
      </div>
    </a>
  </div>
  <div class="col-xl-3 col-md-6">
    <a href="applications.php?status=disbursed" class="stat-card text-decoration-none">
      <div class="stat-icon" style="background:var(--purple-lt)">
        <i class="fas fa-wallet" style="color:var(--purple)"></i>
      </div>
      <div>
        <div class="stat-num">Rs.<?= number_format($disbursed_amt) ?></div>
        <div class="stat-label">Total Received</div>
      </div>
    </a>
  </div>
</div>

<!-- ── Main Grid ── -->
<div class="row g-4">

  <!-- Recent Applications -->
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-history me-2" style="color:var(--primary)"></i>Recent Applications</span>
        <a href="applications.php" style="font-size:.8rem;color:var(--primary)">View all →</a>
      </div>
      <?php if ($recent_apps && $recent_apps->num_rows > 0): ?>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Scholarship</th>
              <th>Level</th>
              <th>Applied</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($app = $recent_apps->fetch_assoc()): ?>
            <tr>
              <td>
                <div class="title-cell"><?= htmlspecialchars($app['title']) ?></div>
                <div class="muted-cell"><?= htmlspecialchars($app['provider'] ?? '') ?></div>
              </td>
              <td><span style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($app['level'] ?? '—') ?></span></td>
              <td class="muted-cell"><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
              <td>
                <span class="status-badge badge-<?= $app['status'] ?>">
                  <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                </span>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <span class="empty-icon">📋</span>
        <h4>No applications yet</h4>
        <p>Find a scholarship and apply today!</p>
        <a href="scholarships.php" class="btn-s-primary">Browse Scholarships</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Notifications + Quick Actions -->
  <div class="col-lg-5">

    <!-- Quick Actions -->
    <div class="panel mb-4">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-bolt me-2" style="color:var(--amber)"></i>Quick Actions</span>
      </div>
      <div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <a href="scholarships.php" style="text-decoration:none">
          <div style="padding:1rem;background:var(--primary-lt);border-radius:var(--radius);text-align:center;transition:all var(--transition);border:1.5px solid transparent"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='transparent'">
            <div style="font-size:1.4rem;margin-bottom:.4rem">🎓</div>
            <div style="font-size:.78rem;font-weight:600;color:var(--primary)">Apply Now</div>
          </div>
        </a>
        <a href="applications.php" style="text-decoration:none">
          <div style="padding:1rem;background:var(--teal-lt);border-radius:var(--radius);text-align:center;transition:all var(--transition);border:1.5px solid transparent"
               onmouseover="this.style.borderColor='var(--teal)'" onmouseout="this.style.borderColor='transparent'">
            <div style="font-size:1.4rem;margin-bottom:.4rem">📊</div>
            <div style="font-size:.78rem;font-weight:600;color:var(--teal)">Track Status</div>
          </div>
        </a>
        <a href="documents.php" style="text-decoration:none">
          <div style="padding:1rem;background:var(--amber-lt);border-radius:var(--radius);text-align:center;transition:all var(--transition);border:1.5px solid transparent"
               onmouseover="this.style.borderColor='var(--amber)'" onmouseout="this.style.borderColor='transparent'">
            <div style="font-size:1.4rem;margin-bottom:.4rem">📁</div>
            <div style="font-size:.78rem;font-weight:600;color:var(--amber)">Documents</div>
          </div>
        </a>
        <a href="profile.php" style="text-decoration:none">
          <div style="padding:1rem;background:var(--purple-lt);border-radius:var(--radius);text-align:center;transition:all var(--transition);border:1.5px solid transparent"
               onmouseover="this.style.borderColor='var(--purple)'" onmouseout="this.style.borderColor='transparent'">
            <div style="font-size:1.4rem;margin-bottom:.4rem">👤</div>
            <div style="font-size:.78rem;font-weight:600;color:var(--purple)">Profile</div>
          </div>
        </a>
      </div>
    </div>

    <!-- Notifications -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-bell me-2" style="color:var(--red)"></i>Notifications</span>
        <a href="notifications.php" style="font-size:.8rem;color:var(--primary)">See all →</a>
      </div>
      <?php if ($notifs && $notifs->num_rows > 0):
        $type_icons = ['info'=>'fa-info-circle','warning'=>'fa-exclamation-triangle','success'=>'fa-check-circle','application'=>'fa-file-alt','payment'=>'fa-money-bill-wave'];
        $type_colors= ['info'=>'var(--primary-lt)','warning'=>'var(--amber-lt)','success'=>'var(--green-lt)','application'=>'var(--teal-lt)','payment'=>'var(--purple-lt)'];
        $icon_colors= ['info'=>'var(--primary)','warning'=>'var(--amber)','success'=>'var(--green)','application'=>'var(--teal)','payment'=>'var(--purple)'];
        while ($n = $notifs->fetch_assoc()):
          $ic = $type_icons[$n['type']] ?? 'fa-bell';
          $bg = $type_colors[$n['type']] ?? 'var(--primary-lt)';
          $cl = $icon_colors[$n['type']] ?? 'var(--primary)';
      ?>
      <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
        <?php if (!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
        <div class="notif-icon" style="background:<?= $bg ?>">
          <i class="fas <?= $ic ?>" style="color:<?= $cl ?>"></i>
        </div>
        <div>
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notif-time"><i class="fas fa-clock"></i><?= date('d M, h:i A', strtotime($n['created_at'])) ?></div>
        </div>
      </div>
      <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:2rem">
          <span class="empty-icon" style="font-size:2rem">🔔</span>
          <p style="margin:0">No notifications yet</p>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div><!-- /row -->

<!-- ── Available Scholarships Teaser ── -->
<?php if ($available && $available->num_rows > 0): ?>
<div class="mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 style="font-size:1.15rem;font-weight:700">
      <i class="fas fa-star me-2" style="color:var(--amber)"></i>Open Scholarships for You
    </h2>
    <a href="scholarships.php" style="font-size:.82rem;color:var(--primary)">Browse all →</a>
  </div>
  <div class="row g-3">
    <?php while ($s = $available->fetch_assoc()):
      $days_left = $s['deadline'] ? ceil((strtotime($s['deadline']) - time()) / 86400) : null;
      $urgent = $days_left !== null && $days_left <= 7;
      $type_class = ['merit'=>'type-merit','need-based'=>'type-need','talent-based'=>'type-talent'][strtolower($s['type'] ?? '')] ?? 'type-merit';
    ?>
    <div class="col-lg-4 col-md-6">
      <div class="schol-card">
        <div class="schol-card-top">
          <span class="schol-type-badge <?= $type_class ?>"><?= htmlspecialchars($s['type'] ?? 'Merit') ?></span>
          <div class="schol-amount">
            <?= $s['amount'] ? 'Rs. '.number_format($s['amount']) : 'Varies' ?>
            <small><?= htmlspecialchars($s['level'] ?? '') ?></small>
          </div>
        </div>
        <div class="schol-card-body">
          <div class="schol-title"><?= htmlspecialchars($s['title']) ?></div>
          <div class="schol-provider"><i class="fas fa-building"></i><?= htmlspecialchars($s['provider'] ?? '') ?></div>
          <?php if ($days_left !== null): ?>
          <div class="schol-deadline <?= $urgent ? 'urgent' : '' ?>">
            <i class="fas fa-clock"></i>
            <?= $urgent ? "Only $days_left days left!" : 'Deadline: '.date('d M Y', strtotime($s['deadline'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="schol-card-footer">
          <a href="apply.php?id=<?= $s['scholarship_id'] ?>" class="btn-apply">
            <i class="fas fa-paper-plane"></i> Apply
          </a>
          <a href="scholarships.php?view=<?= $s['scholarship_id'] ?>" class="btn-view-detail">Details</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
<?php endif; ?>

<?php include 'includes/layout_end.php'; ?>
