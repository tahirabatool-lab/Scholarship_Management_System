<?php
/**
 * admin/dashboard.php — Admin Dashboard
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ── Statistics ──
$total_users        = $conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;
$total_scholarships = $conn->query("SELECT COUNT(*) c FROM scholarships WHERE status='active'")->fetch_assoc()['c'] ?? 0;
$total_applications = $conn->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'] ?? 0;
$pending_apps       = $conn->query("SELECT COUNT(*) c FROM applications WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$approved_apps      = $conn->query("SELECT COUNT(*) c FROM applications WHERE status='approved'")->fetch_assoc()['c'] ?? 0;
$disbursed_amt      = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE payment_status='paid'")->fetch_assoc()['c'] ?? 0;
$total_messages     = $conn->query("SELECT COUNT(*) c FROM contact_messages")->fetch_assoc()['c'] ?? 0;
$new_users_week     = $conn->query("SELECT COUNT(*) c FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY) AND role='student'")->fetch_assoc()['c'] ?? 0;

// ── Recent Applications ──
$recent_apps = $conn->query("
    SELECT a.application_id, a.status, a.applied_at,
           u.full_name, u.email,
           s.title, s.amount
    FROM applications a
    JOIN users u ON u.user_id = a.user_id
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    ORDER BY a.applied_at DESC
    LIMIT 8
");

// ── Applications by status (for mini chart) ──
$status_counts = [];
$sc = $conn->query("SELECT status, COUNT(*) c FROM applications GROUP BY status");
while ($row = $sc->fetch_assoc()) {
    $status_counts[$row['status']] = (int)$row['c'];
}

// ── Recent Users ──
$recent_users = $conn->query("
    SELECT user_id, full_name, email, created_at, status
    FROM users WHERE role='student'
    ORDER BY created_at DESC LIMIT 5
");

include 'includes/layout.php';
?>

<!-- Page Header -->
<div class="page-hd">
  <h1>Dashboard</h1>
  <div class="breadcrumb">
    <i class="fas fa-home" style="color:var(--em)"></i>
    Admin Panel <span class="bc-sep">›</span> Dashboard
  </div>
</div>

<!-- ── Stat Cards Row ── -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="stat-card" style="border-left:3px solid var(--em)">
      <div class="stat-icon" style="background:var(--em-lt)">
        <i class="fas fa-users" style="color:var(--em)"></i>
      </div>
      <div>
        <div class="stat-num"><?= number_format($total_users) ?></div>
        <div class="stat-lbl">Registered Students</div>
        <div class="stat-trend trend-up">
          <i class="fas fa-arrow-up"></i> <?= $new_users_week ?> this week
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="stat-card" style="border-left:3px solid var(--blue)">
      <div class="stat-icon" style="background:var(--blue-lt)">
        <i class="fas fa-graduation-cap" style="color:var(--blue)"></i>
      </div>
      <div>
        <div class="stat-num"><?= number_format($total_scholarships) ?></div>
        <div class="stat-lbl">Active Scholarships</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="stat-card" style="border-left:3px solid var(--amber)">
      <div class="stat-icon" style="background:var(--amber-lt)">
        <i class="fas fa-file-alt" style="color:var(--amber)"></i>
      </div>
      <div>
        <div class="stat-num"><?= number_format($total_applications) ?></div>
        <div class="stat-lbl">Total Applications</div>
        <div class="stat-trend trend-dn">
          <i class="fas fa-clock" style="color:var(--amber)"></i>
          <?= $pending_apps ?> pending review
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="stat-card" style="border-left:3px solid var(--purple)">
      <div class="stat-icon" style="background:var(--purple-lt)">
        <i class="fas fa-wallet" style="color:var(--purple)"></i>
      </div>
      <div>
        <div class="stat-num" style="font-size:1.4rem">Rs.<?= number_format($disbursed_amt) ?></div>
        <div class="stat-lbl">Total Disbursed</div>
        <div class="stat-trend trend-up">
          <i class="fas fa-check-circle" style="color:var(--green)"></i>
          <?= $approved_apps ?> approved
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Second stat row ── -->
<div class="row g-3 mb-4">
  <?php
  $mini_stats = [
    ['pending',      $status_counts['pending']      ?? 0, 'Pending',     'var(--amber)',  'fa-clock'],
    ['under_review', $status_counts['under_review'] ?? 0, 'Under Review','var(--sky)',    'fa-search'],
    ['approved',     $status_counts['approved']     ?? 0, 'Approved',    'var(--green)',  'fa-check-circle'],
    ['rejected',     $status_counts['rejected']     ?? 0, 'Rejected',    'var(--red)',    'fa-times-circle'],
    ['disbursed',    $status_counts['disbursed']    ?? 0, 'Disbursed',   'var(--purple)', 'fa-money-bill-wave'],
    ['messages',     $total_messages,                     'Messages',    'var(--em)',     'fa-envelope'],
  ];
  foreach ($mini_stats as [$key, $num, $label, $color, $icon]):
  ?>
  <div class="col-xl-2 col-md-4 col-6">
    <a href="<?= $key === 'messages' ? 'messages.php' : 'applications.php?status='.$key ?>"
       class="stat-card flex-column text-center text-decoration-none" style="border-top:3px solid <?= $color ?>">
      <i class="fas <?= $icon ?>" style="font-size:1.5rem;color:<?= $color ?>;margin-bottom:.4rem"></i>
      <div class="stat-num" style="font-size:1.5rem"><?= $num ?></div>
      <div class="stat-lbl"><?= $label ?></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Main Content Grid ── -->
<div class="row g-4">

  <!-- Recent Applications -->
  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-history me-2" style="color:var(--em)"></i>Recent Applications</span>
        <a href="applications.php" class="btn-a btn-secondary btn-sm">View All →</a>
      </div>
      <?php if ($recent_apps && $recent_apps->num_rows > 0): ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Scholarship</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $n=1; while ($app = $recent_apps->fetch_assoc()): ?>
            <tr>
              <td class="td-muted"><?= $n++ ?></td>
              <td>
                <div class="td-primary"><?= htmlspecialchars($app['full_name']) ?></div>
                <div class="td-muted"><?= htmlspecialchars($app['email']) ?></div>
              </td>
              <td class="td-primary"><?= htmlspecialchars(mb_substr($app['title'],0,30)).'…' ?></td>
              <td style="font-weight:600;white-space:nowrap">
                <?= $app['amount'] ? 'Rs.'.number_format($app['amount']) : '—' ?>
              </td>
              <td class="td-muted"><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
              <td><span class="badge-s badge-<?= $app['status'] ?>"><?= ucfirst(str_replace('_',' ',$app['status'])) ?></span></td>
              <td>
                <a href="applications.php?detail=<?= $app['application_id'] ?>"
                   class="btn-a btn-info btn-sm">Review</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-tbl">
        <span class="ei">📋</span>
        <h5>No applications yet</h5>
        <p>Applications will appear here once students start applying.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column -->
  <div class="col-lg-4">

    <!-- Quick Actions -->
    <div class="panel mb-4">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-bolt me-2" style="color:var(--amber)"></i>Quick Actions</span>
      </div>
      <div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
        <?php
        $actions = [
          ['scholarships.php?action=add', 'Add Scholarship', 'fa-plus',          'var(--em-lt)',     'var(--em)'],
          ['applications.php',            'Applications',    'fa-file-alt',       'var(--amber-lt)',  'var(--amber)'],
          ['users.php',                   'Manage Users',    'fa-users',          'var(--blue-lt)',   'var(--blue)'],
          ['notifications.php',           'Notify Users',    'fa-bell',           'var(--purple-lt)', 'var(--purple)'],
          ['payments.php',                'Payments',        'fa-money-bill-wave','#d1fae5',          'var(--em)'],
          ['reports.php',                 'Reports',         'fa-chart-bar',      'var(--sky-lt)',    'var(--sky)'],
        ];
        foreach ($actions as [$href, $label, $icon, $bg, $col]):
        ?>
        <a href="<?= $href ?>" class="text-decoration-none">
          <div style="padding:.85rem;background:<?= $bg ?>;border-radius:var(--r);text-align:center;
                      transition:all var(--t);border:1.5px solid transparent"
               onmouseover="this.style.borderColor='<?= $col ?>'"
               onmouseout="this.style.borderColor='transparent'">
            <i class="fas <?= $icon ?>" style="color:<?= $col ?>;font-size:1.2rem;margin-bottom:.4rem;display:block"></i>
            <div style="font-size:.73rem;font-weight:700;color:var(--ink)"><?= $label ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-user-plus me-2" style="color:var(--blue)"></i>New Students</span>
        <a href="users.php" style="font-size:.78rem;color:var(--em)">See all →</a>
      </div>
      <?php if ($recent_users && $recent_users->num_rows > 0):
        while ($u = $recent_users->fetch_assoc()):
          $init = strtoupper(substr($u['full_name'],0,1));
      ?>
      <div style="display:flex;align-items:center;gap:.75rem;padding:.85rem 1.4rem;border-bottom:1px solid var(--border)">
        <div style="width:34px;height:34px;border-radius:50%;
                    background:linear-gradient(135deg,var(--em),#0d9488);
                    display:grid;place-items:center;color:#fff;
                    font-size:.8rem;font-weight:700;flex-shrink:0">
          <?= $init ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.84rem;font-weight:600;color:var(--ink);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($u['full_name']) ?>
          </div>
          <div style="font-size:.72rem;color:var(--muted)">
            <?= date('d M Y', strtotime($u['created_at'])) ?>
          </div>
        </div>
        <span class="badge-s badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span>
      </div>
      <?php endwhile; ?>
      <?php else: ?>
      <div class="empty-tbl" style="padding:2rem">
        <span class="ei" style="font-size:2rem">👤</span>
        <p>No students yet</p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include 'includes/layout_end.php'; ?>
