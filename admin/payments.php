<?php
/**
 * admin/payments.php — Manage Payments & Disbursements
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Payments';
$activePage = 'payments';

$error = $success = '';

// ── Mark as Paid / Disburse ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disburse'])) {
    $app_id  = (int)$_POST['application_id'];
    $amount  = trim($_POST['amount'] ?? '');
    $remarks = clean($_POST['remarks'] ?? '');

    if (!$amount || !is_numeric($amount)) {
        $error = "Please enter a valid amount.";
    } else {
        // Check if payment record exists
        $exists = $conn->query("SELECT payment_id FROM payments WHERE application_id=$app_id")->num_rows;

        if ($exists) {
            $stmt = $conn->prepare("UPDATE payments SET amount=?, payment_status='paid', payment_date=NOW(), remarks=? WHERE application_id=?");
            $stmt->bind_param("dsi", $amount, $remarks, $app_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO payments (application_id,amount,payment_status,payment_date,remarks) VALUES (?,?,'paid',NOW(),?)");
            $stmt->bind_param("ids", $app_id, $amount, $remarks);
        }

        if ($stmt->execute()) {
            // Also mark application as disbursed
            $conn->query("UPDATE applications SET status='disbursed', updated_at=NOW() WHERE application_id=$app_id");

            // Notify student
            $info = $conn->query("
                SELECT a.user_id, s.title
                FROM applications a
                JOIN scholarships s ON s.scholarship_id=a.scholarship_id
                WHERE a.application_id=$app_id
            ")->fetch_assoc();
            if ($info) {
                $uid   = $info['user_id'];
                $title = $conn->real_escape_string($info['title']);
                $amt_f = number_format($amount);
                $conn->query("INSERT INTO notifications (user_id,message,type) VALUES ($uid,'Payment of Rs.$amt_f for scholarship \\'$title\\' has been disbursed to you.','payment')");
            }
            $success = "Payment of Rs.".number_format($amount)." disbursed successfully.";
        } else {
            $error = "Disbursement failed: " . $conn->error;
        }
    }
}

// ── Fetch approved applications (eligible for payment) ──
$approved = $conn->query("
    SELECT a.application_id, a.status, a.applied_at,
           u.full_name, u.email,
           s.title, s.amount AS schol_amount,
           p.payment_status, p.payment_date, p.amount AS paid_amount, p.remarks
    FROM applications a
    JOIN users u ON u.user_id = a.user_id
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    LEFT JOIN payments p ON p.application_id = a.application_id
    WHERE a.status IN ('approved','disbursed')
    ORDER BY a.updated_at DESC
");

// Disbursement summary
$total_paid    = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE payment_status='paid'")->fetch_assoc()['s'] ?? 0;
$total_pending = $conn->query("SELECT COUNT(*) c FROM applications WHERE status='approved'")->fetch_assoc()['c'] ?? 0;
$total_done    = $conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status='paid'")->fetch_assoc()['c'] ?? 0;

include 'includes/layout.php';
?>

<div class="page-hd">
  <h1>Payments & Disbursements</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Payments</div>
</div>

<?php if ($error):   ?><div class="alert-a alert-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>

<!-- Summary stat cards -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card" style="border-left:3px solid var(--purple)">
      <div class="stat-icon" style="background:var(--purple-lt)">
        <i class="fas fa-wallet" style="color:var(--purple)"></i>
      </div>
      <div>
        <div class="stat-num" style="font-size:1.4rem">Rs.<?= number_format($total_paid) ?></div>
        <div class="stat-lbl">Total Disbursed</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card" style="border-left:3px solid var(--amber)">
      <div class="stat-icon" style="background:var(--amber-lt)">
        <i class="fas fa-clock" style="color:var(--amber)"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_pending ?></div>
        <div class="stat-lbl">Pending Disbursement</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card" style="border-left:3px solid var(--green)">
      <div class="stat-icon" style="background:var(--green-lt)">
        <i class="fas fa-check-circle" style="color:var(--green)"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_done ?></div>
        <div class="stat-lbl">Completed Payments</div>
      </div>
    </div>
  </div>
</div>

<!-- Payments Table -->
<div class="panel">
  <div class="panel-hd">
    <span class="panel-title"><i class="fas fa-money-bill-wave me-2" style="color:var(--em)"></i>Approved Applications — Payment Management</span>
  </div>
  <?php if ($approved && $approved->num_rows > 0): ?>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Scholarship</th>
          <th>Scholarship Amt</th>
          <th>App Status</th>
          <th>Payment</th>
          <th>Paid On</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $n=1; while ($row = $approved->fetch_assoc()): ?>
        <tr>
          <td class="td-muted"><?= $n++ ?></td>
          <td>
            <div class="td-primary"><?= htmlspecialchars($row['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($row['email']) ?></div>
          </td>
          <td class="td-primary"><?= htmlspecialchars(mb_substr($row['title'],0,32)).'…' ?></td>
          <td style="font-weight:600">
            <?= $row['schol_amount'] ? 'Rs.'.number_format($row['schol_amount']) : '—' ?>
          </td>
          <td>
            <span class="badge-s badge-<?= $row['status'] ?>">
              <?= ucfirst(str_replace('_',' ',$row['status'])) ?>
            </span>
          </td>
          <td>
            <?php if ($row['payment_status'] === 'paid'): ?>
              <span class="badge-s badge-paid">Rs.<?= number_format($row['paid_amount']) ?></span>
            <?php else: ?>
              <span style="color:var(--muted);font-size:.78rem">Not paid yet</span>
            <?php endif; ?>
          </td>
          <td class="td-muted">
            <?= $row['payment_date'] ? date('d M Y',strtotime($row['payment_date'])) : '—' ?>
          </td>
          <td>
            <?php if ($row['payment_status'] !== 'paid'): ?>
            <button type="button"
                    class="btn-a btn-primary btn-sm"
                    onclick="openDisburse(<?= $row['application_id'] ?>, '<?= addslashes(htmlspecialchars($row['full_name'])) ?>', <?= $row['schol_amount'] ?? 0 ?>)">
              <i class="fas fa-paper-plane"></i> Disburse
            </button>
            <?php else: ?>
            <span style="font-size:.75rem;color:var(--green);font-weight:600">
              <i class="fas fa-check-circle"></i> Done
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">💰</span>
    <h5>No approved applications</h5>
    <p>Applications need to be approved before payment can be disbursed.</p>
    <a href="applications.php" class="btn-a btn-primary">View Applications</a>
  </div>
  <?php endif; ?>
</div>

<!-- Disburse Modal -->
<div id="disburseModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:var(--white);border-radius:var(--r-lg);width:100%;max-width:420px;margin:1rem;box-shadow:var(--shadow-lg)">
    <div style="padding:1.2rem 1.4rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <span style="font-family:'Playfair Display',serif;font-weight:700;color:var(--ink)">Disburse Payment</span>
      <button onclick="closeDisburse()" style="border:none;background:transparent;cursor:pointer;color:var(--muted);font-size:1.1rem">&times;</button>
    </div>
    <form method="POST" action="payments.php" id="disburseForm">
      <input type="hidden" name="disburse" value="1">
      <input type="hidden" name="application_id" id="disburse_app_id">
      <div style="padding:1.4rem">
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:1.2rem">
          Disbursing payment to: <strong id="disburse_name" style="color:var(--ink)"></strong>
        </p>
        <div style="margin-bottom:1rem">
          <label class="form-lbl">Amount (PKR) <span style="color:var(--red)">*</span></label>
          <input type="number" name="amount" id="disburse_amount"
                 class="form-ctrl" min="1" step="0.01" placeholder="Enter amount" required>
        </div>
        <div style="margin-bottom:1.2rem">
          <label class="form-lbl">Remarks / Reference</label>
          <textarea name="remarks" class="form-ctrl" rows="2"
                    placeholder="e.g. Bank transfer ref #12345"></textarea>
        </div>
        <div style="display:flex;gap:.6rem">
          <button type="submit" class="btn-a btn-primary">
            <i class="fas fa-paper-plane"></i> Confirm & Disburse
          </button>
          <button type="button" onclick="closeDisburse()" class="btn-a btn-secondary">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openDisburse(appId, name, amount) {
  document.getElementById('disburse_app_id').value = appId;
  document.getElementById('disburse_name').textContent = name;
  document.getElementById('disburse_amount').value = amount || '';
  document.getElementById('disburseModal').style.display = 'flex';
}
function closeDisburse() {
  document.getElementById('disburseModal').style.display = 'none';
}
</script>

<?php include 'includes/layout_end.php'; ?>
