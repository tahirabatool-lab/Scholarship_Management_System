<?php
/**
 * about.php — About Page
 */
$pageTitle  = 'About Us';
$activePage = 'about';
include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero">
  <div class="container text-center position-relative" style="z-index:2">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9)">Who We Are</span>
    <h1 class="mt-2 mb-3">About ScholarPK</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">About</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Mission Section -->
<section class="section-pad">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Text -->
      <div class="col-lg-6 fade-up">
        <span class="section-label">Our Mission</span>
        <h2 style="font-size:2.2rem;margin-bottom:1.2rem">
          Making Education Accessible to Every Pakistani Student
        </h2>
        <p style="color:var(--muted);margin-bottom:1.5rem;line-height:1.8">
          ScholarPK was founded with one belief: financial barriers should never stand between a
          talented student and their education. We built a centralized platform where students across
          Pakistan can discover, apply for, and track scholarships — all in one place.
        </p>

        <div class="about-feature">
          <div class="about-feature-icon"><i class="fas fa-bullseye"></i></div>
          <div>
            <h5>Centralized Discovery</h5>
            <p>Hundreds of scholarships from HEC, provincial governments, NGOs, and private donors — all searchable from a single dashboard.</p>
          </div>
        </div>

        <div class="about-feature">
          <div class="about-feature-icon"><i class="fas fa-shield-alt"></i></div>
          <div>
            <h5>Transparent Process</h5>
            <p>Every application status is visible in real time. No guesswork, no waiting in the dark — just clear, honest tracking.</p>
          </div>
        </div>

        <div class="about-feature">
          <div class="about-feature-icon"><i class="fas fa-hands-helping"></i></div>
          <div>
            <h5>Student-First Design</h5>
            <p>Built with students in mind — simple forms, mobile-friendly interface, and support in both English and Urdu.</p>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="col-lg-6 fade-up delay-2">
        <div class="row g-3">
          <div class="col-6">
            <div class="stat-box">
              <div class="num" data-count="500" data-suffix="+">0</div>
              <div class="label">Scholarships Listed</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-box">
              <div class="num" data-count="12000" data-suffix="+">0</div>
              <div class="label">Students Registered</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-box">
              <div class="num" data-count="3200" data-suffix="+">0</div>
              <div class="label">Applications Approved</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-box">
              <div class="num" data-count="75" data-suffix="M+">0</div>
              <div class="label">PKR Disbursed</div>
            </div>
          </div>
        </div>

        <!-- Quote -->
        <div class="mt-4 p-4 rounded-3" style="background:var(--blue-50);border-left:4px solid var(--blue-500)">
          <p style="font-family:'Fraunces',serif;font-size:1.1rem;color:var(--ink);font-style:italic;margin-bottom:.75rem">
            "Education is not preparation for life; education is life itself."
          </p>
          <p style="font-size:.82rem;color:var(--muted);margin:0">— John Dewey</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- How It Works -->
