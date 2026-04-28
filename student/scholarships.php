<?php
/**
 * student/scholarships.php — Browse available scholarships
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'Browse Scholarships';
$activePage = 'scholarships';

// IDs already applied for — used to disable Apply button
$applied_ids = [];
$applied_res = $conn->query("SELECT scholarship_id FROM applications WHERE user_id=$user_id");
while ($row = $applied_res->fetch_assoc()) {
    $applied_ids[] = $row['scholarship_id'];
}

// Single view mode
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$view_schol = null;
if ($view_id) {
    $vs = $conn->prepare("SELECT * FROM scholarships WHERE scholarship_id=? AND status='active'");
    $vs->bind_param("i", $view_id);
    $vs->execute();
    $view_schol = $vs->get_result()->fetch_assoc();
}

// Fetch all active scholarships
$scholarships = $conn->query("
    SELECT scholarship_id, title, provider, type, level, amount,
           total_seats, deadline, category, min_gpa, gender_requirement, eligibility_criteria
    FROM scholarships
    WHERE status='active'
    ORDER BY deadline ASC
");

include 'includes/layout.php';
?>

<!-- Detail Modal (shown when ?view=ID) -->
<?php if ($view_schol): ?>
<div class="modal fade show" id="viewModal" tabindex="-1"
     style="display:block;background:rgba(0,0,0,.5)" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:var(--radius-lg);border:none">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--primary-dk),var(--primary));color:#fff;border-radius:var(--radius-lg) var(--radius-lg) 0 0">
        <div>
          <h5 class="modal-title" style="font-family:'Lora',serif;color:#fff">
            <?= htmlspecialchars($view_schol['title']) ?>
          </h5>
          <small style="opacity:.7"><?= htmlspecialchars($view_schol['provider'] ?? '') ?></small>
        </div>
        <a href="scholarships.php" class="btn-close btn-close-white"></a>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div style="background:var(--bg);border-radius:var(--radius);padding:1rem;text-align:center">
              <div style="font-family:'Lora',serif;font-size:1.5rem;font-weight:700;color:var(--primary)">
                <?= $view_schol['amount'] ? 'Rs. '.number_format($view_schol['amount']) : 'Varies' ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted)">Award Amount</div>
            </div>
          </div>
          <div class="col-md-4">
            <div style="background:var(--bg);border-radius:var(--radius);padding:1rem;text-align:center">
              <div style="font-size:1.1rem;font-weight:700;color:var(--ink)"><?= htmlspecialchars($view_schol['level'] ?? '—') ?></div>
              <div style="font-size:.75rem;color:var(--muted)">Education Level</div>
            </div>
          </div>
          <div class="col-md-4">
            <div style="background:var(--bg);border-radius:var(--radius);padding:1rem;text-align:center">
              <div style="font-size:1.1rem;font-weight:700;color:var(--ink)">
                <?= $view_schol['deadline'] ? date('d M Y', strtotime($view_schol['deadline'])) : '—' ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted)">Deadline</div>
            </div>
          </div>
        </div>

        <?php if ($view_schol['description']): ?>
        <h6 style="font-weight:700;margin-bottom:.5rem">Description</h6>
        <p style="font-size:.88rem;color:var(--body);line-height:1.7;margin-bottom:1.2rem">
          <?= nl2br(htmlspecialchars($view_schol['description'])) ?>
        </p>
        <?php endif; ?>

        <?php if ($view_schol['eligibility_criteria']): ?>
        <h6 style="font-weight:700;margin-bottom:.5rem">Eligibility Criteria</h6>
        <p style="font-size:.88rem;color:var(--body);line-height:1.7;margin-bottom:1.2rem">
          <?= nl2br(htmlspecialchars($view_schol['eligibility_criteria'])) ?>
        </p>
        <?php endif; ?>

        <div class="row g-2">
          <?php $meta = [
            ['Type',        $view_schol['type']               ?? '—', 'fa-tag'],
            ['Seats',       ($view_schol['total_seats'] ?? '—').' available', 'fa-users'],
            ['Min GPA',     $view_schol['min_gpa']            ?? 'None', 'fa-star'],
            ['Gender',      $view_schol['gender_requirement'] ?? 'Any',  'fa-venus-mars'],
          ];
          foreach ($meta as [$label, $val, $icon]): ?>
          <div class="col-md-3 col-6">
            <div style="background:var(--bg);border-radius:var(--radius);padding:.75rem;text-align:center">
              <i class="fas <?= $icon ?>" style="color:var(--primary);margin-bottom:.3rem"></i>
              <div style="font-size:.72rem;color:var(--muted)"><?= $label ?></div>
              <div style="font-size:.85rem;font-weight:600;color:var(--ink)"><?= htmlspecialchars($val) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <?php if (!in_array($view_id, $applied_ids)): ?>
          <a href="apply.php?id=<?= $view_id ?>" class="btn-s-primary">
            <i class="fas fa-paper-plane"></i> Apply for This Scholarship
          </a>
        <?php else: ?>
          <span class="status-badge badge-pending">Already Applied</span>
        <?php endif; ?>
        <a href="scholarships.php" class="btn-s-outline">Close</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
  <h1>Browse Scholarships</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="sep">›</span>Scholarships</div>
</div>

<!-- Filter Row -->
<div class="panel mb-4">
  <div class="panel-body" style="padding:1.2rem">
    <div class="row g-2 align-items-center">
      <div class="col-lg-5 col-md-6">
        <div style="position:relative">
          <input type="text" id="searchInput" class="form-control-s ps-5"
                 placeholder="Search by title, provider…" style="padding-left:2.5rem">
          <i class="fas fa-search" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.82rem"></i>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <select id="filterType" class="form-select-s">
          <option value="">All Types</option>
          <option value="merit">Merit</option>
          <option value="need-based">Need-Based</option>
          <option value="talent-based">Talent-Based</option>
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <select id="filterLevel" class="form-select-s">
          <option value="">All Levels</option>
          <option value="matric">Matric</option>
          <option value="intermediate">Intermediate</option>
          <option value="undergraduate">Undergraduate</option>
          <option value="postgraduate">Postgraduate</option>
        </select>
      </div>
      <div class="col-auto">
        <span id="resultCount" style="font-size:.8rem;color:var(--muted);white-space:nowrap">
          <?= $scholarships ? $scholarships->num_rows : 0 ?> found
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Cards -->
<div class="row g-3" id="scholGrid">
  <?php if ($scholarships && $scholarships->num_rows > 0):
    while ($s = $scholarships->fetch_assoc()):
      $already = in_array($s['scholarship_id'], $applied_ids);
      $days_left = $s['deadline'] ? ceil((strtotime($s['deadline']) - time()) / 86400) : null;
      $urgent = $days_left !== null && $days_left <= 7;
      $type_class = ['merit'=>'type-merit','need-based'=>'type-need','talent-based'=>'type-talent'][strtolower($s['type'] ?? '')] ?? 'type-merit';
  ?>
  <div class="col-xl-4 col-md-6 schol-filter-item"
       data-title="<?= strtolower(htmlspecialchars($s['title'].' '.$s['provider'])) ?>"
       data-type="<?= strtolower(htmlspecialchars($s['type'] ?? '')) ?>"
       data-level="<?= strtolower(htmlspecialchars($s['level'] ?? '')) ?>">
    <div class="schol-card">
      <div class="schol-card-top">
        <span class="schol-type-badge <?= $type_class ?>">
          <i class="fas fa-tag"></i> <?= htmlspecialchars($s['type'] ?? 'Merit') ?>
        </span>
        <?php if ($already): ?>
          <span class="schol-type-badge" style="background:rgba(22,163,74,.2);color:#4ade80;margin-left:.5rem">
            <i class="fas fa-check"></i> Applied
          </span>
        <?php endif; ?>
        <div class="schol-amount">
          <?= $s['amount'] ? 'Rs. '.number_format($s['amount']) : 'Varies' ?>
          <small><?= htmlspecialchars($s['level'] ?? '') ?></small>
        </div>
      </div>
      <div class="schol-card-body">
        <div class="schol-title"><?= htmlspecialchars($s['title']) ?></div>
        <div class="schol-provider">
          <i class="fas fa-building"></i><?= htmlspecialchars($s['provider'] ?? '') ?>
        </div>
        <div class="schol-meta">
          <?php if ($s['min_gpa']): ?>
          <span class="schol-meta-tag"><i class="fas fa-star"></i>GPA <?= $s['min_gpa'] ?>+</span>
          <?php endif; ?>
          <?php if ($s['total_seats']): ?>
          <span class="schol-meta-tag"><i class="fas fa-users"></i><?= $s['total_seats'] ?> seats</span>
          <?php endif; ?>
          <?php if ($s['gender_requirement'] && $s['gender_requirement'] !== 'Any'): ?>
          <span class="schol-meta-tag"><i class="fas fa-venus-mars"></i><?= $s['gender_requirement'] ?></span>
          <?php endif; ?>
        </div>
        <?php if ($days_left !== null): ?>
        <div class="schol-deadline <?= $urgent ? 'urgent' : '' ?>">
          <i class="fas fa-clock"></i>
          <?php if ($days_left < 0) echo 'Deadline passed';
                elseif ($days_left === 0) echo 'Deadline: Today!';
                elseif ($urgent) echo "Only {$days_left} days left!";
                else echo 'Deadline: '.date('d M Y', strtotime($s['deadline'])); ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="schol-card-footer">
        <?php if ($already): ?>
          <span class="btn-apply" style="background:var(--border);color:var(--muted);cursor:default">
            <i class="fas fa-check"></i> Already Applied
          </span>
        <?php elseif ($days_left !== null && $days_left < 0): ?>
          <span class="btn-apply" style="background:var(--border);color:var(--muted);cursor:default">Closed</span>
        <?php else: ?>
          <a href="apply.php?id=<?= $s['scholarship_id'] ?>" class="btn-apply">
            <i class="fas fa-paper-plane"></i> Apply Now
          </a>
        <?php endif; ?>
        <a href="scholarships.php?view=<?= $s['scholarship_id'] ?>" class="btn-view-detail">
          <i class="fas fa-eye"></i> Details
        </a>
      </div>
    </div>
  </div>
  <?php endwhile;
  else: ?>
  <div class="col-12">
    <div class="empty-state">
      <span class="empty-icon">🎓</span>
      <h4>No scholarships available right now</h4>
      <p>Check back soon — new opportunities are posted regularly.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Live filter
const search = document.getElementById('searchInput');
const fType  = document.getElementById('filterType');
const fLevel = document.getElementById('filterLevel');
const items  = document.querySelectorAll('.schol-filter-item');
const count  = document.getElementById('resultCount');

function filterSchols() {
  const q = (search.value || '').toLowerCase();
  const t = (fType.value  || '').toLowerCase();
  const l = (fLevel.value || '').toLowerCase();
  let n = 0;
  items.forEach(el => {
    const match = (!q || el.dataset.title.includes(q))
               && (!t || el.dataset.type  === t)
               && (!l || el.dataset.level === l);
    el.style.display = match ? '' : 'none';
    if (match) n++;
  });
  count.textContent = n + ' found';
}
search.addEventListener('input', filterSchols);
fType.addEventListener('change', filterSchols);
fLevel.addEventListener('change', filterSchols);
</script>

<?php include 'includes/layout_end.php'; ?>
