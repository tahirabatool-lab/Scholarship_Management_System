<?php
/**
 * admin/applications.php — View & Manage All Applications
 * Approve / Reject / Mark Under Review
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Applications';
$activePage = 'applications';

// ── Change status ──
if (isset($_GET['action'], $_GET['id'])) {
    $app_id    = (int)$_GET['id'];
    $newStatus = clean($_GET['action']);
    $allowed   = ['pending','under_review','approved','rejected','disbursed'];

    if (in_array($newStatus, $allowed)) {
        $conn->query("UPDATE applications SET status='$newStatus', updated_at=NOW() WHERE application_id=$app_id");

        // Notify student
        $app_info = $conn->query("
            SELECT a.user_id, s.title
            FROM applications a
            JOIN scholarships s ON s.scholarship_id=a.scholarship_id
            WHERE a.application_id=$app_id
        ")->fetch_assoc();

        if ($app_info) {
            $uid  = $app_info['user_id'];
            $stitle = $conn->real_escape_string($app_info['title']);
            $msgs = [
                'under_review' => "Your application for '$stitle' is now under review.",
                'approved'     => "Congratulations! Your application for '$stitle' has been approved.",
                'rejected'     => "Your application for '$stitle' was not successful this time.",
                'disbursed'    => "Payment for '$stitle' has been disbursed. Check your account.",
            ];
            $types = [
                'under_review' => 'info',
                'approved'     => 'success',
                'rejected'     => 'warning',
                'disbursed'    => 'payment',
            ];
            if (isset($msgs[$newStatus])) {
                $msg  = $msgs[$newStatus];
                $msg  = $conn->real_escape_string($msg);
                $type = $types[$newStatus];
                $conn->query("INSERT INTO notifications (user_id,message,type) VALUES ($uid,'$msg','$type')");
            }

            // Log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ip = $conn->real_escape_string($ip);
            $admin_id = $_SESSION['user_id'];
            $conn->query("INSERT INTO activity_logs (user_id,action,table_name,record_id,description,ip_address)
                          VALUES ($admin_id,'STATUS_CHANGE','applications',$app_id,'Status changed to $newStatus','$ip')");
        }

        header("Location: applications.php?success=Status+updated+to+".ucfirst(str_replace('_',' ',$newStatus)).".");
        exit();
    }
}

// Filter
$filter_status = clean($_GET['status'] ?? '');
$valid_statuses = ['pending','under_review','approved','rejected','disbursed'];
$where = "1";
if ($filter_status && in_array($filter_status, $valid_statuses)) {
    $where .= " AND a.status='$filter_status'";
}

// Detail view
$detail_id  = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;
$detail_app = null;
$detail_docs= null;
if ($detail_id) {
    $detail_app = $conn->query("
        SELECT a.*, u.full_name, u.email, u.phone,
               s.title AS schol_title, s.provider, s.amount AS schol_amount,
               s.level, s.type, s.deadline,
               p.payment_status, p.payment_date, p.amount AS paid_amount
        FROM applications a
        JOIN users u ON u.user_id = a.user_id
        JOIN scholarships s ON s.scholarship_id = a.scholarship_id
        LEFT JOIN payments p ON p.application_id = a.application_id
        WHERE a.application_id=$detail_id
    ")->fetch_assoc();
    if ($detail_app) {
        $detail_docs = $conn->query("SELECT * FROM application_documents WHERE application_id=$detail_id");
    }
}

// Fetch applications
$applications = $conn->query("
    SELECT a.application_id, a.status, a.applied_at, a.updated_at,
           u.full_name, u.email,
           s.title, s.amount, s.level, s.type
    FROM applications a
    JOIN users u ON u.user_id = a.user_id
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

include 'includes/layout.php';
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= clean($_GET['success']) ?></div>
<?php endif; ?>

<!-- Detail Panel -->
<?php if ($detail_app): ?>
<div class="panel mb-4">
  <div class="panel-hd">
    <div>
      <span class="panel-title">Application #<?= $detail_id ?></span>
      <span class="td-muted" style="margin-left:.75rem">
        Submitted <?= date('d M Y, h:i A', strtotime($detail_app['applied_at'])) ?>
      </span>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
      <span class="badge-s badge-<?= $detail_app['status'] ?>">
        <?= ucfirst(str_replace('_',' ',$detail_app['status'])) ?>
      </span>
      <a href="applications.php<?= $filter_status ? '?status='.$filter_status : '' ?>"
         class="btn-a btn-secondary btn-sm">
        <i class="fas fa-times"></i> Close
      </a>
    </div>
  </div>
  <div class="panel-body">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:.75rem">Student Info</h6>
        <?php foreach ([
          ['Name',   $detail_app['full_name']],
          ['Email',  $detail_app['email']],
          ['Phone',  $detail_app['phone'] ?? '—'],
          ['CNIC',   $detail_app['cnic']  ?? '—'],
          ['Gender', $detail_app['gender'] ?? '—'],
          ['DOB',    $detail_app['date_of_birth'] ? date('d M Y',strtotime($detail_app['date_of_birth'])) : '—'],
        ] as [$l,$v]): ?>
        <div style="display:flex;justify-content:space-between;padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem">
          <span style="color:var(--muted)"><?= $l ?></span>
          <span style="font-weight:600;color:var(--ink)"><?= htmlspecialchars($v) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="col-md-4">
        <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:.75rem">Scholarship Info</h6>
        <?php foreach ([
          ['Title',    $detail_app['schol_title']],
          ['Provider', $detail_app['provider'] ?? '—'],
          ['Amount',   $detail_app['schol_amount'] ? 'Rs.'.number_format($detail_app['schol_amount']) : 'Varies'],
          ['Level',    $detail_app['level'] ?? '—'],
          ['Deadline', $detail_app['deadline'] ? date('d M Y',strtotime($detail_app['deadline'])) : '—'],
        ] as [$l,$v]): ?>
        <div style="display:flex;justify-content:space-between;padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem">
          <span style="color:var(--muted)"><?= $l ?></span>
          <span style="font-weight:600;color:var(--ink)"><?= htmlspecialchars($v) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="col-md-4">
        <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:.75rem">Academic Info</h6>
        <?php foreach ([
          ['Matric',        $detail_app['matric_marks']        ?? '—'],
          ['Intermediate',  $detail_app['intermediate_marks']  ?? '—'],
          ['University',    $detail_app['university']          ?? '—'],
          ['Father\'s Name',$detail_app['father_name']         ?? '—'],
          ['Address',       mb_substr($detail_app['address'] ?? '—', 0, 35)],
        ] as [$l,$v]): ?>
        <div style="display:flex;justify-content:space-between;padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem">
          <span style="color:var(--muted)"><?= $l ?></span>
          <span style="font-weight:600;color:var(--ink)"><?= htmlspecialchars($v) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Action buttons -->
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.2rem">
      <strong style="font-size:.8rem;color:var(--muted);align-self:center">Change Status:</strong>
      <?php
      $btns = [
        ['under_review','Mark Under Review','btn-info',   'fa-search'],
        ['approved',    'Approve',          'btn-primary','fa-check'],
        ['rejected',    'Reject',           'btn-danger', 'fa-times'],
      ];
      foreach ($btns as [$st, $lbl, $cls, $ico]):
        if ($detail_app['status'] !== $st):
      ?>
      <a href="applications.php?action=<?= $st ?>&id=<?= $detail_id ?>&detail=<?= $detail_id ?>"
         class="btn-a <?= $cls ?> btn-sm"
         data-confirm="<?= "Change status to ".ucfirst(str_replace('_',' ',$st))."?" ?>">
        <i class="fas <?= $ico ?>"></i> <?= $lbl ?>
      </a>
      <?php endif; endforeach; ?>
    </div>

    <!-- Documents -->
    <?php if ($detail_docs && $detail_docs->num_rows > 0): ?>
    <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:.65rem">
      Uploaded Documents (<?= $detail_docs->num_rows ?>)
    </h6>
    <div class="row g-2">
      <?php while ($doc = $detail_docs->fetch_assoc()):
        $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
      ?>
      <div class="col-md-3 col-6">
        <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"
           style="display:flex;align-items:center;gap:.55rem;padding:.65rem .8rem;
                  background:var(--bg);border:1px solid var(--border);border-radius:var(--r);
                  text-decoration:none;transition:all var(--t)"
           onmouseover="this.style.borderColor='var(--em)'" onmouseout="this.style.borderColor='var(--border)'">
          <i class="fas fa-file-<?= $ext === 'pdf' ? 'pdf' : 'image' ?>"
             style="color:<?= $ext === 'pdf' ? 'var(--red)' : 'var(--sky)' ?>;font-size:1.1rem;flex-shrink:0"></i>
          <div style="min-width:0">
            <div style="font-size:.75rem;font-weight:600;color:var(--ink);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($doc['document_name'] ?: 'Document') ?>
            </div>
            <div style="font-size:.68rem;color:var(--muted)"><?= strtoupper($ext) ?></div>
          </div>
        </a>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Page header + filters -->
<div class="page-hd">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <h1>All Applications</h1>
      <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Applications</div>
    </div>
  </div>
</div>

<!-- Status tabs -->
<div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:1.25rem">
  <?php
  $tabs = [
    ['','All','fa-list'],
    ['pending','Pending','fa-clock'],
    ['under_review','Under Review','fa-search'],
    ['approved','Approved','fa-check'],
    ['rejected','Rejected','fa-times'],
    ['disbursed','Disbursed','fa-wallet'],
  ];
  foreach ($tabs as [$val,$label,$icon]):
    $act = $filter_status === $val;
    $cnt_q = $val ? $conn->query("SELECT COUNT(*) c FROM applications WHERE status='$val'")->fetch_assoc()['c'] : $conn->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'];
  ?>
  <a href="applications.php?status=<?= $val ?>"
     style="display:inline-flex;align-items:center;gap:.4rem;
            padding:.42rem .9rem;border-radius:999px;font-size:.78rem;font-weight:600;
            border:1.5px solid <?= $act ? 'var(--em)' : 'var(--border)' ?>;
            background:<?= $act ? 'var(--em)' : 'var(--white)' ?>;
            color:<?= $act ? '#fff' : 'var(--muted)' ?>;
            text-decoration:none;transition:all var(--t)">
    <i class="fas <?= $icon ?>"></i><?= $label ?>
    <span style="background:<?= $act ? 'rgba(255,255,255,.25)' : 'var(--bg)' ?>;
                  padding:.05rem .4rem;border-radius:999px;font-size:.65rem"><?= $cnt_q ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Table -->
<div class="panel">
  <div class="filter-row">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" id="appSearch" placeholder="Search by student or scholarship…">
    </div>
    <span id="appCount" style="font-size:.78rem;color:var(--muted);margin-left:auto">
      <?= $applications ? $applications->num_rows : 0 ?> applications
    </span>
  </div>
  <?php if ($applications && $applications->num_rows > 0): ?>
  <div class="table-wrap">
    <table class="admin-table" id="appsTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Scholarship</th>
          <th>Amount</th>
          <th>Applied</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $n=1; while ($app = $applications->fetch_assoc()): ?>
        <tr class="app-row" data-text="<?= strtolower(htmlspecialchars($app['full_name'].' '.$app['title'].' '.$app['email'])) ?>">
          <td class="td-muted"><?= $n++ ?></td>
          <td>
            <div class="td-primary"><?= htmlspecialchars($app['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($app['email']) ?></div>
          </td>
          <td>
            <div class="td-primary"><?= htmlspecialchars(mb_substr($app['title'],0,32)).'…' ?></div>
            <div class="td-muted"><?= htmlspecialchars($app['level']??'') ?></div>
          </td>
          <td style="font-weight:600;white-space:nowrap">
            <?= $app['amount'] ? 'Rs.'.number_format($app['amount']) : '—' ?>
          </td>
          <td class="td-muted"><?= date('d M Y',strtotime($app['applied_at'])) ?></td>
          <td>
            <span class="badge-s badge-<?= $app['status'] ?>">
              <?= ucfirst(str_replace('_',' ',$app['status'])) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:.3rem">
              <a href="applications.php?detail=<?= $app['application_id'] ?><?= $filter_status ? '&status='.$filter_status : '' ?>"
                 class="btn-a btn-secondary btn-sm"><i class="fas fa-eye"></i> View</a>
              <?php if ($app['status'] === 'pending'): ?>
              <a href="applications.php?action=under_review&id=<?= $app['application_id'] ?>"
                 class="btn-a btn-info btn-sm" data-confirm="Mark as Under Review?">
                <i class="fas fa-search"></i>
              </a>
              <?php endif; ?>
              <?php if (in_array($app['status'],['pending','under_review'])): ?>
              <a href="applications.php?action=approved&id=<?= $app['application_id'] ?>"
                 class="btn-a btn-primary btn-sm" data-confirm="Approve this application?">
                <i class="fas fa-check"></i>
              </a>
              <a href="applications.php?action=rejected&id=<?= $app['application_id'] ?>"
                 class="btn-a btn-danger btn-sm" data-confirm="Reject this application?">
                <i class="fas fa-times"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">📋</span>
    <h5>No applications found</h5>
    <p><?= $filter_status ? "No applications with status '".ucfirst(str_replace('_',' ',$filter_status))."'" : 'No applications yet.' ?></p>
  </div>
  <?php endif; ?>
</div>

<script>
const as = document.getElementById('appSearch');
const ar = document.querySelectorAll('.app-row');
const ac = document.getElementById('appCount');
if (as) {
  as.addEventListener('input', () => {
    const q = as.value.toLowerCase(); let n = 0;
    ar.forEach(r => { const ok = r.dataset.text.includes(q); r.style.display = ok?'':'none'; if(ok)n++; });
    ac.textContent = n + ' applications';
  });
}
</script>

<?php include 'includes/layout_end.php'; ?>
