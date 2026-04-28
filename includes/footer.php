<?php
/**
 * includes/footer.php
 * Usage: include 'includes/footer.php'; at bottom of every page.
 */
?>

<!-- ══════════ FOOTER ══════════ -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-5">

      <!-- Brand Col -->
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand">
          <span class="brand-icon">S</span>
          Scholar<span style="color:var(--accent)">PK</span>
        </div>
        <p class="footer-desc">
          Pakistan's most accessible scholarship management platform.
          Connecting deserving students with life-changing opportunities since 2024.
        </p>
        <div class="social-links mt-3">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="col-lg-2 col-md-3 col-6">
        <p class="footer-heading">Navigation</p>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="scholarships.php">Scholarships</a></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>

      <!-- Account -->
      <div class="col-lg-2 col-md-3 col-6">
        <p class="footer-heading">Account</p>
        <ul class="footer-links">
          <li><a href="register.php">Register</a></li>
          <li><a href="login.php">Login</a></li>
          <li><a href="forgot_password.php">Reset Password</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="col-lg-4 col-md-6">
        <p class="footer-heading">Contact Us</p>
        <ul class="footer-links">
          <li>
            <i class="fas fa-map-marker-alt me-2" style="color:var(--accent);width:16px"></i>
            University Road, Karachi, Pakistan
          </li>
          <li>
            <i class="fas fa-envelope me-2" style="color:var(--accent);width:16px"></i>
            <a href="mailto:info@scholarpk.edu.pk">info@scholarpk.edu.pk</a>
          </li>
          <li>
            <i class="fas fa-phone me-2" style="color:var(--accent);width:16px"></i>
            +92 21 1234 5678
          </li>
          <li>
            <i class="fas fa-clock me-2" style="color:var(--accent);width:16px"></i>
            Mon–Fri, 9:00 AM – 5:00 PM PKT
          </li>
        </ul>
      </div>

    </div><!-- /row -->
  </div><!-- /container -->

  <!-- Bottom Bar -->
  <div class="container">
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> ScholarPK. All rights reserved.</p>
      <div class="d-flex gap-3">
        <a href="#" style="font-size:.8rem;color:rgba(255,255,255,.35)">Privacy Policy</a>
        <a href="#" style="font-size:.8rem;color:rgba(255,255,255,.35)">Terms of Use</a>
      </div>
    </div>
  </div>
</footer>
<!-- ══════════ END FOOTER ══════════ -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
</body>
</html>
