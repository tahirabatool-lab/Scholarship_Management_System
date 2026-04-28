<!--
  admin/includes/layout_end.php

  Purpose: Shared closing HTML and JavaScript for the admin panel.
  This file closes the main admin layout opened in
  `admin/includes/layout.php` and registers small client-side
  helpers (sidebar toggle, profile dropdown, auto-dismiss alerts,
  and generic confirm handlers).

  Included by admin pages using:
    include 'includes/layout_end.php';
-->

  </div><!-- /admin-body -->
</div><!-- /admin-main -->
</div><!-- /admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Sidebar toggle (mobile) ──
function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
  document.getElementById('sbOverlay').classList.toggle('show');
}

// ── Profile dropdown ──
function toggleDrop() {
  document.getElementById('dropMenu').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const drop = document.getElementById('profileDrop');
  if (drop && !drop.contains(e.target)) {
    document.getElementById('dropMenu').classList.remove('open');
  }
});

// ── Auto-dismiss alerts ──
document.querySelectorAll('.alert-a').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 4500);
});

// ── Confirm delete ──
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
});

// ── Highlight active sidebar link ──
document.querySelectorAll('.sb-link').forEach(link => {
  if (link.href === window.location.href) link.classList.add('active');
});
</script>
</body>
</html>
