<?php
/**
 * index.php — Home Page
 * Sections: Hero, Stats, Featured Scholarships, Benefits, CTA
 */
require_once 'db.php';

$pageTitle  = 'Home';
$activePage = 'home';
include 'includes/header.php';

// Fetch 6 active featured scholarships
$featured = $conn->query("
    SELECT scholarship_id, title, provider, type, level, amount, total_seats, deadline, category
    FROM scholarships
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 6
");

// Total counts for stats section
$totalScholarships = $conn->query("SELECT COUNT(*) as c FROM scholarships WHERE status='active'")->fetch_assoc()['c'] ?? 0;
$totalStudents     = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;
$totalDisbursed    = $conn->query("SELECT COUNT(*) as c FROM payments WHERE payment_status='paid'")->fetch_assoc()['c'] ?? 0;
?>

<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-blob hero-blob-1"></div>
  <div class="hero-blob hero-blob-2"></div>

  <div class="container">
    <div class="row align-items-center g-5 py-5">

      <!-- Left: Text -->
      <div class="col-lg-7 hero-content">
        <div class="hero-eyebrow">
          <span class="dot"></span>
          Pakistan's #1 Scholarship Platform
        </div>

        <h1>
          Your Future Starts<br>
          with the <span class="highlight">Right Scholarship</span>
        </h1>

        <p class="hero-desc">
          Discover hundreds of merit, need-based, and talent scholarships
          for Matric, Intermediate, and University students across Pakistan.
          Apply in minutes — no complicated paperwork.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <a href="scholarships.php" class="btn-accent">
            <i class="fas fa-search"></i> Browse Scholarships
          </a>
          <a href="register.php" class="btn-outline-custom">
            <i class="fas fa-user-plus"></i> Create Free Account
          </a>
        </div>

        <!-- Stats -->
        <div class="hero-stats">
          <div>
            <div class="hero-stat-num" data-count="<?= $totalScholarships ?>" data-suffix="+">0</div>
            <div class="hero-stat-label">Active Scholarships</div>
          </div>
          <div>
            <div class="hero-stat-num" data-count="<?= $totalStudents ?>" data-suffix="+">0</div>
            <div class="hero-stat-label">Registered Students</div>
          </div>
          <div>
            <div class="hero-stat-num" data-count="<?= $totalDisbursed ?>" data-suffix="+">0</div>
            <div class="hero-stat-label">Scholarships Awarded</div>
          </div>
        </div>
      </div>

      <!-- Right: Info cards -->
      <div class="col-lg-5 col-md-8 mx-auto">
        <div class="hero-card fade-up delay-1">
          <div class="card-icon">🎓</div>
          <h5>Merit-Based Awards</h5>
          <p>Top grades earn top funding. GPA-based scholarships for high achievers at every level.</p>
        </div>
        <div class="hero-card fade-up delay-2">
          <div class="card-icon">💚</div>
          <h5>Need-Based Support</h5>
          <p>Financial hardship shouldn't stop your education. Apply for income-based support today.</p>
        </div>
        <div class="hero-card fade-up delay-3">
          <div class="card-icon">⚡</div>
          <h5>Talent Scholarships</h5>
          <p>Sports, arts, STEM — special abilities deserve special recognition and funding.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     FEATURED SCHOLARSHIPS
══════════════════════════════════════════ -->
<section class="section-pad">
  <div class="container">

    <!-- Heading -->
    <div class="row justify-content-between align-items-end mb-5">
      <div class="col-lg-6 fade-up">
        <span class="section-label">Open Now</span>
        <h2 style="font-size:2.2rem">Featured Scholarships</h2>
        <p class="lead-text">Recently added and actively accepting applications. Don't miss these opportunities.</p>
      </div>
      <div class="col-auto fade-up delay-1">
        <a href="scholarships.php" class="btn-primary-custom">
          View All <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- Cards Grid -->
    <div class="row g-4">
      <?php if ($featured && $featured->num_rows > 0): ?>
        <?php while ($s = $featured->fetch_assoc()): ?>
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
          <div class="col-lg-4 col-md-6 fade-up">
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

                <div class="schol-meta">
                  <?php if ($s['level']): ?>
                  <span class="schol-meta-item">
                    <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($s['level']) ?>
                  </span>
                  <?php endif; ?>
                  <?php if ($s['total_seats']): ?>
                  <span class="schol-meta-item">
                    <i class="fas fa-users"></i> <?= $s['total_seats'] ?> seats
                  </span>
                  <?php endif; ?>
                  <?php if ($s['category']): ?>
                  <span class="schol-meta-item">
                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($s['category']) ?>
                  </span>
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
                    <?= $days_left ?> day<?= $days_left > 1 ? 's' : '' ?> left — Apply now!
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
                <a href="scholarships.php?id=<?= $s['scholarship_id'] ?>" class="btn-details">
                  Details
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <!-- Placeholder cards if no data yet -->
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="col-lg-4 col-md-6 fade-up">
          <div class="schol-card">
            <div class="schol-card-header">
              <span class="schol-badge badge-merit"><i class="fas fa-tag"></i> Merit</span>
              <div class="schol-amount">Rs. 50,000 <small>Undergraduate Level</small></div>
            </div>
            <div class="schol-card-body">
              <h3 class="schol-title">HEC Need Based Scholarship 2025</h3>
              <p class="schol-provider"><i class="fas fa-building" style="color:var(--blue-400)"></i> Higher Education Commission</p>
              <div class="schol-meta">
                <span class="schol-meta-item"><i class="fas fa-graduation-cap"></i> Undergraduate</span>
                <span class="schol-meta-item"><i class="fas fa-users"></i> 500 seats</span>
              </div>
              <div class="schol-deadline">
                <i class="fas fa-clock"></i> Deadline: 31 Dec 2025
              </div>
            </div>
            <div class="schol-card-footer">
              <a href="register.php" class="btn-apply">Apply Now <i class="fas fa-arrow-right ms-1"></i></a>
              <a href="scholarships.php" class="btn-details">Details</a>
            </div>
          </div>
        </div>
        <?php endfor; ?>
      <?php endif; ?>
    </div><!-- /row -->

  </div>
</section>

<!-- ══════════════════════════════════════════
     BENEFITS SECTION
══════════════════════════════════════════ -->
<section class="section-pad bg-soft">
  <div class="container">

    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-6 fade-up">
        <span class="section-label">Why ScholarPK</span>
        <h2 style="font-size:2.2rem">Everything You Need to Succeed</h2>
        <p class="lead-text mx-auto">From discovery to disbursement, we simplify every step of the scholarship journey.</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-3 col-md-6 fade-up delay-1">
        <div class="benefit-card">
          <div class="benefit-icon icon-blue">🔍</div>
          <h4>Smart Search</h4>
          <p>Filter scholarships by level, type, amount, and deadline. Find exactly what fits your profile.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 fade-up delay-2">
        <div class="benefit-card">
          <div class="benefit-icon icon-amber">⚡</div>
          <h4>Quick Apply</h4>
          <p>Submit your application in under 10 minutes. Upload documents, fill details, done.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 fade-up delay-3">
        <div class="benefit-card">
          <div class="benefit-icon icon-green">📊</div>
          <h4>Real-time Tracking</h4>
          <p>Log in anytime to see your application status — pending, under review, approved, or disbursed.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 fade-up delay-4">
        <div class="benefit-card">
          <div class="benefit-icon icon-purple">🔔</div>
          <h4>Smart Alerts</h4>
          <p>Get notified about deadline reminders, status changes, and new scholarships that match you.</p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════════
     CATEGORIES STRIP
══════════════════════════════════════════ -->
<section class="section-pad-sm">
  <div class="container">
    <div class="row justify-content-center text-center mb-4">
      <div class="col fade-up">
        <span class="section-label">Browse by Level</span>
        <h2 style="font-size:1.9rem">Find Scholarships for Your Stage</h2>
      </div>
    </div>

    <div class="row g-3 justify-content-center">
      <?php
      $levels = [
        ['Matric',          'fas fa-school',          'var(--blue-500)',   'var(--blue-50)'],
        ['Intermediate',    'fas fa-book-open',        '#8b5cf6',          '#f5f3ff'],
        ['Undergraduate',   'fas fa-graduation-cap',   '#059669',          '#ecfdf5'],
        ['Postgraduate',    'fas fa-user-graduate',    '#d97706',          '#fffbeb'],
      ];
      foreach ($levels as [$name, $icon, $color, $bg]):
        $count = $conn->query("SELECT COUNT(*) as c FROM scholarships WHERE status='active' AND level='$name'")->fetch_assoc()['c'] ?? 0;
      ?>
      <div class="col-lg-3 col-md-6 fade-up">
        <a href="scholarships.php?level=<?= urlencode($name) ?>"
           class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
           style="background:<?= $bg ?>;border:1.5px solid transparent;transition:all .25s"
           onmouseover="this.style.borderColor='<?= $color ?>'"
           onmouseout="this.style.borderColor='transparent'">
          <div style="width:48px;height:48px;background:<?= $color ?>;border-radius:12px;display:grid;place-items:center;color:#fff;font-size:1.1rem;flex-shrink:0">
            <i class="<?= $icon ?>"></i>
          </div>
          <div>
            <div style="font-weight:700;color:var(--ink);font-size:.95rem"><?= $name ?></div>
            <div style="font-size:.8rem;color:var(--muted)"><?= $count ?> open</div>
          </div>
          <i class="fas fa-chevron-right ms-auto" style="color:<?= $color ?>;font-size:.75rem"></i>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     CTA BANNER
══════════════════════════════════════════ -->
<section class="section-pad-sm">
  <div class="container">
    <div class="row">
      <div class="col-12 fade-up">
        <div class="rounded-4 p-5 text-center position-relative overflow-hidden"
             style="background:linear-gradient(135deg,var(--blue-900),var(--blue-600))">
          <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%"></div>
          <div style="position:absolute;bottom:-40px;left:5%;width:150px;height:150px;background:rgba(245,158,11,.1);border-radius:50%"></div>
          <div style="position:relative;z-index:1">
            <h2 style="color:#fff;font-size:2rem;margin-bottom:.75rem">Ready to Fund Your Future?</h2>
            <p style="color:rgba(255,255,255,.7);max-width:480px;margin:0 auto 2rem;font-size:1rem">
              Join thousands of Pakistani students who've already discovered and applied for scholarships through ScholarPK.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
              <a href="register.php" class="btn-accent">
                <i class="fas fa-rocket"></i> Start for Free
              </a>
              <a href="scholarships.php" class="btn-outline-custom">
                <i class="fas fa-search"></i> Browse Scholarships
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
