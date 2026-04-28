<?php
/**
 * student/apply.php
 *
 * Scholarship application form for logged-in students. Key responsibilities:
 *  - Load the requested scholarship by `id`
 *  - Prevent duplicate applications by the same student
 *  - Validate form inputs and handle multi-file uploads
 *  - Insert application record, save file paths, and notify admins
 *
 * URL: apply.php?id=<scholarship_id>
 */

// Load database connection ($conn)
require_once '../db.php';

// Load auth helpers (require_login, require_role, clean)
require_once '../auth_helper.php';

// Must be logged in as a student — redirect otherwise
require_login();
require_role('student');

// ============================================================
// STEP 1 — GET THE SCHOLARSHIP ID FROM URL
// ============================================================
// (int) converts to integer — prevents SQL injection from URL
$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID given, send back to scholarships list
if ($scholarship_id <= 0) {
    header("Location: scholarships.php?error=" . urlencode("No scholarship selected."));
    exit();
}

// ============================================================
// STEP 2 — LOAD SCHOLARSHIP DETAILS FROM DATABASE
// ============================================================
// We need: title, amount, deadline, level, type, eligibility
// Only show active scholarships (status = 'active')
$schol_stmt = $conn->prepare(
    "SELECT scholarship_id, title, provider, type, level, amount,
            total_seats, deadline, start_date, eligibility_criteria, description,
            min_gpa, max_age, gender_requirement
     FROM scholarships
     WHERE scholarship_id = ? AND status = 'active'
     LIMIT 1"
);
$schol_stmt->bind_param("i", $scholarship_id);
$schol_stmt->execute();
$scholarship = $schol_stmt->get_result()->fetch_assoc();

// If scholarship not found or not active, go back to list
if (!$scholarship) {
    header("Location: scholarships.php?error=" . urlencode("Scholarship not found or is no longer accepting applications."));
    exit();
}

// ============================================================
// STEP 3 — CHECK FOR DUPLICATE APPLICATION
// ============================================================
// Each student can only apply once per scholarship.
// The database also has a UNIQUE KEY on (user_id, scholarship_id)
// but we check here too to show a friendly message.
$user_id = $_SESSION['user_id'];

$dup_stmt = $conn->prepare(
    "SELECT application_id FROM applications
     WHERE user_id = ? AND scholarship_id = ?
     LIMIT 1"
);
$dup_stmt->bind_param("ii", $user_id, $scholarship_id);
$dup_stmt->execute();
$existing_application = $dup_stmt->get_result()->fetch_assoc();
$already_applied = ($existing_application !== null);

// ============================================================
// STEP 4 — LOAD STUDENT'S OWN INFO TO PRE-FILL THE FORM
// ============================================================
$student_stmt = $conn->prepare(
    "SELECT full_name, email, phone FROM users WHERE user_id = ? LIMIT 1"
);
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

// ============================================================
// UPLOAD SETTINGS
// ============================================================
// Where to save files on the server
define('UPLOAD_FOLDER', '../uploads/documents/');

