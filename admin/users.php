<?php
/**
 * admin/users.php — Manage Users (View, Toggle Status, Delete)
 */
require_once '../db.php';
require_once '../auth_helper.php';
require_login();
require_role('admin');

$pageTitle  = 'Users';
$activePage = 'users';

// ── Toggle status ──
if (isset($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    if ($uid === $_SESSION['user_id']) {
        header("Location: users.php?error=Cannot+deactivate+your+own+account.");
        exit();
    }
  // NOTE: These queries currently use direct interpolation. Cast to int above
  // reduces risk, but converting to prepared statements is recommended.
  // Example read+update using prepared statements prevents accidental SQL injection.
  $cur = $conn->query("SELECT status FROM users WHERE user_id=$uid")->fetch_assoc()['status'] ?? '';
  $new = $cur === 'active' ? 'inactive' : 'active';
  $conn->query("UPDATE users SET status='$new' WHERE user_id=$uid");
    header("Location: users.php?success=User+status+changed+to+$new.");
    exit();
}

// ── Delete user ──
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === $_SESSION['user_id']) {
        header("Location: users.php?error=Cannot+delete+your+own+account.");
        exit();
    }
  // Direct delete — consider using prepared statements and cascade
  // safety checks. Also consider switching to a soft-delete pattern
  // (status='deleted') if audit/history retention is required.
  $conn->query("DELETE FROM users WHERE user_id=$uid AND role='student'");
    header("Location: users.php?success=Student+account+deleted.");
    exit();
}

// Fetch all students (not admins)
$search = clean($_GET['q'] ?? '');
$where  = "role='student'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (full_name LIKE '%$s%' OR email LIKE '%$s%')";
}
$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");

include 'includes/layout.php';
?>

<div class="page-hd">
  <h1>Manage Users</h1>
  <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><span class="bc-sep">›</span>Users</div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert-a alert-success"><i class="fas fa-check-circle"></i><?= clean($_GET['success']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert-a alert-error"><i class="fas fa-times-circle"></i><?= clean($_GET['error']) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="filter-row">
    <form method="GET" action="users.php" style="display:flex;gap:.65rem;flex-wrap:wrap;flex:1">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by name or email…">
      </div>
      <button type="submit" class="btn-a btn-secondary btn-sm">
        <i class="fas fa-search"></i> Search
      </button>
      <?php if ($search): ?>
      <a href="users.php" class="btn-a btn-secondary btn-sm">
        <i class="fas fa-times"></i> Clear
      </a>
      <?php endif; ?>
    </form>
    <span style="font-size:.78rem;color:var(--muted)">
      <?= $users ? $users->num_rows : 0 ?> student<?= ($users && $users->num_rows !== 1) ? 's' : '' ?>
    </span>
  </div>

  <?php if ($users && $users->num_rows > 0): ?>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Phone</th>
          <th>Applications</th>
          <th>Joined</th>
          <th>Last Login</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $n=1; while ($u = $users->fetch_assoc()):
          // NOTE: This per-row COUNT query can cause N+1 performance issues
          // on large datasets. Consider fetching application counts with a
          // single JOIN or a grouped query before rendering the table.
          $app_cnt = $conn->query("SELECT COUNT(*) c FROM applications WHERE user_id={$u['user_id']}")->fetch_assoc()['c'] ?? 0;
          $init    = strtoupper(substr($u['full_name'],0,1));
        ?>
        <tr>
          <td class="td-muted"><?= $n++ ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.65rem">
              <div style="width:34px;height:34px;border-radius:50%;
                          background:linear-gradient(135deg,var(--em),#0d9488);
                          display:grid;place-items:center;color:#fff;
                          font-size:.8rem;font-weight:700;flex-shrink:0">
                <?= $init ?>
              </div>
              <div>
                <div class="td-primary"><?= htmlspecialchars($u['full_name']) ?></div>
                <div class="td-muted"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="td-muted"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td>
            <a href="applications.php?student=<?= $u['user_id'] ?>"
               style="font-size:.82rem;font-weight:700;color:var(--em)">
              <?= $app_cnt ?> application<?= $app_cnt !== 1 ? 's' : '' ?>
            </a>
          </td>
          <td class="td-muted"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
          <td class="td-muted">
            <?= $u['last_login'] ? date('d M Y',strtotime($u['last_login'])) : 'Never' ?>
          </td>
          <td>
            <span class="badge-s badge-<?= $u['status'] ?>">
              <?= ucfirst($u['status']) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap">
              <a href="users.php?toggle=<?= $u['user_id'] ?>"
                 class="btn-a btn-sm <?= $u['status']==='active' ? 'btn-warning' : 'btn-primary' ?>"
                 data-confirm="<?= $u['status']==='active' ? 'Deactivate this student?' : 'Activate this student?' ?>">
                <i class="fas fa-<?= $u['status']==='active' ? 'ban' : 'check' ?>"></i>
                <?= $u['status']==='active' ? 'Deactivate' : 'Activate' ?>
              </a>
              <a href="users.php?delete=<?= $u['user_id'] ?>"
                 class="btn-a btn-danger btn-sm"
                 data-confirm="Delete this student account? All their applications and documents will be removed.">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-tbl">
    <span class="ei">👥</span>
    <h5><?= $search ? "No students match \"$search\"" : 'No students registered yet' ?></h5>
    <p>Students will appear here once they register on the public website.</p>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/layout_end.php'; ?>
