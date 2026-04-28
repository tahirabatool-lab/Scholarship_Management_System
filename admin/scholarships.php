<?php
/**
 * admin/scholarships.php — Manage Scholarships (Create, Read, Update, Delete)
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Manage Scholarships';
$activePage = 'scholarships';

$error = $success = '';
$action = clean($_GET['action'] ?? 'list');
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// ── DELETE ──
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
  // NOTE: This uses direct interpolation into the SQL query.
  // For security hardening convert to a prepared statement to
  // avoid any chance of SQL injection if casting is bypassed.
  // Example prepared statement:
  // $stmt = $conn->prepare("DELETE FROM scholarships WHERE scholarship_id = ?");
  // $stmt->bind_param('i', $did); $stmt->execute();
  $conn->query("DELETE FROM scholarships WHERE scholarship_id=$did");
    header("Location: scholarships.php?success=Scholarship+deleted.");
    exit();
}

// ── TOGGLE STATUS ──
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
  // NOTE: Using inline queries to read/update status. Prefer prepared
  // statements and explicit validation of allowed status values.
  // Example:
  // $stmt = $conn->prepare("SELECT status FROM scholarships WHERE scholarship_id = ?");
  // $stmt->bind_param('i', $tid); $stmt->execute();
  // $cur = $stmt->get_result()->fetch_assoc()['status'] ?? '';
  $cur = $conn->query("SELECT status FROM scholarships WHERE scholarship_id=$tid")->fetch_assoc()['status'] ?? '';
  $new = $cur === 'active' ? 'closed' : 'active';
  $conn->query("UPDATE scholarships SET status='$new' WHERE scholarship_id=$tid");
    header("Location: scholarships.php?success=Status+updated.");
    exit();
}

// ── SAVE (Add or Edit) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid         = (int)($_POST['scholarship_id'] ?? 0);
    $title       = clean($_POST['title']       ?? '');
    $provider    = clean($_POST['provider']    ?? '');
    $category    = clean($_POST['category']    ?? '');
    $level       = clean($_POST['level']       ?? '');
    $type        = clean($_POST['type']        ?? '');
    $description = clean($_POST['description'] ?? '');
    $eligibility = clean($_POST['eligibility_criteria'] ?? '');
    $min_gpa     = trim($_POST['min_gpa']  ?? '') ?: null;
    $max_age     = trim($_POST['max_age']  ?? '') ?: null;
    $gender      = clean($_POST['gender_requirement'] ?? 'Any');
    $amount      = trim($_POST['amount']   ?? '') ?: null;
    $seats       = trim($_POST['total_seats'] ?? '') ?: null;
    $start_date  = clean($_POST['start_date'] ?? '') ?: null;
    $deadline    = clean($_POST['deadline']   ?? '') ?: null;
    $status      = clean($_POST['status']     ?? 'active');

    if (empty($title)) {
        $error = "Scholarship title is required.";
        $action = $sid ? 'edit' : 'add';
        $edit_id = $sid;
    } else {
        if ($sid) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE scholarships SET
                title=?,provider=?,category=?,level=?,type=?,description=?,
                eligibility_criteria=?,min_gpa=?,max_age=?,gender_requirement=?,
                amount=?,total_seats=?,start_date=?,deadline=?,status=?,
                updated_at=NOW()
                WHERE scholarship_id=?");
            $stmt->bind_param("sssssssdisissssi",
                $title,$provider,$category,$level,$type,$description,
                $eligibility,$min_gpa,$max_age,$gender,
                $amount,$seats,$start_date,$deadline,$status,$sid);
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO scholarships
                (title,provider,category,level,type,description,
                 eligibility_criteria,min_gpa,max_age,gender_requirement,
                 amount,total_seats,start_date,deadline,status)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssisisss",
                $title,$provider,$category,$level,$type,$description,
                $eligibility,$min_gpa,$max_age,$gender,
                $amount,$seats,$start_date,$deadline,$status);
            // Fix bind
            $stmt = $conn->prepare("INSERT INTO scholarships
                (title,provider,category,level,type,description,
                 eligibility_criteria,min_gpa,max_age,gender_requirement,
                 amount,total_seats,start_date,deadline,status)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssissssss",
                $title,$provider,$category,$level,$type,$description,
                $eligibility,$min_gpa,$max_age,$gender,
                $amount,$seats,$start_date,$deadline,$status);
        }
        if ($stmt->execute()) {
            header("Location: scholarships.php?success=Scholarship+".($sid?'updated':'added').".");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Fetch for edit
$edit_data = null;
if ($edit_id) {
  // WARNING: direct query interpolation below — convert to prepared
  // statements when refactoring. Currently we cast `$edit_id` to int,
  // which mitigates most risk, but prepared statements are recommended.
  $edit_data = $conn->query("SELECT * FROM scholarships WHERE scholarship_id=$edit_id")->fetch_assoc();
    $action = 'edit';
}

// List
// Retrieve list of scholarships. Consider adding pagination here to
// avoid loading all rows into memory when the dataset grows.
$scholarships = $conn->query("SELECT * FROM scholarships ORDER BY created_at DESC");

if ($action === 'add') {
    $pageTitle  = 'Add Scholarship';
    $activePage = 'add_scholarship';
}

include 'includes/layout.php';
?>

<?php if ($error):   ?><div class="alert-a alert-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if (isset($_GET['success'])): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= clean($_GET['success']) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- ── ADD / EDIT FORM ── -->
<div class="page-hd">
  <h1><?= $action === 'edit' ? 'Edit Scholarship' : 'Add New Scholarship' ?></h1>
  <div class="breadcrumb">
    <a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>
    <a href="scholarships.php">Scholarships</a><span class="bc-sep">›</span>
    <?= $action === 'edit' ? 'Edit' : 'Add' ?>
  </div>
</div>

<form method="POST" action="scholarships.php" novalidate>
  <?php if ($edit_data): ?>
    <input type="hidden" name="scholarship_id" value="<?= $edit_data['scholarship_id'] ?>">
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">

      <!-- Basic Info -->
      <div class="panel mb-4">
        <div class="panel-hd"><span class="panel-title">Basic Information</span></div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-lbl">Title <span style="color:var(--red)">*</span></label>
              <input type="text" name="title" class="form-ctrl"
                     placeholder="e.g. HEC Need Based Scholarship 2025"
                     value="<?= htmlspecialchars($edit_data['title'] ?? $_POST['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-lbl">Provider / Organization</label>
              <input type="text" name="provider" class="form-ctrl"
                     placeholder="e.g. Higher Education Commission"
                     value="<?= htmlspecialchars($edit_data['provider'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-lbl">Category</label>
              <input type="text" name="category" class="form-ctrl"
                     placeholder="e.g. STEM, Arts, General"
                     value="<?= htmlspecialchars($edit_data['category'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-lbl">Description</label>
              <textarea name="description" class="form-ctrl" rows="4"
                        placeholder="Detailed description of this scholarship…"
                        ><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-lbl">Eligibility Criteria</label>
              <textarea name="eligibility_criteria" class="form-ctrl" rows="3"
                        placeholder="Who can apply, what qualifications are needed…"
                        ><?= htmlspecialchars($edit_data['eligibility_criteria'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Academic Requirements -->
      <div class="panel mb-4">
        <div class="panel-hd"><span class="panel-title">Academic Requirements</span></div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-lbl">Education Level</label>
              <select name="level" class="form-sel">
                <option value="">Select level</option>
                <?php foreach (['Matric','Intermediate','Undergraduate','Postgraduate'] as $lvl): ?>
                <option value="<?= $lvl ?>" <?= ($edit_data['level'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-lbl">Scholarship Type</label>
              <select name="type" class="form-sel">
                <option value="">Select type</option>
                <?php foreach (['Merit','Need-Based','Talent-Based'] as $t): ?>
                <option value="<?= $t ?>" <?= ($edit_data['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-lbl">Gender Requirement</label>
              <select name="gender_requirement" class="form-sel">
                <?php foreach (['Any','Male','Female'] as $g): ?>
                <option value="<?= $g ?>" <?= ($edit_data['gender_requirement'] ?? 'Any') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-lbl">Minimum GPA</label>
              <input type="number" name="min_gpa" class="form-ctrl" step="0.01" min="0" max="4"
                     placeholder="e.g. 3.00"
                     value="<?= htmlspecialchars($edit_data['min_gpa'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-lbl">Maximum Age</label>
              <input type="number" name="max_age" class="form-ctrl" min="0"
                     placeholder="e.g. 25"
                     value="<?= htmlspecialchars($edit_data['max_age'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right column -->
    <div class="col-lg-4">

      <!-- Financial -->
      <div class="panel mb-4">
        <div class="panel-hd"><span class="panel-title">Financial Details</span></div>
        <div class="panel-body">
          <div class="mb-3">
            <label class="form-lbl">Amount (PKR)</label>
            <input type="number" name="amount" class="form-ctrl" min="0"
                   placeholder="e.g. 50000"
                   value="<?= htmlspecialchars($edit_data['amount'] ?? '') ?>">
          </div>
          <div>
            <label class="form-lbl">Total Seats</label>
            <input type="number" name="total_seats" class="form-ctrl" min="1"
                   placeholder="e.g. 100"
                   value="<?= htmlspecialchars($edit_data['total_seats'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Dates -->
      <div class="panel mb-4">
        <div class="panel-hd"><span class="panel-title">Dates</span></div>
        <div class="panel-body">
          <div class="mb-3">
            <label class="form-lbl">Start Date</label>
            <input type="date" name="start_date" class="form-ctrl"
                   value="<?= htmlspecialchars($edit_data['start_date'] ?? '') ?>">
          </div>
          <div>
            <label class="form-lbl">Application Deadline</label>
            <input type="date" name="deadline" class="form-ctrl"
                   value="<?= htmlspecialchars($edit_data['deadline'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Status -->
      <div class="panel mb-4">
        <div class="panel-hd"><span class="panel-title">Status</span></div>
        <div class="panel-body">
          <select name="status" class="form-sel">
            <option value="active" <?= ($edit_data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="closed" <?= ($edit_data['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>
      </div>

      <!-- Submit -->
      <div style="display:flex;gap:.65rem;flex-wrap:wrap">
        <button type="submit" class="btn-a btn-primary">
          <i class="fas fa-save"></i>
          <?= $action === 'edit' ? 'Save Changes' : 'Add Scholarship' ?>
        </button>
        <a href="scholarships.php" class="btn-a btn-secondary">Cancel</a>
      </div>

    </div>
  </div>
</form>

<?php else: ?>
<!-- ── LIST VIEW ── -->
<div class="page-hd">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <h1>Scholarships</h1>
      <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Scholarships
      </div>
    </div>
    <a href="scholarships.php?action=add" class="btn-a btn-primary">
      <i class="fas fa-plus"></i> Add Scholarship
    </a>
  </div>
</div>

<div class="panel">
  <div class="filter-row">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Search scholarships…">
    </div>
    <select id="filterStatus" class="form-sel" style="width:auto">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="closed">Closed</option>
    </select>
    <select id="filterType" class="form-sel" style="width:auto">
      <option value="">All Types</option>
      <option value="merit">Merit</option>
      <option value="need-based">Need-Based</option>
      <option value="talent-based">Talent-Based</option>
    </select>
    <span id="resultCount" style="font-size:.78rem;color:var(--muted);margin-left:auto">
      <?= $scholarships ? $scholarships->num_rows : 0 ?> scholarships
    </span>
  </div>
  <?php if ($scholarships && $scholarships->num_rows > 0): ?>
  <div class="table-wrap">
    <table class="admin-table" id="scholTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Provider</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Deadline</th>
          <th>Seats</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $n=1; while ($s = $scholarships->fetch_assoc()):
          $days = $s['deadline'] ? ceil((strtotime($s['deadline'])-time())/86400) : null;
        ?>
        <tr class="schol-row"
            data-title="<?= strtolower(htmlspecialchars($s['title'].' '.$s['provider'])) ?>"
            data-status="<?= strtolower($s['status']) ?>"
            data-type="<?= strtolower($s['type'] ?? '') ?>">
          <td class="td-muted"><?= $n++ ?></td>
          <td>
            <div class="td-primary"><?= htmlspecialchars(mb_substr($s['title'],0,40)) ?></div>
            <div class="td-muted"><?= htmlspecialchars($s['level'] ?? '—') ?></div>
          </td>
          <td class="td-muted"><?= htmlspecialchars(mb_substr($s['provider']??'—',0,25)) ?></td>
          <td><?= htmlspecialchars($s['type'] ?? '—') ?></td>
          <td style="font-weight:600;white-space:nowrap">
            <?= $s['amount'] ? 'Rs.'.number_format($s['amount']) : '—' ?>
          </td>
          <td>
            <?php if ($s['deadline']): ?>
              <div style="font-size:.8rem"><?= date('d M Y',strtotime($s['deadline'])) ?></div>
              <?php if ($days !== null && $days <= 7 && $days >= 0): ?>
              <div style="font-size:.7rem;color:var(--red);font-weight:600">
                <?= $days ?> days left!
              </div>
              <?php elseif ($days !== null && $days < 0): ?>
              <div style="font-size:.7rem;color:var(--muted)">Expired</div>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="td-muted"><?= $s['total_seats'] ?? '—' ?></td>
          <td>
            <span class="badge-s badge-<?= $s['status'] === 'active' ? 'active' : 'inactive' ?>">
              <?= ucfirst($s['status']) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap">
              <a href="scholarships.php?edit=<?= $s['scholarship_id'] ?>"
                 class="btn-a btn-secondary btn-sm" title="Edit">
                <i class="fas fa-edit"></i>
              </a>
              <a href="scholarships.php?toggle=<?= $s['scholarship_id'] ?>"
                 class="btn-a btn-warning btn-sm" title="Toggle status"
                 data-confirm="Toggle scholarship status?">
                <i class="fas fa-toggle-on"></i>
              </a>
              <a href="scholarships.php?delete=<?= $s['scholarship_id'] ?>"
                 class="btn-a btn-danger btn-sm" title="Delete"
                 data-confirm="Delete this scholarship? All related applications will also be deleted.">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">🎓</span>
    <h5>No scholarships yet</h5>
    <p>Click "Add Scholarship" to get started.</p>
  </div>
  <?php endif; ?>
</div>

<script>
const si = document.getElementById('searchInput');
const fStatus = document.getElementById('filterStatus');
const fType   = document.getElementById('filterType');
const rows    = document.querySelectorAll('.schol-row');
const cnt     = document.getElementById('resultCount');
function filter() {
  const q  = si.value.toLowerCase();
  const st = fStatus.value.toLowerCase();
  const tp = fType.value.toLowerCase();
  let n = 0;
  rows.forEach(r => {
    const ok = (!q  || r.dataset.title.includes(q))
            && (!st || r.dataset.status === st)
            && (!tp || r.dataset.type   === tp);
    r.style.display = ok ? '' : 'none';
    if (ok) n++;
  });
  cnt.textContent = n + ' scholarships';
}
si.addEventListener('input', filter);
fStatus.addEventListener('change', filter);
fType.addEventListener('change', filter);
</script>
<?php endif; ?>

<?php include 'includes/layout_end.php'; ?>