// Allowed file extensions
define('ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Maximum file size: 5 MB (in bytes)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Create the upload folder if it doesn't exist yet
if (!is_dir(UPLOAD_FOLDER)) {
    mkdir(UPLOAD_FOLDER, 0755, true);
}

// ============================================================
// VARIABLES FOR MESSAGES AND STICKY FORM VALUES
// ============================================================
$errors         = [];   // Array of error messages to show
$success_msg    = '';   // Success message after submission
$upload_warnings = [];  // Warnings about individual file issues

// ============================================================
// STEP 5 — PROCESS THE FORM ON POST SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {

    // ----------------------------------------------------------
    // 5a. COLLECT AND SANITIZE FORM INPUTS
    // clean() strips HTML tags + trims whitespace
    // ----------------------------------------------------------
    $father_name         = clean($_POST['father_name']         ?? '');
    $cnic                = clean($_POST['cnic']                ?? '');
    $date_of_birth       = clean($_POST['date_of_birth']       ?? '');
    $gender              = clean($_POST['gender']              ?? '');
    $address             = clean($_POST['address']             ?? '');
    $matric_marks        = clean($_POST['matric_marks']        ?? '');
    $intermediate_marks  = clean($_POST['intermediate_marks']  ?? '');
    $university          = clean($_POST['university']          ?? '');

    // ----------------------------------------------------------
    // 5b. VALIDATE REQUIRED FIELDS
    // ----------------------------------------------------------
    if (empty($father_name)) {
        $errors[] = "Father's name is required.";
    }

    if (empty($cnic)) {
        $errors[] = "CNIC / B-Form number is required.";
    } elseif (!preg_match('/^\d{5}-\d{7}-\d$/', $cnic)) {
        // CNIC format must be: 42101-1234567-1
        $errors[] = "CNIC format must be: 42101-1234567-1";
    }

    if (empty($date_of_birth)) {
        $errors[] = "Date of birth is required.";
    }

    if (empty($gender)) {
        $errors[] = "Please select your gender.";
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Invalid gender selection.";
    }

    if (empty($address)) {
        $errors[] = "Current address is required.";
    }

    // ----------------------------------------------------------
    // 5c. IF NO ERRORS — SAVE APPLICATION TO DATABASE
    // ----------------------------------------------------------
    if (empty($errors)) {

        // Insert the application record
        // Status starts as 'pending' — admin will review later
        $app_stmt = $conn->prepare(
            "INSERT INTO applications
                (user_id, scholarship_id, father_name, cnic, date_of_birth,
                 gender, address, matric_marks, intermediate_marks, university, status)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );

        // Bind all 10 parameters
        // i = integer, s = string
        $app_stmt->bind_param(
            "iissssssss",
            $user_id,
            $scholarship_id,
            $father_name,
            $cnic,
            $date_of_birth,
            $gender,
            $address,
            $matric_marks,
            $intermediate_marks,
            $university
        );

        if ($app_stmt->execute()) {
            // Get the new application's ID (we need it for document links)
            $new_app_id = $conn->insert_id;

            // ------------------------------------------------------
            // 5d. HANDLE DOCUMENT UPLOADS
            // ------------------------------------------------------
            // $_FILES['documents'] is an array because the input uses name="documents[]"
            // We loop through each uploaded file and process it individually

            if (
                isset($_FILES['documents']) &&
                !empty($_FILES['documents']['name'][0])
            ) {
                $file_count = count($_FILES['documents']['name']);

                for ($i = 0; $i < $file_count; $i++) {

                    // Skip if this slot had no file or an upload error
                    if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
                        if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                            $upload_warnings[] = "File #" . ($i + 1) . " failed to upload. Try again.";
                        }
                        continue;
                    }

                    $original_name = basename($_FILES['documents']['name'][$i]);
                    $temp_path     = $_FILES['documents']['tmp_name'][$i];
                    $file_size     = $_FILES['documents']['size'][$i];

                    // Get the file extension (e.g. "pdf", "jpg")
                    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                    // ── Validate file type ──
                    if (!in_array($extension, ALLOWED_TYPES)) {
                        $upload_warnings[] = '"' . htmlspecialchars($original_name) . '" skipped — only PDF, JPG, PNG allowed.';
                        continue;
                    }

                    // ── Validate file size ──
                    if ($file_size > MAX_FILE_SIZE) {
                        $upload_warnings[] = '"' . htmlspecialchars($original_name) . '" skipped — exceeds 5 MB limit.';
                        continue;
                    }

                    // ── Extra security: verify it's actually an image/pdf ──
                    // mime_content_type reads the actual file bytes, not just the extension
                    $mime = mime_content_type($temp_path);
                    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                    if (!in_array($mime, $allowed_mimes)) {
                        $upload_warnings[] = '"' . htmlspecialchars($original_name) . '" skipped — file content does not match its extension.';
                        continue;
                    }

                    // ── Build a unique filename ──
                    // Format: doc_APPID_TIMESTAMP_INDEX.ext
                    // Example: doc_42_1712345678_0.pdf
                    $safe_name  = 'doc_' . $new_app_id . '_' . time() . '_' . $i . '.' . $extension;
                    $save_path  = UPLOAD_FOLDER . $safe_name;

                    // ── The relative path we store in the database ──
                    // This is relative to the project root (not the student/ folder)
                    $db_path = 'uploads/documents/' . $safe_name;

                    // ── Move the file from temp location to our uploads folder ──
                    if (move_uploaded_file($temp_path, $save_path)) {

                        // Save the document record in application_documents table
                        $doc_stmt = $conn->prepare(
                            "INSERT INTO application_documents
                                (application_id, document_name, file_path, file_type)
                             VALUES (?, ?, ?, ?)"
                        );
                        $doc_stmt->bind_param("isss", $new_app_id, $original_name, $db_path, $mime);
                        $doc_stmt->execute();

                    } else {
                        $upload_warnings[] = '"' . htmlspecialchars($original_name) . '" could not be saved. Check server permissions.';
                    }

                } // end file loop
            }

            // ------------------------------------------------------
            // 5e. SEND A NOTIFICATION TO THE STUDENT
            // ------------------------------------------------------
            $schol_title  = $conn->real_escape_string($scholarship['title']);
            $notif_message = "Your application for '{$schol_title}' has been submitted and is under review.";

            $notif_stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, message, type)
                 VALUES (?, ?, 'application')"
            );
            $notif_stmt->bind_param("is", $user_id, $notif_message);
            $notif_stmt->execute();

            // ------------------------------------------------------
            // 5f. LOG THE ACTIVITY
            // ------------------------------------------------------
            $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $log_message = "Applied for scholarship: {$scholarship['title']}";

            $log_stmt = $conn->prepare(
                "INSERT INTO activity_logs
                    (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, 'APPLY', 'applications', ?, ?, ?)"
            );
            $log_stmt->bind_param("iiss", $user_id, $new_app_id, $log_message, $ip);
            $log_stmt->execute();

            // ------------------------------------------------------
            // 5g. REDIRECT TO MY APPLICATIONS PAGE
            // ------------------------------------------------------
            // Build a success message for the redirect
            $msg = "Application submitted successfully for '{$scholarship['title']}'!";
            $redirect_url = "applications.php?success=" . urlencode($msg);

            // Add upload warnings to URL if any files had issues
            if (!empty($upload_warnings)) {
                $warn_text = "Some files were skipped: " . implode(" | ", $upload_warnings);
                $redirect_url .= "&warning=" . urlencode($warn_text);
            }

            header("Location: " . $redirect_url);
            exit();

        } else {
            // Database insert failed
            // Check if it's a duplicate application at DB level
            if ($conn->errno === 1062) {
                $errors[] = "You have already applied for this scholarship.";
                $already_applied = true;
            } else {
                $errors[] = "Could not save your application. Please try again. (DB Error: " . $conn->error . ")";
            }
        }

    } // end if no errors

} // end POST processing