<section class="section-pad bg-soft">
  <div class="container">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-6 fade-up">
        <span class="section-label">Simple Steps</span>
        <h2 style="font-size:2.1rem">How ScholarPK Works</h2>
      </div>
    </div>

    <div class="row g-4 justify-content-center">
      <?php
      $steps = [
        ['01', 'fas fa-user-plus',    'Create Account',     'Register for free with your email. Students only — takes under 2 minutes.',     'var(--blue-500)', 'var(--blue-100)'],
        ['02', 'fas fa-search',       'Browse & Filter',    'Search scholarships by level, type, GPA, and deadline. Save your favorites.',     '#8b5cf6',         '#f5f3ff'],
        ['03', 'fas fa-file-upload',  'Submit Application', 'Fill the form, upload your documents (CNIC, transcripts), and submit.',           '#059669',         '#ecfdf5'],
        ['04', 'fas fa-check-circle', 'Get Approved',       'Track your status in real time. Get notified when approved and disbursed.',       '#d97706',         '#fffbeb'],
      ];
      foreach ($steps as $i => [$num, $icon, $title, $desc, $color, $bg]):
      ?>
      <div class="col-lg-3 col-md-6 fade-up" style="transition-delay:<?= $i * .1 ?>s">
        <div class="text-center p-4 rounded-4 h-100" style="background:var(--white);border:1.5px solid var(--border);transition:all .25s" onmouseover="this.style.borderColor='<?= $color ?>'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="width:64px;height:64px;background:<?= $bg ?>;border-radius:16px;display:grid;place-items:center;margin:0 auto 1.2rem;font-size:1.4rem;color:<?= $color ?>">
            <i class="<?= $icon ?>"></i>
          </div>
          <div style="font-family:'Fraunces',serif;font-size:2.5rem;font-weight:800;color:<?= $color ?>;opacity:.2;line-height:1;margin-bottom:.3rem"><?= $num ?></div>
          <h4 style="font-size:1rem;margin-bottom:.5rem"><?= $title ?></h4>
          <p style="font-size:.86rem;color:var(--muted);margin:0;line-height:1.6"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- Our Story / Timeline -->
<section class="section-pad">
  <div class="container">
    <div class="row">
      <div class="col-lg-5 fade-up">
        <span class="section-label">Our Journey</span>
        <h2 style="font-size:2.1rem;margin-bottom:2rem">Building a Better Future, Step by Step</h2>

        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-year">2022</div>
            <h6>The Idea</h6>
            <p>A group of university students frustrated by the fragmented scholarship landscape in Pakistan decided to build a solution.</p>
          </div>
          <div class="timeline-item">
            <div class="timeline-year">2023</div>
            <h6>First Version Launched</h6>
            <p>ScholarPK beta went live with 50 scholarships and 500 registered students within the first month.</p>
          </div>
          <div class="timeline-item">
            <div class="timeline-year">2024</div>
            <h6>Growth & Partnerships</h6>
            <p>Partnerships with HEC, Punjab and Sindh Education Departments. 5,000+ students registered.</p>
          </div>
          <div class="timeline-item">
            <div class="timeline-year">2025</div>
            <h6>National Expansion</h6>
            <p>Covering all provinces. 500+ active scholarships. Mobile app in development.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6 offset-lg-1 fade-up delay-2">
        <div class="row g-4 mt-lg-5">
          <?php
          $values = [
            ['🌍', 'Accessibility',  'Every student, regardless of location or income, deserves access to scholarship opportunities.'],
            ['🔍', 'Transparency',   'No hidden criteria. Every scholarship requirement is clearly listed for you to evaluate.'],
            ['⚡', 'Efficiency',     'We respect your time. Simple forms, quick uploads, fast decisions.'],
            ['🤝', 'Integrity',      'We never charge students. ScholarPK is 100% free for applicants.'],
          ];
          foreach ($values as [$emoji, $title, $desc]):
          ?>
          <div class="col-md-6">
            <div class="p-4 rounded-3 h-100" style="background:var(--blue-50);border:1px solid var(--blue-100)">
              <div style="font-size:2rem;margin-bottom:.75rem"><?= $emoji ?></div>
              <h5 style="font-size:.95rem;margin-bottom:.4rem"><?= $title ?></h5>
              <p style="font-size:.84rem;color:var(--muted);margin:0;line-height:1.6"><?= $desc ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- CTA -->
<section class="section-pad-sm">
  <div class="container">
    <div class="row justify-content-center text-center fade-up">
      <div class="col-lg-7">
        <h2 style="font-size:2rem;margin-bottom:.75rem">Have Questions?</h2>
        <p style="color:var(--muted);margin-bottom:2rem">Our team is here to help you navigate every step of the process.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          <a href="contact.php"       class="btn-primary-custom"><i class="fas fa-envelope"></i> Get in Touch</a>
          <a href="scholarships.php"  class="btn-primary-custom" style="background:transparent;border-color:var(--blue-300);color:var(--blue-600)">
            <i class="fas fa-search"></i> Browse Scholarships
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
