/**
 * main.js — Public Website JavaScript
 * Scholarship Management System
 */

document.addEventListener('DOMContentLoaded', () => {

  // ─────────────────────────────────────────
  // 1. NAVBAR: scroll effect
  // ─────────────────────────────────────────
  const nav = document.querySelector('.site-nav');
  if (nav) {
    const onScroll = () => {
      nav.classList.toggle('scrolled', window.scrollY > 60);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run on load
  }

  // ─────────────────────────────────────────
  // 2. NAVBAR: highlight active page link
  // ─────────────────────────────────────────
  const navLinks = document.querySelectorAll('.site-nav .nav-link:not(.nav-cta)');
  const currentPage = window.location.pathname.split('/').pop() || 'index.php';
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.includes(currentPage)) {
      link.classList.add('active');
    }
  });

  // ─────────────────────────────────────────
  // 3. SCROLL REVEAL (fade-up elements)
  // ─────────────────────────────────────────
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

  // ─────────────────────────────────────────
  // 4. COUNTER ANIMATION (for stat numbers)
  // ─────────────────────────────────────────
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const countObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          countObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    counters.forEach(el => countObserver.observe(el));
  }

  function animateCounter(el) {
    const target   = parseInt(el.dataset.count);
    const suffix   = el.dataset.suffix || '';
    const duration = 1800;
    const start    = performance.now();

    function update(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(eased * target).toLocaleString() + suffix;
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  }

  // ─────────────────────────────────────────
  // 5. PASSWORD VISIBILITY TOGGLE
  // ─────────────────────────────────────────
  document.querySelectorAll('.toggle-icon').forEach(icon => {
    icon.addEventListener('click', () => {
      const input = icon.closest('.password-toggle').querySelector('input');
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      icon.classList.toggle('fa-eye',      isText);
      icon.classList.toggle('fa-eye-slash', !isText);
    });
  });

  // ─────────────────────────────────────────
  // 6. PASSWORD STRENGTH METER
  // ─────────────────────────────────────────
  const pwInput = document.getElementById('password');
  if (pwInput) {
    pwInput.addEventListener('input', () => {
      const val = pwInput.value;
      const bars = document.querySelectorAll('.strength-bar');
      if (!bars.length) return;

      let strength = 0;
      if (val.length >= 8)              strength++;
      if (/[A-Z]/.test(val))            strength++;
      if (/[0-9]/.test(val))            strength++;
      if (/[^A-Za-z0-9]/.test(val))     strength++;

      bars.forEach((bar, i) => {
        bar.classList.remove('weak', 'medium', 'strong');
        if (i < strength) {
          bar.classList.add(strength <= 1 ? 'weak' : strength <= 3 ? 'medium' : 'strong');
        }
      });
    });
  }

  // ─────────────────────────────────────────
  // 7. SCHOLARSHIP FILTER (scholarships.php)
  // ─────────────────────────────────────────
  const searchInput  = document.getElementById('searchInput');
  const filterType   = document.getElementById('filterType');
  const filterLevel  = document.getElementById('filterLevel');
  const scholarCards = document.querySelectorAll('.schol-filter-item');

  function filterScholarships() {
    const q     = (searchInput?.value || '').toLowerCase();
    const type  = (filterType?.value  || '').toLowerCase();
    const level = (filterLevel?.value || '').toLowerCase();
    let visible = 0;

    scholarCards.forEach(card => {
      const text   = card.dataset.title?.toLowerCase()    || '';
      const cType  = card.dataset.type?.toLowerCase()     || '';
      const cLevel = card.dataset.level?.toLowerCase()    || '';

      const match = (!q     || text.includes(q))
                 && (!type  || cType  === type)
                 && (!level || cLevel === level);

      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    // Update result count
    const countEl = document.getElementById('resultCount');
    if (countEl) countEl.textContent = visible + ' scholarship' + (visible !== 1 ? 's' : '');
  }

  if (searchInput) searchInput.addEventListener('input', filterScholarships);
  if (filterType)  filterType.addEventListener('change', filterScholarships);
  if (filterLevel) filterLevel.addEventListener('change', filterScholarships);

  // ─────────────────────────────────────────
  // 8. CONTACT FORM: character count
  // ─────────────────────────────────────────
  const msgArea = document.getElementById('message');
  const charCount = document.getElementById('charCount');
  if (msgArea && charCount) {
    msgArea.addEventListener('input', () => {
      charCount.textContent = msgArea.value.length + ' / 1000';
    });
  }

  // ─────────────────────────────────────────
  // 9. SMOOTH SCROLL for anchor links
  // ─────────────────────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ─────────────────────────────────────────
  // 10. Auto-dismiss Bootstrap alerts
  // ─────────────────────────────────────────
  document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });

});
