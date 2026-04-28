<?php
/**
 * student/applications.php
 *
 * Lists the logged-in student's scholarship applications with filters
 * and a detail view. Uses prepared statements where user-supplied
 * parameters are bound (detail view) and enforces role checks.
 */

require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'My Applications';
$activePage = 'applications';

// ============================================================
// READ FILTER FROM URL
// ============================================================
// e.g. applications.php?status=approved
$filter = trim($_GET['status'] ?? '');

// Only allow valid status values to prevent SQL injection
$valid_statuses = ['pending', 'under_review', 'approved', 'rejected', 'disbursed'];
if (!in_array($filter, $valid_statuses)) {
    $filter = ''; // empty = show all
}

// ============================================================
// FETCH ALL APPLICATIONS FOR THIS STUDENT
// ============================================================
// Build WHERE clause
$where_sql = "WHERE a.user_id = $user_id";
if ($filter !== '') {
    $filter_safe = $conn->real_escape_string($filter);
    $where_sql  .= " AND a.status = '$filter_safe'";
}

$apps_result = $conn->query("
    SELECT
        a.application_id,
        a.status,
        a.applied_at,
        a.updated_at,
        s.scholarship_id,
        s.title          AS schol_title,
        s.provider,
        s.amount,
        s.level,
        s.type,
        p.payment_status,
        p.payment_date,
        p.amount         AS paid_amount
    FROM applications a
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    LEFT JOIN payments p ON p.application_id = a.application_id
    $where_sql
    ORDER BY a.applied_at DESC
");

// ============================================================
// COUNT PER STATUS (for tab badges)
// ============================================================
$status_counts = [];
$count_result = $conn->query("
    SELECT status, COUNT(*) AS cnt
    FROM applications
    WHERE user_id = $user_id
    GROUP BY status
");
while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['status']] = (int)$row['cnt'];
}
$total_count = array_sum($status_counts);

// ============================================================
// DETAIL VIEW — show full info for one application
// ============================================================
$detail_id  = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;
$detail     = null;
$detail_docs = [];

if ($detail_id > 0) {
    // Fetch main application detail
    // The WHERE includes user_id to prevent viewing other students' applications
    $d_stmt = $conn->prepare("
        SELECT
            a.*,
            s.title    AS schol_title,
            s.provider,
            s.amount   AS schol_amount,
            s.level,
            s.type,
            s.deadline,
            p.payment_status,
            p.payment_date,
            p.amount   AS paid_amount,
            p.remarks  AS payment_remarks
        FROM applications a
        JOIN scholarships s ON s.scholarship_id = a.scholarship_id
        LEFT JOIN payments p ON p.application_id = a.application_id
        WHERE a.application_id = ? AND a.user_id = ?
        LIMIT 1
    ");
    $d_stmt->bind_param("ii", $detail_id, $user_id);
    $d_stmt->execute();
    $detail = $d_stmt->get_result()->fetch_assoc();

    // Fetch uploaded documents for this application
    if ($detail) {
        $docs_result = $conn->query("
            SELECT document_id, document_name, file_path, file_type, uploaded_at
            FROM application_documents
            WHERE application_id = $detail_id
            ORDER BY uploaded_at ASC
        ");
        while ($doc = $docs_result->fetch_assoc()) {
            $detail_docs[] = $doc;
        }
    }
}

// ============================================================
// STATUS HELPER — colour and label for each status
// ============================================================
function get_status_style($status) {
    $styles = [
        'pending'      => ['bg' => '#fef9c3', 'color' => '#92400e', 'label' => 'Pending'],
        'under_review' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => 'Under Review'],
        'approved'     => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Approved'],
        'rejected'     => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Rejected'],
        'disbursed'    => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'label' => 'Disbursed'],
    ];
    return $styles[$status] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => ucfirst($status)];
}

// Flash messages from redirect
$flash_success = clean($_GET['success']  ?? '');
$flash_warning = clean($_GET['warning']  ?? '');
$flash_error   = clean($_GET['error']    ?? '');
?>

