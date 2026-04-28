<?php
/**
 * student/documents.php — Manage uploaded documents
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('student');

$user_id    = $_SESSION['user_id'];
$pageTitle  = 'My Documents';
$activePage = 'documents';

define('UPLOAD_DIR', '../uploads/documents/');
define('ALLOWED_EXT', ['pdf','jpg','jpeg','png']);
define('MAX_SIZE', 5 * 1024 * 1024);

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

$error   = '';
$success = '';

// ── Delete document ──
if (isset($_GET['delete'])) {
    $doc_id = (int)$_GET['delete'];
    // Verify ownership through application
    $check = $conn->prepare("
        SELECT d.file_path FROM application_documents d
        JOIN applications a ON a.application_id = d.application_id
        WHERE d.document_id=? AND a.user_id=?
    ");
    $check->bind_param("ii", $doc_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    $result->free();
    $check->close();
    
    if ($row) {
        $full_path = '../' . $row['file_path'];
        if (file_exists($full_path)) unlink($full_path);
        $del_stmt = $conn->prepare("DELETE FROM application_documents WHERE document_id=?");
        $del_stmt->bind_param("i", $doc_id);
        $del_stmt->execute();
        $del_stmt->close();
        $success = "Document deleted successfully.";
    }
}

// ── Upload standalone document ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['document']['tmp_name'])) {
    $app_id = (int)($_POST['application_id'] ?? 0);

    // Verify app belongs to this user
    $vcheck = $conn->prepare("SELECT application_id FROM applications WHERE application_id=? AND user_id=?");
    $vcheck->bind_param("ii", $app_id, $user_id);
    $vcheck->execute();

    if ($app_id && $vcheck->get_result()->num_rows > 0) {
        $file  = $_FILES['document'];
        $orig  = basename($file['name']);
        $ext   = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $size  = $file['size'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload failed. Please try again.";
        } elseif (!in_array($ext, ALLOWED_EXT)) {
            $error = "Invalid file type. Only PDF, JPG, PNG allowed.";
        } elseif ($size > MAX_SIZE) {
            $error = "File too large. Maximum size is 5 MB.";
        } else {
            $new_name = 'doc_'.$app_id.'_'.time().'.'.$ext;
            $dest     = UPLOAD_DIR . $new_name;
            $rel_path = 'uploads/documents/' . $new_name;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $mime  = mime_content_type($dest) ?: 'application/octet-stream';
                $dname = clean($_POST['doc_name'] ?? $orig);
                $ins   = $conn->prepare("INSERT INTO application_documents (application_id,document_name,file_path,file_type) VALUES (?,?,?,?)");
                $ins->bind_param("isss", $app_id, $dname, $rel_path, $mime);
                $ins->execute();
                $success = "Document uploaded successfully.";
            } else {
                $error = "Could not save file. Check server permissions.";
            }
        }
    } else {
        $error = "Invalid application selected.";
    }
}

// Fetch student's applications (for dropdown)
$my_apps = $conn->query("
    SELECT a.application_id, s.title
    FROM applications a
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    WHERE a.user_id=$user_id
    ORDER BY a.applied_at DESC
");

// Fetch all documents for this student
$documents = $conn->query("
    SELECT d.document_id, d.document_name, d.file_path, d.file_type, d.uploaded_at,
           a.application_id, s.title as schol_title
    FROM application_documents d
    JOIN applications a ON a.application_id = d.application_id
    JOIN scholarships s ON s.scholarship_id = a.scholarship_id
    WHERE a.user_id=$user_id
    ORDER BY d.uploaded_at DESC
");

include 'includes/layout.php';
?>

<div class="page-header">
  <h1>My Documents</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="sep">›</span>Documents</div>
</div>

<?php if ($error):   ?><div class="alert-s alert-s-error"><i class="fas fa-times-circle"></i><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert-s alert-s-success"><i class="fas fa-check-circle"></i><?= $success ?></div><?php endif; ?>

<div class="row g-4">

  <!-- Upload Form -->
  <div class="col-lg-4">
    <div class="panel" style="position:sticky;top:80px">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-upload me-2" style="color:var(--primary)"></i>Upload Document</span>
      </div>
      <div class="panel-body">

        <?php if ($my_apps && $my_apps->num_rows > 0): ?>
        <form method="POST" enctype="multipart/form-data" novalidate>
          <div style="margin-bottom:1rem">
            <label class="form-label-s">Attach to Application <span style="color:var(--red)">*</span></label>
            <select name="application_id" class="form-select-s" required>
              <option value="">— Select application —</option>
              <?php
              $my_apps->data_seek(0);
              while ($app = $my_apps->fetch_assoc()): ?>
              <option value="<?= $app['application_id'] ?>">
                #<?= $app['application_id'] ?> — <?= htmlspecialchars(mb_substr($app['title'],0,35)) ?>…
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div style="margin-bottom:1rem">
            <label class="form-label-s">Document Name / Label</label>
            <input type="text" name="doc_name" class="form-control-s"
                   placeholder="e.g. CNIC Front, Matric Certificate">
          </div>

          <div style="margin-bottom:1rem">
            <label class="form-label-s">File <span style="color:var(--red)">*</span></label>
            <div class="upload-zone" style="padding:1.5rem">
              <input type="file" name="document" id="docInput"
                     accept=".pdf,.jpg,.jpeg,.png" required>
              <div style="font-size:1.5rem;margin-bottom:.5rem">📎</div>
              <p style="font-size:.82rem"><strong>Click to choose file</strong></p>
              <p style="font-size:.72rem;margin-top:.2rem">PDF, JPG, PNG · max 5 MB</p>
            </div>
            <div id="fileList"></div>
          </div>

          <button type="submit" class="btn-s-primary w-100 justify-content-center">
            <i class="fas fa-cloud-upload-alt"></i> Upload Document
          </button>
        </form>

        <?php else: ?>
        <div class="empty-state" style="padding:1.5rem 0">
          <span class="empty-icon" style="font-size:2rem">📋</span>
          <p style="font-size:.85rem">You need to apply for a scholarship before uploading documents.</p>
          <a href="scholarships.php" class="btn-s-primary" style="font-size:.82rem">Browse Scholarships</a>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Documents List -->
  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title"><i class="fas fa-folder-open me-2" style="color:var(--amber)"></i>Uploaded Documents</span>
        <span style="font-size:.78rem;color:var(--muted)">
          <?= $documents ? $documents->num_rows : 0 ?> file<?= ($documents && $documents->num_rows !== 1) ? 's' : '' ?>
        </span>
      </div>

      <?php if ($documents && $documents->num_rows > 0): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.5rem;padding:1.5rem">
        <?php $documents->data_seek(0); while ($doc = $documents->fetch_assoc()):
          $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
          $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
          $isPdf = $ext === 'pdf';
        ?>
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;height:100%;box-shadow:0 2px 6px rgba(0,0,0,0.08);transition:transform 0.2s,box-shadow 0.2s"
               onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)'"
               onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)'">
            
            <?php if ($isImage): ?>
              <!-- Image Preview -->
              <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"
                 style="display:block;width:100%;height:200px;background:var(--bg);overflow:hidden;border-bottom:1px solid var(--border)">
                <img src="../<?= htmlspecialchars($doc['file_path']) ?>"
                     alt="<?= htmlspecialchars($doc['document_name'] ?: 'Untitled') ?>"
                     style="width:100%;height:100%;object-fit:cover;cursor:pointer;transition:transform 0.2s"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform='scale(1)'">
              </a>
            <?php elseif ($isPdf): ?>
              <!-- PDF Icon -->
              <div style="display:grid;place-items:center;width:100%;height:200px;background:linear-gradient(135deg,#f5f5f5,#e8e8e8);border-bottom:1px solid var(--border)">
                <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"
                   style="display:grid;place-items:center;width:100%;height:100%;text-decoration:none">
                  <div style="text-align:center">
                    <i class="fas fa-file-pdf" style="font-size:3.5rem;color:var(--red);margin-bottom:.5rem"></i>
                    <div style="font-size:.7rem;color:var(--muted);font-weight:600">PDF FILE</div>
                  </div>
                </a>
              </div>
            <?php endif; ?>

            <!-- Document Info -->
            <div style="padding:1rem;flex:1;display:flex;flex-direction:column">
              <div style="margin-bottom:.75rem;flex:1">
                <div style="font-size:.85rem;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-break:break-word;line-height:1.3">
                  <?= htmlspecialchars($doc['document_name'] ?: 'Untitled') ?>
                </div>
                <div style="font-size:.75rem;color:var(--muted);margin-top:.35rem">
                  .<?= strtoupper($ext) ?> · <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                </div>
              </div>

              <!-- Application Link -->
              <div style="font-size:.75rem;padding:.5rem;background:var(--primary-lt);border-radius:6px;margin-bottom:.75rem;color:var(--primary)">
                <a href="applications.php?detail=<?= $doc['application_id'] ?>"
                   style="color:var(--primary);text-decoration:none;font-weight:500">
                  #<?= $doc['application_id'] ?> — <?= htmlspecialchars(mb_substr($doc['schol_title'],0,20)) ?>
                </a>
              </div>

              <!-- Action Buttons -->
              <div style="display:flex;gap:.5rem">
                <a href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"
                   class="btn-view-detail" style="flex:1;font-size:.75rem;padding:.4rem .5rem;text-align:center"
                   title="View document">
                  <i class="fas fa-eye" style="margin-right:.25rem"></i>View
                </a>
                <a href="documents.php?delete=<?= $doc['document_id'] ?>"
                   class="btn-view-detail"
                   style="flex:1;font-size:.75rem;padding:.4rem .5rem;text-align:center;color:var(--red);border-color:var(--red-lt)"
                   onclick="return confirm('Delete this document?')"
                   title="Delete document">
                  <i class="fas fa-trash" style="margin-right:.25rem"></i>Delete
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <span class="empty-icon">📁</span>
        <h4>No documents uploaded yet</h4>
        <p>Upload your CNIC, certificates, and other required documents.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include 'includes/layout_end.php'; ?>