// ============================================================
// RENDER THE PAGE
// ============================================================
$pageTitle  = 'Apply: ' . htmlspecialchars($scholarship['title']);
$activePage = 'scholarships';
include 'includes/layout.php';
?>

<!-- ============================================================
     PAGE HEADER
============================================================ -->
<div class="page-header" style="margin-bottom: 1.75rem;">
    <h1 style="font-size: 1.5rem; margin-bottom: .25rem;">
        Apply for Scholarship
    </h1>
    <div class="breadcrumb" style="font-size: .8rem; color: var(--muted);">
        <a href="dashboard.php" style="color: var(--muted);">Dashboard</a>
        <span style="margin: 0 .4rem; opacity: .4;">›</span>
        <a href="scholarships.php" style="color: var(--muted);">Scholarships</a>
        <span style="margin: 0 .4rem; opacity: .4;">›</span>
        Apply
    </div>
</div>

<!-- ============================================================
     ALREADY APPLIED — show message and hide form
============================================================ -->
<?php if ($already_applied): ?>
<div class="alert-s alert-s-warn" style="display:flex;align-items:flex-start;gap:.65rem;
     padding:.85rem 1.1rem;border-radius:var(--radius);font-size:.88rem;margin-bottom:1.5rem;
     background:#fef9c3;color:#92400e;border:1px solid #fde68a;">
    <i class="fas fa-exclamation-triangle" style="margin-top:.15rem;flex-shrink:0;"></i>
    <div>
        <strong>Already Applied!</strong>
        You have already submitted an application for
        <strong><?= htmlspecialchars($scholarship['title']) ?></strong>.
        <br>
        <a href="applications.php"
           style="color:#92400e;font-weight:700;text-decoration:underline;margin-top:.35rem;display:inline-block;">
            View your application status →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     VALIDATION ERROR LIST