<?php include 'includes/layout.php'; ?>

<!-- Page header -->
<div class="page-header" style="margin-bottom:1.75rem;">
    <h1 style="font-size:1.5rem;margin-bottom:.25rem;">My Applications</h1>
    <div class="breadcrumb" style="font-size:.8rem;color:var(--muted);">
        <a href="dashboard.php" style="color:var(--muted);">Dashboard</a>
        <span style="margin:0 .4rem;opacity:.4;">›</span>
        My Applications
    </div>
</div>

<!-- Flash messages -->
<?php if ($flash_success): ?>
<div style="padding:.85rem 1.1rem;border-radius:var(--radius);font-size:.88rem;margin-bottom:1.25rem;
     background:var(--green-lt);color:#166534;border:1px solid #bbf7d0;
     display:flex;align-items:flex-start;gap:.65rem;">
    <i class="fas fa-check-circle" style="margin-top:.1rem;flex-shrink:0;"></i>
    <?= $flash_success ?>
</div>
<?php endif; ?>

<?php if ($flash_warning): ?>
<div style="padding:.85rem 1.1rem;border-radius:var(--radius);font-size:.88rem;margin-bottom:1.25rem;
     background:#fef9c3;color:#92400e;border:1px solid #fde68a;
     display:flex;align-items:flex-start;gap:.65rem;">
    <i class="fas fa-exclamation-triangle" style="margin-top:.1rem;flex-shrink:0;"></i>
    <?= $flash_warning ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div style="padding:.85rem 1.1rem;border-radius:var(--radius);font-size:.88rem;margin-bottom:1.25rem;
     background:var(--red-lt);color:#991b1b;border:1px solid #fecaca;
     display:flex;align-items:flex-start;gap:.65rem;">
    <i class="fas fa-times-circle" style="margin-top:.1rem;flex-shrink:0;"></i>
    <?= $flash_error ?>
</div>
<?php endif; ?>


<!-- ============================================================
     DETAIL PANEL — shown when ?detail=N is in URL
