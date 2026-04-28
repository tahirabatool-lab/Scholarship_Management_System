<?php
/**
 * reset_password.php
 *
 * Password reset form shown after the user clicks the emailed reset link.
 * This file validates the URL `token`, allows the user to set a new
 * password and (when enabled) marks the reset token as used. For
 * development the DB token checks are commented out — see `forgot_password.php`
 * for the `password_resets` table SQL and instructions to enable.
 */

require_once 'db.php';
require_once 'auth_helper.php';

redirect_if_logged_in();

$error   = '';
$success = '';
$token   = clean($_GET['token'] ?? '');
$valid   = false; // Is the token valid?
$user_id = null;

// -------------------------------------------------------
// STEP 1: Validate the token from the URL
// -------------------------------------------------------
if (empty($token)) {
    $error = "Invalid or missing reset token.";

} else {

    // --- Uncomment this block once password_resets table is created ---
    //
    // $stmt = $conn->prepare("
    //     SELECT pr.user_id, u.email
    //     FROM password_resets pr
    //     JOIN users u ON u.user_id = pr.user_id
    //     WHERE pr.token = ?
    //       AND pr.expires_at > NOW()
    //       AND pr.used = FALSE
    //       AND u.status = 'active'
    // ");
    // $stmt->bind_param("s", $token);
    // $stmt->execute();
    // $result = $stmt->get_result();
    //
    // if ($result->num_rows === 1) {
    //     $row     = $result->fetch_assoc();
    //     $user_id = $row['user_id'];
    //     $valid   = true;
    // } else {
    //     $error = "This reset link is invalid or has expired. Please request a new one.";
    // }
    // $stmt->close();

    // Placeholder for development (remove in production)
    $valid = true;
    $error = ""; // Token structure is valid — wire up DB check above to enforce expiry.
}

// -------------------------------------------------------
// STEP 2: Process the new password form
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {

    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields are required.";

    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";

    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";

    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // --- Uncomment once DB token validation is active ---
        //
        // // Update the password
        // $upd = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
        // $upd->bind_param("si", $hashed, $user_id);
        // $upd->execute();
        //
        // // Mark the token as used
        // $mark = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
        // $mark->bind_param("s", $token);
        // $mark->execute();
        //
        // $success = "Your password has been reset. You can now log in.";
        // $valid   = false; // Hide the form

        // Placeholder response
        $success = "Password reset successful! (DB update disabled — uncomment DB block to activate.)
                    <br><br><a href='login.php' style='color:#1a7a4a'>Proceed to login →</a>";
        $valid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Scholarship Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --ink:#0f1923; --muted:#6b7685; --gold:#c8973a; --bg:#f7f4ef; --white:#ffffff; --err:#c0392b; --ok:#1a7a4a; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; display:grid; place-items:center; padding:2rem; }
        .card { background:var(--white); width:100%; max-width:420px; border-radius:4px; box-shadow:0 2px 40px rgba(0,0,0,.10); overflow:hidden; }
        .card-header { background:var(--ink); padding:2rem 2.5rem 1.8rem; text-align:center; }
        .card-header h1 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.5rem; }
        .card-header p { color:#8fa0b0; font-size:.85rem; margin-top:.35rem; }
        .card-body { padding:2rem 2.5rem 2.5rem; }
        .alert { padding:.75rem 1rem; border-radius:3px; font-size:.875rem; margin-bottom:1.4rem; line-height:1.6; }
        .alert-error   { background:#fdecea; color:var(--err); border-left:3px solid var(--err); }
        .alert-success { background:#e6f4ed; color:var(--ok);  border-left:3px solid var(--ok); }
        label { display:block; font-size:.78rem; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin-bottom:.4rem; }
        input[type="password"] { width:100%; padding:.72rem 1rem; border:1.5px solid #ddd; border-radius:3px; font-family:inherit; font-size:.95rem; color:var(--ink); transition:border-color .2s; margin-bottom:1.2rem; }
        input:focus { outline:none; border-color:var(--gold); }
        .hint { font-size:.75rem; color:var(--muted); margin-top:-.8rem; margin-bottom:1rem; }
        .btn { width:100%; padding:.85rem; background:var(--gold); color:var(--ink); border:none; border-radius:3px; font-family:'DM Sans',sans-serif; font-weight:500; font-size:1rem; cursor:pointer; transition:background .2s; }
        .btn:hover { background:#b8862d; }
        .links { text-align:center; margin-top:1.4rem; font-size:.875rem; color:var(--muted); }
        .links a { color:var(--gold); text-decoration:none; font-weight:500; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h1>Reset Password</h1>
        <p>Enter your new password below</p>
    </div>
    <div class="card-body">

        <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <?php if ($valid && !$success): ?>
        <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>" novalidate>
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
            <p class="hint">8+ characters, one uppercase letter, one number.</p>

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>

            <button type="submit" class="btn">Reset Password</button>
        </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">← Back to login</a>
        </div>
    </div>
</div>
</body>
</html>
