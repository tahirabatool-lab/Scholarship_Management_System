<?php
/**
 * scholarships.php — Public Scholarship Listings
 */
require_once 'db.php';

$pageTitle  = 'Browse Scholarships';
$activePage = 'scholarships';
include 'includes/header.php';

// Fetch all active scholarships
$scholarships = $conn->query("
    SELECT scholarship_id, title, provider, type, level, amount,
           total_seats, deadline, category, min_gpa, gender_requirement, description
    FROM scholarships
    WHERE status = 'active'
    ORDER BY deadline ASC
");

$total = $scholarships ? $scholarships->num_rows : 0;
?>

<!-- Page Hero -->
<div class="page-hero">
  <div class="container text-center position-relative" style="z-index:2">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9)">All Open Scholarships</span>
    <h1 class="mt-2 mb-3">Find Your Scholarship</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Scholarships</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Main Content -->
<section class="section-pad">
  <div class="container">

    <!-- Filter Bar -->
    <div class="filter-bar fade-up">
      <div class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-6">
          <label class="form-label fw-semibold" style="font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">
            Search
          </label>
          <div class="position-relative">
            <input type="text" id="searchInput" class="form-control ps-4"
                   placeholder="Search by title, provider…">
            <i class="fas fa-search position-absolute"
               style="left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem"></i>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold" style="font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">
            Type
          </label>
          <select id="filterType" class="form-select">
            <option value="">All Types</option>
            <option value="merit">Merit</option>
            <option value="need-based">Need-Based</option>
            <option value="talent-based">Talent-Based</option>
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold" style="font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">
            Level
          </label>
          <select id="filterLevel" class="form-select">
            <option value="">All Levels</option>
            <option value="matric">Matric</option>
            <option value="intermediate">Intermediate</option>
            <option value="undergraduate">Undergraduate</option>
            <option value="postgraduate">Postgraduate</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-6">
          <p class="mb-0 fw-semibold" style="font-size:.82rem;color:var(--muted)">
            Showing <span id="resultCount" class="fw-bold" style="color:var(--blue-600)"><?= $total ?> scholarship<?= $total !== 1 ? 's' : '' ?></span>
          </p>
        </div>
      </div>
    </div><!-- /filter-bar -->

    <!-- Cards -->
    <div class="row g-4" id="scholarshipsGrid">
      <?php if ($scholarships && $scholarships->num_rows > 0): ?>
        <?php while ($s = $scholarships->fetch_assoc()): ?>
          <?php
            $badge_class = match(strtolower($s['type'] ?? '')) {
              'merit'        => 'badge-merit',
              'need-based'   => 'badge-need',
              'talent-based' => 'badge-talent',
              default        => 'badge-merit'
            };
            $days_left = $s['deadline'] ? ceil((strtotime($s['deadline']) - time()) / 86400) : null;
            $is_urgent = $days_left !== null && $days_left <= 7;
          ?>
          <div class="col-lg-4 col-md-6 fade-up schol-filter-item"
               data-title="<?= strtolower(htmlspecialchars($s['title'] . ' ' . $s['provider'])) ?>"
               data-type="<?= strtolower(htmlspecialchars($s['type'] ?? '')) ?>"
               data-level="<?= strtolower(htmlspecialchars($s['level'] ?? '')) ?>">
            <div class="schol-card">
              <div class="schol-card-header">
                <span class="schol-badge <?= $badge_class ?>">
                  <i class="fas fa-tag"></i> <?= htmlspecialchars($s['type'] ?? 'Merit') ?>
                </span>
                <div class="schol-amount">
                  <?= $s['amount'] ? 'Rs. ' . number_format($s['amount']) : 'Varies' ?>
                  <small><?= htmlspecialchars($s['level'] ?? '') ?> Level</small>
                </div>
              </div>

              <div class="schol-card-body">
                <h3 class="schol-title"><?= htmlspecialchars($s['title']) ?></h3>
                <p class="schol-provider">
                  <i class="fas fa-building" style="color:var(--blue-400)"></i>
                  <?= htmlspecialchars($s['provider'] ?? 'N/A') ?>
                </p>

                <?php if ($s['description']): ?>
                <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;line-height:1.6">
                  <?= htmlspecialchars(mb_substr($s['description'], 0, 110)) ?>…
                </p>
                <?php endif; ?>

                <div class="schol-meta">
                  <?php if ($s['level']): ?>
                  <span class="schol-meta-item"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($s['level']) ?></span>
                  <?php endif; ?>
                  <?php if ($s['total_seats']): ?>
                  <span class="schol-meta-item"><i class="fas fa-users"></i> <?= $s['total_seats'] ?> seats</span>
                  <?php endif; ?>
                  <?php if ($s['min_gpa']): ?>
                  <span class="schol-meta-item"><i class="fas fa-star"></i> Min GPA <?= $s['min_gpa'] ?></span>
                  <?php endif; ?>
                  <?php if ($s['gender_requirement'] && $s['gender_requirement'] !== 'Any'): ?>
                  <span class="schol-meta-item"><i class="fas fa-venus-mars"></i> <?= $s['gender_requirement'] ?> only</span>
                  <?php endif; ?>
                </div>

                <?php if ($days_left !== null): ?>
                <div class="schol-deadline <?= $is_urgent ? 'urgent' : '' ?>">
                  <i class="fas fa-clock"></i>
                  <?php if ($days_left < 0): ?>
                    Deadline passed
                  <?php elseif ($days_left === 0): ?>
                    Deadline: Today!
                  <?php elseif ($is_urgent): ?>
                    Only <?= $days_left ?> day<?= $days_left > 1 ? 's' : '' ?> left!
                  <?php else: ?>
                    Deadline: <?= date('d M Y', strtotime($s['deadline'])) ?>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>

              <div class="schol-card-footer">
                <a href="register.php" class="btn-apply">
                  Apply Now <i class="fas fa-arrow-right ms-1"></i>
                </a>
                <!-- Expandable detail toggle -->
                <button class="btn-details border-0 bg-transparent" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#detail-<?= $s['scholarship_id'] ?>">
                  More info
                </button>
              </div>

              <!-- Collapsible detail panel -->
              <div class="collapse" id="detail-<?= $s['scholarship_id'] ?>">
                <div class="px-4 py-3 border-top" style="background:var(--blue-50);font-size:.85rem">
                  <?php if ($s['category']): ?>
                  <p class="mb-1"><strong>Category:</strong> <?= htmlspecialchars($s['category']) ?></p>
                  <?php endif; ?>
                  <?php if ($s['description']): ?>
                  <p class="mb-0"><strong>Details:</strong> <?= nl2br(htmlspecialchars($s['description'])) ?></p>
                  <?php endif; ?>
                </div>
              </div>

            </div><!-- /schol-card -->
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <!-- Empty state -->
        <div class="col-12 text-center py-5">
          <div style="font-size:4rem;margin-bottom:1rem">🎓</div>
          <h4 style="color:var(--muted)">No scholarships available right now</h4>
          <p style="color:var(--muted)">Check back soon — new opportunities are added regularly.</p>
          <a href="index.php" class="btn-primary-custom mt-2">Back to Home</a>
        </div>
      <?php endif; ?>
    </div><!-- /row -->

  </div>
</section>

<?php include 'includes/footer.php'; ?>
