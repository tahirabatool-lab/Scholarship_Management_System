<?php
/**
 * contact.php — Contact Page
 * Stores submitted messages in contact_messages table.
 */
require_once 'db.php';

$pageTitle  = 'Contact Us';
$activePage = 'contact';

$error   = '';
$success = '';

// ── Process form ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim(htmlspecialchars($_POST['name']    ?? ''));
    $email   = trim(htmlspecialchars($_POST['email']   ?? ''));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($message) < 20) {
        $error = "Message is too short. Please provide more detail (at least 20 characters).";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        // Combine subject into message for storage
        $full_message = ($subject ? "[Subject: $subject]\n\n" : '') . $message;
        $stmt->bind_param("sss", $name, $email, $full_message);

        if ($stmt->execute()) {
            $success = "Thank you, $name! Your message has been received. We'll get back to you within 24–48 hours.";
          // Notify all active admins about the new contact message
          $admins = $conn->query("SELECT user_id FROM users WHERE role='admin' AND status='active'");
          if ($admins && $admins->num_rows > 0) {
            $ins = $conn->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)");
            $type = 'info';
            $notify_msg = ($subject ? "[Subject: $subject] \n" : '') . "New contact from $name (<$email>): " . mb_substr($message, 0, 300);
            while ($a = $admins->fetch_assoc()) {
              $uid = (int)$a['user_id'];
              $ins->bind_param("iss", $uid, $notify_msg, $type);
              $ins->execute();
            }
            $ins->close();
          }
        } else {
            $error = "Failed to send your message. Please try again.";
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero">
  <div class="container text-center position-relative" style="z-index:2">
    <span class="section-label" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9)">We'd Love to Hear From You</span>
    <h1 class="mt-2 mb-3">Contact Us</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Contact</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section-pad">
  <div class="container">
    <div class="row g-5">

      <!-- Left: Info -->
      <div class="col-lg-4 fade-up">
        <h2 style="font-size:1.8rem;margin-bottom:.75rem">Get in Touch</h2>
        <p style="color:var(--muted);margin-bottom:2rem;line-height:1.7">
          Have a question about a scholarship, your application, or need help with your account?
          Our team is happy to help.
        </p>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div>
            <h6>Address</h6>
            <p>University Road, Karachi,<br>Sindh, Pakistan</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
          <div>
            <h6>Email</h6>
            <p>info@scholarpk.edu.pk</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-phone"></i></div>
          <div>
            <h6>Phone</h6>
            <p>+92 21 1234 5678</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
          <div>
            <h6>Office Hours</h6>
            <p>Monday – Friday<br>9:00 AM – 5:00 PM PKT</p>
          </div>
        </div>

        <!-- Social -->
        <div class="mt-3">
          <p class="fw-semibold mb-2" style="font-size:.82rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">Follow Us</p>
          <div class="social-links">
            <a href="#" style="background:var(--blue-100);color:var(--blue-600)" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" style="background:var(--blue-100);color:var(--blue-600)" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" style="background:var(--blue-100);color:var(--blue-600)" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
      </div>

      <!-- Right: Form -->
      <div class="col-lg-8 fade-up delay-2">
        <div class="contact-card">
          <h3 style="font-size:1.5rem;margin-bottom:.4rem">Send Us a Message</h3>
          <p style="color:var(--muted);font-size:.9rem;margin-bottom:2rem">We typically respond within 24 to 48 business hours.</p>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible alert-auto-dismiss fade show" role="alert">
              <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible alert-auto-dismiss fade show" role="alert">
              <i class="fas fa-check-circle me-2"></i><?= $success ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php else: ?>

          <form method="POST" action="contact.php" novalidate>
            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label-custom">Your Name <span style="color:#dc2626">*</span></label>
                <input type="text" name="name" class="form-control-custom"
                       placeholder="Muhammad Ahmed"
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                       required>
              </div>

              <div class="col-md-6">
                <label class="form-label-custom">Email Address <span style="color:#dc2626">*</span></label>
                <input type="email" name="email" class="form-control-custom"
                       placeholder="you@example.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       required>
              </div>

              <div class="col-12">
                <label class="form-label-custom">Subject</label>
                <input type="text" name="subject" class="form-control-custom"
                       placeholder="e.g. Question about HEC scholarship"
                       value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
              </div>

              <div class="col-12">
                <label class="form-label-custom">Message <span style="color:#dc2626">*</span></label>
                <textarea name="message" id="message" class="form-control-custom"
                          rows="5" placeholder="Write your message here…" required
                          maxlength="1000"><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                <div class="d-flex justify-content-end mt-1">
                  <span id="charCount" style="font-size:.75rem;color:var(--muted)">0 / 1000</span>
                </div>
              </div>

              <div class="col-12 pt-1">
                <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:.9rem">
                  <i class="fas fa-paper-plane"></i> Send Message
                </button>
              </div>

            </div>
          </form>

          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- FAQ Strip -->
<section class="section-pad-sm bg-soft">
  <div class="container">
    <div class="row justify-content-center text-center mb-4">
      <div class="col-lg-6 fade-up">
        <span class="section-label">Quick Answers</span>
        <h2 style="font-size:1.9rem">Frequently Asked Questions</h2>
      </div>
    </div>

    <div class="row g-3 justify-content-center">
      <div class="col-lg-8 fade-up delay-1">
        <div class="accordion" id="faqAccordion">
          <?php
          $faqs = [
            ['Is ScholarPK free to use?', 'Yes, 100% free. We never charge students for registration, browsing, or applying.'],
            ['How do I apply for a scholarship?', 'Register for a free account, browse available scholarships, and click "Apply Now". Fill in your details and upload required documents.'],
            ['How long does approval take?', 'Timelines vary by scholarship. Most applications are reviewed within 2–4 weeks of the deadline.'],
            ['Can I apply to multiple scholarships?', 'Absolutely. You can apply for as many scholarships as you are eligible for.'],
            ['What documents do I need?', 'Typically: CNIC, matric/intermediate result card, university enrollment letter, and a recent photograph. Each scholarship may have specific requirements.'],
          ];
          foreach ($faqs as $i => [$q, $a]):
          ?>
          <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden" style="box-shadow:var(--shadow-sm)">
            <h2 class="accordion-header" id="faq-h<?= $i ?>">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold"
                      type="button" data-bs-toggle="collapse"
                      data-bs-target="#faq-c<?= $i ?>"
                      style="font-family:'Plus Jakarta Sans',sans-serif;font-size:.92rem">
                <?= $q ?>
              </button>
            </h2>
            <div id="faq-c<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                 data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="font-size:.88rem;color:var(--muted);line-height:1.7">
                <?= $a ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
