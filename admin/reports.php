<?php
/**
 * admin/reports.php — Reports & Analytics
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Reports';
$activePage = 'reports';

// ── Date filter ──
$date_from = clean($_GET['from'] ?? date('Y-m-01'));       // Default: first day of current month
$date_to   = clean($_GET['to']   ?? date('Y-m-d'));        // Default: today
$schol_filter = (int)($_GET['scholarship_id'] ?? 0);

// Validate dates
if (!strtotime($date_from)) $date_from = date('Y-m-01');
if (!strtotime($date_to))   $date_to   = date('Y-m-d');
if ($date_from > $date_to) [$date_from, $date_to] = [$date_to, $date_from];

$where_date = "DATE(a.applied_at) BETWEEN '$date_from' AND '$date_to'";
$schol_cond = $schol_filter ? "AND a.scholarship_id=$schol_filter" : '';

// ── Summary for date range ──
$apps_in_range   = $conn->query("SELECT COUNT(*) c FROM applications a WHERE $where_date $schol_cond")->fetch_assoc()['c'] ?? 0;
$approved_range  = $conn->query("SELECT COUNT(*) c FROM applications a WHERE $where_date $schol_cond AND a.status='approved'")->fetch_assoc()['c'] ?? 0;
$rejected_range  = $conn->query("SELECT COUNT(*) c FROM applications a WHERE $where_date $schol_cond AND a.status='rejected'")->fetch_assoc()['c'] ?? 0;
$disbursed_range = $conn->query("
    SELECT COALESCE(SUM(p.amount),0) c FROM payments p
    JOIN applications a ON a.application_id=p.application_id
    WHERE $where_date $schol_cond AND p.payment_status='paid'
")->fetch_assoc()['c'] ?? 0;

// ── Applications by status (all time) ──
$by_status = $conn->query("SELECT status, COUNT(*) c FROM applications GROUP BY status ORDER BY c DESC");

// ── Applications by scholarship ──
$by_schol = $conn->query("
    SELECT s.title, s.level, s.type, COUNT(a.application_id) app_count,
           SUM(CASE WHEN a.status='approved' THEN 1 ELSE 0 END) approved_count
    FROM scholarships s
    LEFT JOIN applications a ON a.scholarship_id=s.scholarship_id
    GROUP BY s.scholarship_id
    ORDER BY app_count DESC
    LIMIT 10
");

// ── Daily applications (for the selected range, grouped by date) ──
$daily = $conn->query("
    SELECT DATE(a.applied_at) AS day, COUNT(*) c
    FROM applications a
    WHERE $where_date $schol_cond
    GROUP BY DATE(a.applied_at)
    ORDER BY day
");

// ── New users in range ──
$new_users_range = $conn->query("
    SELECT COUNT(*) c FROM users
    WHERE role='student' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc()['c'] ?? 0;

// Scholarship list for filter dropdown
$schol_list = $conn->query("SELECT scholarship_id, title FROM scholarships ORDER BY title");

include 'includes/layout.php';
?>

<div class="page-hd">
  <h1>Reports</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Reports</div>
</div>

<!-- Date Filter -->
<div class="panel mb-4">
  <div class="panel-hd">
    <span class="panel-title"><i class="fas fa-filter me-2" style="color:var(--em)"></i>Filter Report</span>
  </div>
  <div class="panel-body">
    <form method="GET" action="reports.php">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-lbl">Date From</label>
          <input type="date" name="from" class="form-ctrl" value="<?= $date_from ?>">
        </div>
        <div class="col-md-3">
          <label class="form-lbl">Date To</label>
          <input type="date" name="to" class="form-ctrl" value="<?= $date_to ?>">
        </div>
        <div class="col-md-4">
          <label class="form-lbl">Scholarship (optional)</label>
          <select name="scholarship_id" class="form-sel">
            <option value="0">All Scholarships</option>
            <?php if ($schol_list): while ($sl = $schol_list->fetch_assoc()): ?>
            <option value="<?= $sl['scholarship_id'] ?>" <?= $schol_filter === (int)$sl['scholarship_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars(mb_substr($sl['title'],0,45)) ?>…
            </option>
            <?php endwhile; endif; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn-a btn-primary" style="width:100%;justify-content:center">
            <i class="fas fa-chart-bar"></i> Generate
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Range Summary -->
<div style="margin-bottom:.75rem;font-size:.82rem;color:var(--muted);font-style:italic">
  Showing results from <strong><?= date('d M Y',strtotime($date_from)) ?></strong>
  to <strong><?= date('d M Y',strtotime($date_to)) ?></strong>
</div>

<div class="row g-3 mb-4">
  <?php
  $range_stats = [
    [$apps_in_range,   'Applications',    'fa-file-alt',       'var(--em)',     'var(--em-lt)'],
    [$approved_range,  'Approved',        'fa-check-circle',   'var(--green)',  'var(--green-lt)'],
    [$rejected_range,  'Rejected',        'fa-times-circle',   'var(--red)',    'var(--red-lt)'],
    [$new_users_range, 'New Students',    'fa-user-plus',      'var(--blue)',   'var(--blue-lt)'],
    ['Rs.'.number_format($disbursed_range),'Disbursed','fa-wallet','var(--purple)','var(--purple-lt)'],
  ];
  foreach ($range_stats as [$num,$label,$icon,$col,$bg]):
  ?>
  <div class="col-xl col-md-4 col-6">
    <div class="stat-card" style="border-top:3px solid <?= $col ?>">
      <div class="stat-icon" style="background:<?= $bg ?>">
        <i class="fas <?= $icon ?>" style="color:<?= $col ?>"></i>
      </div>
      <div>
        <div class="stat-num" style="font-size:1.5rem"><?= $num ?></div>
        <div class="stat-lbl"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">

  <!-- Daily breakdown table -->
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title"><i class="fas fa-calendar me-2" style="color:var(--em)"></i>Daily Applications</span>
      </div>
      <?php if ($daily && $daily->num_rows > 0): ?>
      <div class="table-wrap" style="max-height:380px;overflow-y:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Applications</th>
              <th>Bar</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $max = 1;
            $daily_data = [];
            while ($d = $daily->fetch_assoc()) {
                $daily_data[] = $d;
                if ($d['c'] > $max) $max = $d['c'];
            }
            foreach ($daily_data as $d):
              $pct = round(($d['c'] / $max) * 100);
            ?>
            <tr>
              <td class="td-muted"><?= date('d M Y',strtotime($d['day'])) ?></td>
              <td style="font-weight:700;color:var(--ink)"><?= $d['c'] ?></td>
              <td style="width:120px">
                <div style="background:var(--border);border-radius:999px;height:6px;overflow:hidden">
                  <div style="background:var(--em);height:100%;width:<?= $pct ?>%;border-radius:999px"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-tbl" style="padding:2.5rem">
        <span class="ei" style="font-size:2rem">📅</span>
        <h5>No applications in this date range</h5>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Applications by Status (all time) -->
  <div class="col-lg-3">
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title">By Status (All Time)</span>
      </div>
      <div class="panel-body">
        <?php
        $total_for_pct = (int)$conn->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'];
        if ($by_status): while ($bs = $by_status->fetch_assoc()):
          $pct = $total_for_pct > 0 ? round(($bs['c'] / $total_for_pct) * 100) : 0;
          $colors = [
            'pending'=>'var(--amber)','under_review'=>'var(--sky)',
            'approved'=>'var(--green)','rejected'=>'var(--red)','disbursed'=>'var(--purple)'
          ];
          $col = $colors[$bs['status']] ?? 'var(--muted)';
        ?>
        <div style="margin-bottom:1rem">
          <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
            <span style="font-size:.8rem;font-weight:600;color:var(--ink)">
              <?= ucfirst(str_replace('_',' ',$bs['status'])) ?>
            </span>
            <span style="font-size:.78rem;color:var(--muted)"><?= $bs['c'] ?> (<?= $pct ?>%)</span>
          </div>
          <div style="background:var(--border);border-radius:999px;height:7px;overflow:hidden">
            <div style="background:<?= $col ?>;height:100%;width:<?= $pct ?>%;border-radius:999px;transition:width .5s"></div>
          </div>
        </div>
        <?php endwhile; endif; ?>
      </div>
    </div>
  </div>

  <!-- Top scholarships by applications -->
  <div class="col-lg-3">
    <div class="panel">
      <div class="panel-hd">
        <span class="panel-title">Top Scholarships</span>
      </div>
      <div class="panel-body" style="padding:0">
        <?php if ($by_schol): $i=1; while ($bs = $by_schol->fetch_assoc()): ?>
        <div style="display:flex;align-items:center;gap:.75rem;
                    padding:.75rem 1.2rem;border-bottom:1px solid var(--border)">
          <div style="width:24px;height:24px;border-radius:50%;
                      background:var(--em-lt);color:var(--em);
                      display:grid;place-items:center;
                      font-size:.7rem;font-weight:800;flex-shrink:0"><?= $i++ ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.8rem;font-weight:600;color:var(--ink);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($bs['title']) ?>
            </div>
            <div style="font-size:.7rem;color:var(--muted)">
              <?= $bs['app_count'] ?> applied · <?= $bs['approved_count'] ?> approved
            </div>
          </div>
        </div>
        <?php endwhile; endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Detailed applications table for range -->
<div class="panel mt-4">
  <div class="panel-hd">
    <span class="panel-title">
      <i class="fas fa-table me-2" style="color:var(--em)"></i>
      Applications (<?= date('d M',strtotime($date_from)) ?> – <?= date('d M Y',strtotime($date_to)) ?>)
    </span>
    <span style="font-size:.78rem;color:var(--muted)"><?= $apps_in_range ?> total</span>
  </div>
  <?php
  $report_apps = $conn->query("
      SELECT a.application_id, a.status, a.applied_at,
             u.full_name, u.email,
             s.title, s.amount, s.level,
             p.payment_status, p.amount AS paid_amount
      FROM applications a
      JOIN users u ON u.user_id=a.user_id
      JOIN scholarships s ON s.scholarship_id=a.scholarship_id
      LEFT JOIN payments p ON p.application_id=a.application_id
      WHERE $where_date $schol_cond
      ORDER BY a.applied_at DESC
  ");
  ?>
  <?php if ($report_apps && $report_apps->num_rows > 0): ?>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Scholarship</th>
          <th>Amount</th>
          <th>Applied</th>
          <th>Status</th>
          <th>Payment</th>
        </tr>
      </thead>
      <tbody>
        <?php $n=1; while ($ra = $report_apps->fetch_assoc()): ?>
        <tr>
          <td class="td-muted"><?= $n++ ?></td>
          <td>
            <div class="td-primary"><?= htmlspecialchars($ra['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($ra['email']) ?></div>
          </td>
          <td>
            <div class="td-primary"><?= htmlspecialchars(mb_substr($ra['title'],0,30)).'…' ?></div>
            <div class="td-muted"><?= htmlspecialchars($ra['level']??'') ?></div>
          </td>
          <td style="font-weight:600"><?= $ra['amount'] ? 'Rs.'.number_format($ra['amount']) : '—' ?></td>
          <td class="td-muted"><?= date('d M Y',strtotime($ra['applied_at'])) ?></td>
          <td><span class="badge-s badge-<?= $ra['status'] ?>"><?= ucfirst(str_replace('_',' ',$ra['status'])) ?></span></td>
          <td>
            <?php if ($ra['payment_status'] === 'paid'): ?>
              <span class="badge-s badge-paid">Rs.<?= number_format($ra['paid_amount']) ?></span>
            <?php else: ?>
              <span class="td-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">📊</span>
    <h5>No data for selected range</h5>
    <p>Try adjusting the date filter above.</p>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_end.php'; ?>