============================================================ -->
<?php if ($detail): ?>
<div class="panel" style="margin-bottom:1.5rem;">

    <!-- Header row -->
    <div class="panel-header" style="display:flex;align-items:center;
         justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <span class="panel-title">
                Application #<?= $detail_id ?> — <?= htmlspecialchars($detail['schol_title']) ?>
            </span>
            <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                Submitted <?= date('d M Y, h:i A', strtotime($detail['applied_at'])) ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;">
            <?php $st = get_status_style($detail['status']); ?>
            <span style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;
                         padding:.3rem .9rem;border-radius:999px;
                         font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">
                <?= $st['label'] ?>
            </span>
            <!-- Close the detail view -->
            <a href="applications.php<?= $filter ? '?status='.$filter : '' ?>"
               style="font-size:.8rem;color:var(--muted);text-decoration:none;
                      border:1px solid var(--border);padding:.35rem .8rem;border-radius:6px;">
                <i class="fas fa-times me-1"></i>Close
            </a>
        </div>
    </div>

    <div style="padding:1.4rem;">

        <!-- ── Status Timeline ── -->
        <!-- Shows progress visually: Submitted → Under Review → Approved → Disbursed -->
        <div style="margin-bottom:1.75rem;">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.07em;color:var(--muted);margin-bottom:1rem;">
                Application Progress
            </div>
            <?php
            // Define the steps in order
            $timeline_steps = [
                ['key' => 'pending',      'label' => 'Submitted',    'icon' => 'fa-paper-plane'],
                ['key' => 'under_review', 'label' => 'Under Review', 'icon' => 'fa-search'],
                ['key' => 'approved',     'label' => 'Approved',     'icon' => 'fa-check'],
                ['key' => 'disbursed',    'label' => 'Disbursed',    'icon' => 'fa-wallet'],
            ];

            // Map each status to its position in the timeline
            $order = ['pending' => 0, 'under_review' => 1, 'approved' => 2, 'disbursed' => 3, 'rejected' => 2];
            $current_position = $order[$detail['status']] ?? 0;
            $is_rejected = ($detail['status'] === 'rejected');
            ?>
            <div class="app-timeline">
                <?php foreach ($timeline_steps as $step_index => $step): ?>
                <?php
                // Determine step state: done (past), active (current), upcoming
                if ($is_rejected && $step_index === 2) {
                    $state = 'rejected';
                } elseif ($step_index < $current_position) {
                    $state = 'done';
                } elseif ($step_index === $current_position) {
                    $state = 'active';
                } else {
                    $state = '';
                }
                ?>
                <div class="timeline-step <?= $state ?>">
                    <div class="step-circle">
                        <?php if ($state === 'done'): ?>
                            <i class="fas fa-check" style="font-size:.7rem;"></i>
                        <?php elseif ($state === 'rejected'): ?>
                            <i class="fas fa-times" style="font-size:.7rem;"></i>
                        <?php else: ?>
                            <i class="fas <?= $step['icon'] ?>" style="font-size:.7rem;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label">
                        <?= $is_rejected && $step_index === 2 ? 'Rejected' : $step['label'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Rejection message -->
            <?php if ($is_rejected): ?>
            <div style="margin-top:.9rem;padding:.75rem 1rem;background:var(--red-lt);
                        border-radius:var(--radius);font-size:.84rem;color:#991b1b;
                        border:1px solid #fecaca;">
                <i class="fas fa-info-circle me-2"></i>
                Your application was not successful. You may apply for other scholarships.
            </div>
            <?php endif; ?>

            <!-- Disbursement success message -->
            <?php if ($detail['payment_status'] === 'paid'): ?>
            <div style="margin-top:.9rem;padding:.75rem 1rem;background:var(--green-lt);
                        border-radius:var(--radius);font-size:.84rem;color:#166534;
                        border:1px solid #bbf7d0;">
                <i class="fas fa-money-bill-wave me-2"></i>
                <strong>Payment Received!</strong>
                Rs. <?= number_format($detail['paid_amount']) ?>
                was disbursed on <?= date('d M Y', strtotime($detail['payment_date'])) ?>.
                <?php if ($detail['payment_remarks']): ?>
                    <br><small>Ref: <?= htmlspecialchars($detail['payment_remarks']) ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Info Tables: Scholarship + Personal + Academic ── -->
        <div class="row g-3" style="margin-bottom:1.25rem;">

            <!-- Scholarship Info -->
            <div class="col-md-4">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                            letter-spacing:.07em;color:var(--muted);margin-bottom:.65rem;">
                    Scholarship
                </div>
                <?php
                $schol_info = [
                    ['Title',    $detail['schol_title']],
                    ['Provider', $detail['provider'] ?? '—'],
                    ['Amount',   $detail['schol_amount']
                                    ? 'Rs. ' . number_format($detail['schol_amount'])
                                    : 'Varies'],
                    ['Level',    $detail['level']    ?? '—'],
                    ['Deadline', $detail['deadline']
                                    ? date('d M Y', strtotime($detail['deadline']))
                                    : '—'],
                ];
                foreach ($schol_info as [$label, $value]):
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem;">
                    <span style="color:var(--muted);"><?= $label ?></span>
                    <span style="font-weight:600;color:var(--ink);text-align:right;max-width:60%;">
                        <?= htmlspecialchars($value) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Personal Info -->
            <div class="col-md-4">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                            letter-spacing:.07em;color:var(--muted);margin-bottom:.65rem;">
                    Personal Info
                </div>
                <?php
                $personal_info = [
                    ["Father's Name", $detail['father_name']    ?? '—'],
                    ['CNIC',          $detail['cnic']           ?? '—'],
                    ['Date of Birth', $detail['date_of_birth']
                                        ? date('d M Y', strtotime($detail['date_of_birth']))
                                        : '—'],
                    ['Gender',        $detail['gender']         ?? '—'],
                ];
                foreach ($personal_info as [$label, $value]):
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem;">
                    <span style="color:var(--muted);"><?= $label ?></span>
                    <span style="font-weight:600;color:var(--ink);text-align:right;max-width:60%;">
                        <?= htmlspecialchars($value) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Academic Info -->
            <div class="col-md-4">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                            letter-spacing:.07em;color:var(--muted);margin-bottom:.65rem;">
                    Academic Info
                </div>
                <?php
                $academic_info = [
                    ['Matric',       $detail['matric_marks']       ?? '—'],
                    ['Intermediate', $detail['intermediate_marks'] ?? '—'],
                    ['University',   $detail['university']         ?? '—'],
                    ['Address',      mb_substr($detail['address'] ?? '—', 0, 40)],
                ];
                foreach ($academic_info as [$label, $value]):
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:.38rem 0;border-bottom:1px solid var(--border);font-size:.83rem;">
                    <span style="color:var(--muted);"><?= $label ?></span>
                    <span style="font-weight:600;color:var(--ink);text-align:right;max-width:60%;">
                        <?= htmlspecialchars($value) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- ── Uploaded Documents ── -->
        <?php if (!empty($detail_docs)): ?>
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.07em;color:var(--muted);margin-bottom:.65rem;">
                Uploaded Documents (<?= count($detail_docs) ?>)
            </div>
            <div class="row g-2">
                <?php foreach ($detail_docs as $doc):
                    $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    $icon_class = ($ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                    $icon_color = ($ext === 'pdf') ? '#dc2626'     : '#0d9488';
                ?>
                <div class="col-md-4 col-6">
                    <a href="../<?= htmlspecialchars($doc['file_path']) ?>"
                       target="_blank"
                       style="display:flex;align-items:center;gap:.6rem;
                              padding:.65rem .85rem;background:var(--bg);
                              border:1px solid var(--border);border-radius:var(--radius);
                              text-decoration:none;transition:border-color .2s;">
                        <i class="fas <?= $icon_class ?>"
                           style="color:<?= $icon_color ?>;font-size:1.1rem;flex-shrink:0;"></i>
                        <div style="min-width:0;">
                            <div style="font-size:.78rem;font-weight:600;color:var(--ink);
                                        overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($doc['document_name']) ?>
                            </div>
                            <div style="font-size:.68rem;color:var(--muted);">
                                <?= strtoupper($ext) ?> &nbsp;·&nbsp;
                                <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                        <i class="fas fa-external-link-alt"
                           style="font-size:.7rem;color:var(--muted);margin-left:auto;flex-shrink:0;"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:.84rem;color:var(--muted);padding:.75rem 1rem;
                    background:var(--bg);border-radius:var(--radius);">
            <i class="fas fa-folder-open me-2"></i>No documents were uploaded with this application.
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>
<!-- END detail panel -->


<!-- ============================================================
     STATUS FILTER TABS
============================================================ -->
<div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <?php
    $tabs = [
        ['' ,           'All',         'fa-list'],
        ['pending',     'Pending',     'fa-clock'],
        ['under_review','Under Review','fa-search'],
        ['approved',    'Approved',    'fa-check'],
        ['rejected',    'Rejected',    'fa-times'],
        ['disbursed',   'Disbursed',   'fa-wallet'],
    ];

    foreach ($tabs as [$val, $label, $icon]):
        $is_active = ($filter === $val);
        // Count for this tab
        $tab_count = ($val === '')
            ? $total_count
            : ($status_counts[$val] ?? 0);
    ?>
    <a href="applications.php<?= $val ? '?status='.$val : '' ?>"
       style="display:inline-flex;align-items:center;gap:.4rem;
              padding:.42rem 1rem;border-radius:999px;font-size:.78rem;font-weight:600;
              border:1.5px solid <?= $is_active ? 'var(--primary)' : 'var(--border)' ?>;
              background:<?= $is_active ? 'var(--primary)' : 'var(--white)' ?>;
              color:<?= $is_active ? '#fff' : 'var(--muted)' ?>;
              text-decoration:none;transition:all .2s ease;">
        <i class="fas <?= $icon ?>"></i>
        <?= $label ?>
        <span style="padding:.05rem .45rem;border-radius:999px;font-size:.65rem;
                     background:<?= $is_active ? 'rgba(255,255,255,.25)' : 'var(--bg)' ?>;
                     color:<?= $is_active ? '#fff' : 'var(--muted)' ?>;">
            <?= $tab_count ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>


<!-- ============================================================
     APPLICATIONS TABLE
============================================================ -->
<div class="panel">

    <?php if ($apps_result && $apps_result->num_rows > 0): ?>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Scholarship</th>
                    <th>Amount</th>
                    <th>Applied On</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $row_num = 1; while ($app = $apps_result->fetch_assoc()):
                    $st = get_status_style($app['status']);
                ?>
                <tr>
                    <!-- Row number -->
                    <td class="muted-cell"><?= $row_num++ ?></td>

                    <!-- Scholarship title + provider -->
                    <td>
                        <div class="title-cell">
                            <?= htmlspecialchars($app['schol_title']) ?>
                        </div>
                        <?php if ($app['provider']): ?>
                        <div class="muted-cell">
                            <i class="fas fa-building me-1"></i>
                            <?= htmlspecialchars($app['provider']) ?>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Amount -->
                    <td style="font-weight:600;white-space:nowrap;">
                        <?= $app['amount']
                            ? 'Rs. ' . number_format($app['amount'])
                            : '—' ?>
                    </td>

                    <!-- Applied date -->
                    <td class="muted-cell">
                        <?= date('d M Y', strtotime($app['applied_at'])) ?>
                    </td>

                    <!-- Status badge -->
                    <td>
                        <span style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;
                                     padding:.28rem .8rem;border-radius:999px;
                                     font-size:.7rem;font-weight:700;text-transform:uppercase;
                                     letter-spacing:.05em;display:inline-block;">
                            <?= $st['label'] ?>
                        </span>
                    </td>

                    <!-- Payment status -->
                    <td>
                        <?php if ($app['payment_status'] === 'paid'): ?>
                        <span style="font-size:.78rem;color:var(--green);font-weight:600;">
                            <i class="fas fa-check-circle me-1"></i>
                            Rs. <?= number_format($app['paid_amount']) ?>
                        </span>
                        <?php elseif ($app['status'] === 'approved'): ?>
                        <span style="font-size:.78rem;color:var(--amber);font-weight:600;">
                            <i class="fas fa-clock me-1"></i>Pending
                        </span>
                        <?php else: ?>
                        <span class="muted-cell">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- View detail button -->
                    <td>
                        <a href="applications.php?detail=<?= $app['application_id'] ?><?= $filter ? '&status='.$filter : '' ?>"
                           class="btn-view-detail">
                            <i class="fas fa-eye"></i> Track
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>

    <!-- Empty state -->
    <div style="text-align:center;padding:4rem 2rem;color:var(--muted);">
        <div style="font-size:3.5rem;margin-bottom:1rem;">📋</div>
        <h4 style="font-size:1rem;color:var(--ink);margin-bottom:.4rem;">
            <?= $filter
                ? 'No applications with status "' . htmlspecialchars(str_replace('_', ' ', $filter)) . '"'
                : 'No applications yet' ?>
        </h4>
        <p style="font-size:.88rem;margin-bottom:1.5rem;">
            <?= $filter
                ? 'Try a different status filter above.'
                : 'Browse available scholarships and apply today.' ?>
        </p>
        <a href="scholarships.php" class="btn-s-primary">
            <i class="fas fa-search"></i> Browse Scholarships
        </a>
    </div>

    <?php endif; ?>

</div>
<!-- END applications table -->

<?php include 'includes/layout_end.php'; ?>
