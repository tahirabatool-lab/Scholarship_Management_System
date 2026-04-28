        </div>
        <!-- END page-body -->

    </div>
    <!-- END main-content -->

</div>
<!-- END dash-layout -->


<!-- Bootstrap 5 JavaScript (needed for responsive behaviour) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================================
// layout_end.php — Shared JavaScript for Student Panel
// ============================================================
// Functions in this file:
//
//   1. toggleSidebar()        — open/close sidebar on mobile
//   2. closeSidebar()         — close sidebar (overlay click)
//   3. toggleProfileDropdown()— open/close topbar dropdown
//   4. askBeforeLogout()      — confirm before logging out
//   5. Auto-dismiss alerts    — fade out .alert-s messages
//   6. File upload preview    — show selected file names
//   7. Close dropdown outside — click anywhere else to close
//   8. ESC key handler        — press ESC to close dropdown
// ============================================================


// ============================================================
// 1. SIDEBAR TOGGLE (mobile hamburger menu)
// Called by the hamburger button in the topbar
// ============================================================
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    if (sidebar.classList.contains('open')) {
        // Already open — close it
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    } else {
        // Closed — open it
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }
}


// ============================================================
// 2. CLOSE SIDEBAR (called when overlay is tapped on mobile)
// ============================================================
function closeSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
}


// ============================================================
// 3. TOPBAR PROFILE DROPDOWN TOGGLE
// Called when user clicks their name/avatar in the topbar
// ============================================================
function toggleProfileDropdown() {
    var wrap    = document.getElementById('profileDropWrap');
    var trigger = document.getElementById('profileTrigger');

    if (wrap.classList.contains('open')) {
        // Dropdown is open — close it
        wrap.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    } else {
        // Dropdown is closed — open it
        wrap.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
    }
}


// ============================================================
// 4. ASK BEFORE LOGOUT
// Called by both the sidebar and topbar logout links.
// Returns true  → browser follows the href (logout happens)
// Returns false → browser stays on current page (cancelled)
// ============================================================
function askBeforeLogout() {
    var answer = window.confirm(
        'Are you sure you want to sign out?\n\n' +
        'You will be returned to the login page.'
    );

    if (!answer) {
        // User clicked Cancel — stop the link from loading logout.php
        return false;
    }

    // User clicked OK — allow the link to load logout.php
    return true;
}


// ============================================================
// 5. AUTO-DISMISS ALERT MESSAGES
// Any element with class .alert-s fades out after 4 seconds.
// Used for success/error messages shown after actions.
// ============================================================
document.querySelectorAll('.alert-s').forEach(function(alertBox) {
    setTimeout(function() {
        alertBox.style.transition = 'opacity 0.4s ease';
        alertBox.style.opacity    = '0';

        // Remove from DOM after fade animation completes
        setTimeout(function() {
            if (alertBox.parentNode) {
                alertBox.parentNode.removeChild(alertBox);
            }
        }, 400);

    }, 4000); // 4000ms = 4 seconds
});


// ============================================================
// 6. FILE UPLOAD DRAG AND DROP + PREVIEW
// Works with any .upload-zone element on the page.
// Shows a preview of selected file names below the zone.
// ============================================================
var uploadZone = document.querySelector('.upload-zone');

if (uploadZone) {
    var fileInput = uploadZone.querySelector('input[type="file"]');

    // Click on zone → trigger file picker
    uploadZone.addEventListener('click', function() {
        if (fileInput) fileInput.click();
    });

    // Dragging file over zone → highlight it
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });

    // File leaves zone → remove highlight
    uploadZone.addEventListener('dragleave', function() {
        uploadZone.classList.remove('drag-over');
    });

    // File dropped on zone → assign to input and show preview
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        if (fileInput && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // When files are selected (by click or drop) → show preview
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            var fileList = document.getElementById('fileList');
            if (!fileList) return;

            fileList.innerHTML = ''; // Clear previous preview

            var files = Array.from(fileInput.files);
            files.forEach(function(file) {
                // Format file size: KB or MB
                var size = file.size > 1024 * 1024
                    ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
                    : (file.size / 1024).toFixed(0) + ' KB';

                // Choose icon based on file type
                var iconClass = file.name.toLowerCase().endsWith('.pdf')
                    ? 'fa-file-pdf'
                    : 'fa-file-image';

                // Build the preview HTML block
                var html =
                    '<div class="uploaded-file">' +
                    '  <div class="file-icon"><i class="fas ' + iconClass + '"></i></div>' +
                    '  <div>' +
                    '    <div class="file-name">' + file.name + '</div>' +
                    '    <div class="file-size">' + size + '</div>' +
                    '  </div>' +
                    '</div>';

                fileList.insertAdjacentHTML('beforeend', html);
            });
        });
    }
}


// ============================================================
// 7. CLOSE DROPDOWN WHEN CLICKING OUTSIDE
// If the user clicks anywhere on the page OUTSIDE the dropdown,
// it closes automatically.
// ============================================================
document.addEventListener('click', function(event) {
    var dropWrap = document.getElementById('profileDropWrap');
    var trigger  = document.getElementById('profileTrigger');

    if (!dropWrap) return; // Safety: element might not exist

    // Check if the click happened inside the dropdown
    var clickedInside = dropWrap.contains(event.target);

    if (!clickedInside) {
        // Click was outside — close dropdown
        dropWrap.classList.remove('open');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }
});


// ============================================================
// 8. CLOSE DROPDOWN WITH ESC KEY
// Pressing the Escape key closes the profile dropdown.
// ============================================================
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        var dropWrap = document.getElementById('profileDropWrap');
        var trigger  = document.getElementById('profileTrigger');
        if (dropWrap) {
            dropWrap.classList.remove('open');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }
    }
});


// ============================================================
// 9. HIGHLIGHT ACTIVE SIDEBAR LINK
// Compares each sidebar link's href to the current page URL.
// If they match, adds the 'active' class.
// (PHP already handles this via $activePage, this is a backup)
// ============================================================
document.querySelectorAll('.sidebar-link').forEach(function(link) {
    if (link.href === window.location.href) {
        link.classList.add('active');
    }
});

</script>
</body>
</html>