============================================================ -->
<?php if (!empty($errors)): ?>
<div style="padding:.85rem 1.1rem;border-radius:var(--radius);font-size:.88rem;margin-bottom:1.5rem;
     background:var(--red-lt);color:#991b1b;border:1px solid #fecaca;">
    <div style="font-weight:700;margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;">
        <i class="fas fa-times-circle"></i> Please fix the following errors:
    </div>
    <ul style="margin:0;padding-left:1.4rem;">
        <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- ============================================================
     TWO COLUMN LAYOUT: Left = scholarship info | Right = form
============================================================ -->
<div class="row g-4">

    <!-- ══════════════════════════════
         LEFT COLUMN — Scholarship Info
         Sticky so it stays visible while scrolling the form
    ══════════════════════════════ -->
    <div class="col-lg-4">
        <div class="panel" style="position: sticky; top: 80px;">

            <!-- Coloured header band -->
            <div style="background: linear-gradient(135deg, var(--primary-dk), var(--primary));
                        padding: 1.4rem; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div style="font-size:.68rem;color:rgba(255,255,255,.55);
                            text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;">
                    Applying for
                </div>
                <h3 style="color:#fff;font-family:'Lora',serif;font-size:1.05rem;
                           margin-bottom:.4rem;line-height:1.35;">
                    <?= htmlspecialchars($scholarship['title']) ?>
                </h3>
                <?php if ($scholarship['provider']): ?>
                <div style="font-size:.8rem;color:rgba(255,255,255,.6);">
                    <i class="fas fa-building me-1"></i>
                    <?= htmlspecialchars($scholarship['provider']) ?>
                </div>
                <?php endif; ?>
                <div style="font-family:'Lora',serif;font-size:1.5rem;font-weight:700;
                            color:#fff;margin-top:.75rem;">
                    <?= $scholarship['amount']
                        ? 'Rs. ' . number_format($scholarship['amount'])
                        : 'Amount Varies' ?>
                </div>
            </div>

            <!-- Details list -->
            <div style="padding: 1.1rem;">
                <?php
                // Build key-value rows for scholarship details
                $details = [
                    ['Level',    $scholarship['level']    ?? '—',  'fa-graduation-cap'],
                    ['Type',     $scholarship['type']     ?? '—',  'fa-tag'],
                    ['Deadline', $scholarship['deadline']
                                    ? date('d M Y', strtotime($scholarship['deadline']))
                                    : '—',                          'fa-calendar'],
                    ['Seats',    $scholarship['total_seats']
                                    ? $scholarship['total_seats'] . ' available'
                                    : 'Unlimited',                  'fa-users'],
                    ['Min GPA',  $scholarship['min_gpa']  ?? 'None','fa-star'],
                    ['Gender',   $scholarship['gender_requirement'] ?? 'Any', 'fa-venus-mars'],
                ];
                foreach ($details as [$label, $value, $icon]):
                ?>
                <div style="display:flex;align-items:center;gap:.7rem;
                            padding:.5rem 0;border-bottom:1px solid var(--border);">
                    <i class="fas <?= $icon ?>"
                       style="color:var(--primary);width:16px;text-align:center;flex-shrink:0;font-size:.82rem;"></i>
                    <span style="font-size:.8rem;color:var(--muted);flex:1;"><?= $label ?></span>
                    <span style="font-size:.82rem;font-weight:600;color:var(--ink);">
                        <?= htmlspecialchars($value) ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Eligibility criteria box -->
                <?php if ($scholarship['eligibility_criteria']): ?>
                <div style="margin-top:.9rem;padding:.8rem;background:var(--bg);
                            border-radius:var(--radius);border-left:3px solid var(--primary);">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;
                                letter-spacing:.07em;color:var(--muted);margin-bottom:.35rem;">
                        Eligibility
                    </div>
                    <p style="font-size:.8rem;color:var(--body);margin:0;line-height:1.65;">
                        <?= nl2br(htmlspecialchars($scholarship['eligibility_criteria'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Deadline warning if close -->
                <?php
                if ($scholarship['deadline']) {
                    $days_left = ceil((strtotime($scholarship['deadline']) - time()) / 86400);
                    if ($days_left >= 0 && $days_left <= 7):
                ?>
                <div style="margin-top:.9rem;padding:.7rem;background:var(--red-lt);
                            border-radius:var(--radius);border:1px solid #fecaca;
                            font-size:.8rem;color:var(--red);font-weight:600;
                            display:flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-clock"></i>
                    Only <?= $days_left ?> day<?= $days_left !== 1 ? 's' : '' ?> left to apply!
                </div>
                <?php endif; } ?>

            </div>
        </div>
    </div>
    <!-- END left column -->


    <!-- ══════════════════════════════
         RIGHT COLUMN — Application Form
    ══════════════════════════════ -->
    <div class="col-lg-8">

        <?php if (!$already_applied): ?>

        <form method="POST"
              action="apply.php?id=<?= $scholarship_id ?>"
              enctype="multipart/form-data">
        <!--
            enctype="multipart/form-data" is REQUIRED for file uploads.
            Without it, files will not be sent to the server.
        -->

            <!-- ══════════════════════
                 SECTION 1: PERSONAL INFORMATION
            ══════════════════════ -->
            <div class="panel" style="margin-bottom: 1.25rem;">
                <div class="panel-header">
                    <span class="panel-title">
                        <i class="fas fa-user me-2" style="color:var(--primary);"></i>
                        Personal Information
                    </span>
                </div>
                <div class="panel-body">
                    <div class="row g-3">

                        <!-- Full Name (read-only — from account) -->
                        <div class="col-md-6">
                            <label class="form-label-s">Full Name</label>
                            <input type="text"
                                   class="form-control-s"
                                   value="<?= htmlspecialchars($student['full_name']) ?>"
                                   disabled
                                   style="background:var(--bg); cursor:not-allowed;">
                            <small style="font-size:.72rem;color:var(--muted);">
                                Loaded from your account
                            </small>
                        </div>

                        <!-- Father's Name -->
                        <div class="col-md-6">
                            <label class="form-label-s">
                                Father's Name <span style="color:var(--red)">*</span>
                            </label>
                            <input type="text"
                                   name="father_name"
                                   class="form-control-s <?= in_array("Father's name is required.", $errors) ? 'is-invalid' : '' ?>"
                                   placeholder="e.g. Muhammad Ahmed"
                                   value="<?= htmlspecialchars($_POST['father_name'] ?? '') ?>"
                                   maxlength="120"
                                   required>
                        </div>

                        <!-- CNIC -->
                        <div class="col-md-6">
                            <label class="form-label-s">
                                CNIC / B-Form <span style="color:var(--red)">*</span>
                            </label>
                            <input type="text"
                                   name="cnic"
                                   class="form-control-s"
                                   placeholder="42101-1234567-1"
                                   value="<?= htmlspecialchars($_POST['cnic'] ?? '') ?>"
                                   maxlength="25"
                                   required>
                            <small style="font-size:.72rem;color:var(--muted);">
                                Format: 42101-1234567-1
                            </small>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6">
                            <label class="form-label-s">
                                Date of Birth <span style="color:var(--red)">*</span>
                            </label>
                            <input type="date"
                                   name="date_of_birth"
                                   class="form-control-s"
                                   value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6">
                            <label class="form-label-s">
                                Gender <span style="color:var(--red)">*</span>
                            </label>
                            <select name="gender" class="form-select-s" required>
                                <option value="">— Select —</option>
                                <option value="Male"
                                    <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>
                                    Male
                                </option>
                                <option value="Female"
                                    <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>
                                    Female
                                </option>
                                <option value="Other"
                                    <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>
                                    Other / Prefer not to say
                                </option>
                            </select>
                        </div>

                        <!-- Email (read-only) -->
                        <div class="col-md-6">
                            <label class="form-label-s">Email Address</label>
                            <input type="email"
                                   class="form-control-s"
                                   value="<?= htmlspecialchars($student['email']) ?>"
                                   disabled
                                   style="background:var(--bg); cursor:not-allowed;">
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label-s">
                                Current Address <span style="color:var(--red)">*</span>
                            </label>
                            <textarea name="address"
                                      class="form-control-s"
                                      rows="2"
                                      placeholder="House No., Street, Area, City, Province"
                                      required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>
            </div>
            <!-- END Section 1 -->


            <!-- ══════════════════════
                 SECTION 2: ACADEMIC INFORMATION
            ══════════════════════ -->
            <div class="panel" style="margin-bottom: 1.25rem;">
                <div class="panel-header">
                    <span class="panel-title">
                        <i class="fas fa-graduation-cap me-2" style="color:var(--teal);"></i>
                        Academic Information
                    </span>
                    <span style="font-size:.75rem;color:var(--muted);">Optional but recommended</span>
                </div>
                <div class="panel-body">
                    <div class="row g-3">

                        <!-- Matric Marks -->
                        <div class="col-md-6">
                            <label class="form-label-s">Matric Marks / Grade</label>
                            <input type="text"
                                   name="matric_marks"
                                   class="form-control-s"
                                   placeholder="e.g. 890/1100 or A+"
                                   value="<?= htmlspecialchars($_POST['matric_marks'] ?? '') ?>"
                                   maxlength="20">
                        </div>

                        <!-- Intermediate Marks -->
                        <div class="col-md-6">
                            <label class="form-label-s">Intermediate Marks / Grade</label>
                            <input type="text"
                                   name="intermediate_marks"
                                   class="form-control-s"
                                   placeholder="e.g. 970/1100 or A+"
                                   value="<?= htmlspecialchars($_POST['intermediate_marks'] ?? '') ?>"
                                   maxlength="20">
                        </div>

                        <!-- University / Institution -->
                        <div class="col-12">
                            <label class="form-label-s">Current University / Institution</label>
                            <input type="text"
                                   name="university"
                                   class="form-control-s"
                                   placeholder="e.g. University of Karachi"
                                   value="<?= htmlspecialchars($_POST['university'] ?? '') ?>"
                                   maxlength="150">
                        </div>

                    </div>
                </div>
            </div>
            <!-- END Section 2 -->


            <!-- ══════════════════════
                 SECTION 3: DOCUMENT UPLOAD
            ══════════════════════ -->
            <div class="panel" style="margin-bottom: 1.25rem;">
                <div class="panel-header">
                    <span class="panel-title">
                        <i class="fas fa-paperclip me-2" style="color:var(--amber);"></i>
                        Upload Documents
                    </span>
                    <span style="font-size:.75rem;color:var(--muted);">
                        PDF, JPG, PNG · max 5 MB each
                    </span>
                </div>
                <div class="panel-body">

                    <!-- What to upload -->
                    <div style="background:var(--bg);border-radius:var(--radius);
                                padding:.85rem 1rem;margin-bottom:1.1rem;font-size:.84rem;">
                        <div style="font-weight:700;color:var(--ink);margin-bottom:.4rem;">
                            <i class="fas fa-list-check me-2" style="color:var(--primary);"></i>
                            Recommended Documents
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.25rem .75rem;color:var(--muted);">
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>CNIC / B-Form copy</span>
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>Matric result card</span>
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>Intermediate result card</span>
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>University enrollment letter</span>
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>Passport size photo</span>
                            <span><i class="fas fa-check me-1" style="color:var(--green);font-size:.7rem;"></i>Income certificate (if needed)</span>
                        </div>
                    </div>

                    <!-- File drop zone -->
                    <!-- Clicking this area opens the file picker -->
                    <div class="upload-zone" id="uploadZone">
                        <!--
                            name="documents[]"  — the [] means multiple files
                            multiple            — allow selecting more than one file at once
                            accept              — hint to browser which files to show
                            The input is hidden; clicking the zone triggers it via JS
                        -->
                        <input type="file"
                               id="filePickerInput"
                               name="documents[]"
                               accept=".pdf,.jpg,.jpeg,.png"
                               multiple>

                        <div class="upload-zone-icon">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2.2rem;color:var(--muted);"></i>
                        </div>
                        <p style="font-size:.9rem;margin:.5rem 0 .25rem;">
                            <strong>Click to choose files</strong> or drag &amp; drop here
                        </p>
                        <p style="font-size:.76rem;color:var(--muted);margin:0;">
                            PDF, JPG, PNG accepted · Maximum 5 MB per file · Multiple files allowed
                        </p>
                    </div>

                    <!-- Selected files preview list (filled by JavaScript) -->
                    <div id="selectedFilesList"></div>

                </div>
            </div>
            <!-- END Section 3 -->


            <!-- ══════════════════════
                 SECTION 4: SUBMIT BAR
            ══════════════════════ -->
            <div class="panel">
                <div class="panel-body"
                     style="display:flex;align-items:center;
                            justify-content:space-between;gap:1rem;flex-wrap:wrap;">

                    <!-- Info note -->
                    <p style="font-size:.82rem;color:var(--muted);margin:0;flex:1;">
                        <i class="fas fa-shield-alt me-1" style="color:var(--primary);"></i>
                        Your application will be reviewed by the admin team.
                        You will receive a notification when the status changes.
                    </p>

                    <!-- Buttons -->
                    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                        <a href="scholarships.php" class="btn-s-outline">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit"
                                class="btn-s-primary"
                                id="submitBtn"
                                onclick="return confirmSubmit()">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>

                </div>
            </div>
            <!-- END submit bar -->

        </form>

        <?php else: ?>
        <!-- If already applied, show a go-back option instead of form -->
        <div class="panel">
            <div class="panel-body" style="text-align:center;padding:3rem 2rem;">
                <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
                <h4 style="font-size:1.1rem;margin-bottom:.5rem;">Application Already Submitted</h4>
                <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem;">
                    You applied for this scholarship on
                    <?= $existing_application
                        ? date('d M Y', strtotime($conn->query("SELECT applied_at FROM applications WHERE application_id={$existing_application['application_id']}")->fetch_assoc()['applied_at'] ?? 'now'))
                        : 'a previous date' ?>.
                </p>
                <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
                    <a href="applications.php" class="btn-s-primary">
                        <i class="fas fa-list"></i> View My Applications
                    </a>
                    <a href="scholarships.php" class="btn-s-outline">
                        <i class="fas fa-search"></i> Browse Other Scholarships
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <!-- END right column -->

</div>
<!-- END two-column layout -->


<!-- ============================================================
     JAVASCRIPT — File Upload Preview + Submit Confirm
============================================================ -->
<script>
// ──────────────────────────────────────────────────────────────
// FILE UPLOAD: Connect the drop zone to the hidden file input
// ──────────────────────────────────────────────────────────────
var uploadZone   = document.getElementById('uploadZone');
var fileInput    = document.getElementById('filePickerInput');
var previewList  = document.getElementById('selectedFilesList');

if (uploadZone && fileInput) {

    // Clicking the zone opens the file picker dialog
    uploadZone.addEventListener('click', function () {
        fileInput.click();
    });

    // Visual feedback when dragging a file over the zone
    uploadZone.addEventListener('dragover', function (e) {
        e.preventDefault();           // needed to allow drop
        uploadZone.classList.add('drag-over');
    });

    // Remove visual feedback when file leaves the zone
    uploadZone.addEventListener('dragleave', function () {
        uploadZone.classList.remove('drag-over');
    });

    // Handle file drop
    uploadZone.addEventListener('drop', function (e) {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        // Assign dropped files to the input so they submit with the form
        fileInput.files = e.dataTransfer.files;
        showSelectedFiles(fileInput.files);
    });

    // When files are chosen via the picker, show the preview
    fileInput.addEventListener('change', function () {
        showSelectedFiles(this.files);
    });
}

// Build a preview list of selected files
function showSelectedFiles(files) {
    if (!previewList) return;
    previewList.innerHTML = '';  // clear any previous preview

    if (files.length === 0) return;

    var filesArray = Array.from(files);

    filesArray.forEach(function (file) {
        // Format the file size nicely
        var sizeText;
        if (file.size >= 1024 * 1024) {
            sizeText = (file.size / 1024 / 1024).toFixed(1) + ' MB';
        } else {
            sizeText = Math.round(file.size / 1024) + ' KB';
        }

        // Choose icon based on extension
        var ext = file.name.split('.').pop().toLowerCase();
        var iconClass = (ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
        var iconColor = (ext === 'pdf') ? '#dc2626' : '#0d9488';

        // Flag oversized files in red
        var sizeColor = (file.size > 5 * 1024 * 1024) ? '#dc2626' : 'var(--muted)';

        // Build the preview row HTML
        var html =
            '<div class="uploaded-file">' +
            '    <div class="file-icon">' +
            '        <i class="fas ' + iconClass + '" style="color:' + iconColor + ';"></i>' +
            '    </div>' +
            '    <div style="flex:1;min-width:0;">' +
            '        <div class="file-name">' + escapeHtml(file.name) + '</div>' +
            '        <div class="file-size" style="color:' + sizeColor + ';">' +
                         sizeText +
                         (file.size > 5 * 1024 * 1024 ? ' ⚠️ Too large!' : '') +
            '        </div>' +
            '    </div>' +
            '    <i class="fas fa-check-circle" style="color:var(--green);font-size:.9rem;"></i>' +
            '</div>';

        previewList.insertAdjacentHTML('beforeend', html);
    });

    // Update zone text to show count
    var countText = uploadZone.querySelector('p strong');
    if (countText) {
        countText.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';
    }
}

// Simple HTML escape to prevent XSS in file names shown in preview
function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// ──────────────────────────────────────────────────────────────
// SUBMIT CONFIRM: Ask once before submitting
// ──────────────────────────────────────────────────────────────
function confirmSubmit() {
    return window.confirm(
        'Submit your application for:\n"<?= addslashes(htmlspecialchars($scholarship['title'])) ?>"\n\n' +
        'Once submitted, you cannot apply for this scholarship again.\n\n' +
        'Continue?'
    );
}

// CNIC format helper — auto-insert dashes as user types
var cnicInput = document.querySelector('input[name="cnic"]');
if (cnicInput) {
    cnicInput.addEventListener('input', function () {
        // Remove all non-digits
        var digits = this.value.replace(/\D/g, '');

        // Format as XXXXX-XXXXXXX-X
        var formatted = '';
        if (digits.length <= 5) {
            formatted = digits;
        } else if (digits.length <= 12) {
            formatted = digits.slice(0, 5) + '-' + digits.slice(5);
        } else {
            formatted = digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12, 13);
        }

        this.value = formatted;
    });
}
</script>

<?php include 'includes/layout_end.php'; ?>
