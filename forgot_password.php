<?php
/**
 * forgot_password.php
 *
 * Public form to request a password reset. Generates a secure token and
 * (in development) shows the reset link. In production, enable PHPMailer
 * and store tokens in a `password_resets` table to email the reset link.
 * See inline SQL comment for the table schema and usage.
 */

require_once 'db.php';
require_once 'auth_helper.php';

redirect_if_logged_in();

$error   = '';
$success = '';

// -------------------------------------------------------
// PROCESS FORM SUBMISSION
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = clean($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {

        // Check if this email exists in the database
        $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Generate a secure random token
            $token   = generate_token(64);       // 128 hex characters
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

            // -------------------------------------------------
            // OPTION A: Store token in a password_resets table
            // (Create this table in your database first — see SQL below)
            // -------------------------------------------------
            // CREATE TABLE IF NOT EXISTS password_resets (
            //     id         INT AUTO_INCREMENT PRIMARY KEY,
            //     user_id    INT NOT NULL,
            //     token      VARCHAR(128) NOT NULL,
            //     expires_at DATETIME NOT NULL,
            //     used       BOOLEAN DEFAULT FALSE,
            //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            //     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            // );

            // $del  = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            // $del->bind_param("i", $user['user_id']);
            // $del->execute(); // Remove old tokens for this user

            // $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            // $ins->bind_param("iss", $user['user_id'], $token, $expires);
            // $ins->execute();

            // Build the reset link
            $reset_link = "http://localhost/scholarship/reset_password.php?token=" . $token;

            // -------------------------------------------------
            // OPTION B: Send email with PHPMailer (uncomment when ready)
            // -------------------------------------------------
            // require 'vendor/autoload.php';
            // use PHPMailer\PHPMailer\PHPMailer;
            //
            // $mail = new PHPMailer(true);
            // try {
            //     $mail->isSMTP();
            //     $mail->Host       = 'smtp.gmail.com';      // Your SMTP host
            //     $mail->SMTPAuth   = true;
            //     $mail->Username   = 'your@gmail.com';       // Your email
            //     $mail->Password   = 'your_app_password';    // App password (not account password)
            //     $mail->SMTPSecure = 'tls';
            //     $mail->Port       = 587;
            //
            //     $mail->setFrom('noreply@scholarship.com', 'Scholarship System');
            //     $mail->addAddress($email, $user['full_name']);
            //     $mail->Subject = 'Password Reset Request';
            //     $mail->Body    = "Hello {$user['full_name']},\n\n"
            //                    . "Click the link below to reset your password:\n"
            //                    . $reset_link . "\n\n"
            //                    . "This link expires in 1 hour.\n"
            //                    . "If you did not request this, ignore this email.";
            //
            //     $mail->send();
            // } catch (Exception $e) {
            //     $error = "Email could not be sent. Please try again later.";
            // }

            // -------------------------------------------------
            // FOR DEVELOPMENT: Show the link directly on screen
            // REMOVE THIS in production and use email instead!
            // -------------------------------------------------
            $success = "A password reset link has been generated. 
                        <br><br>
                        <strong>⚠️ Development Mode:</strong> 
                        <a href='" . htmlspecialchars($reset_link) . "' style='color:#1a7a4a'>
                            Click here to reset password
                        </a>
                        <br><small>(In production, this link is emailed to the user.)</small>";

        } else {
            // Security tip: Show the same message even if email not found.
            // This prevents "email enumeration" — attackers shouldn't know
            // which emails exist in your system.
            $success = "If an account with that email exists, a reset link has been sent.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Scholarship Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:   #0f1923; --muted: #6b7685;
            --gold:  #c8973a; --bg:    #f7f4ef;
            --white: #ffffff; --err:   #c0392b; --ok: #1a7a4a;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: grid; place-items: center; padding: 2rem;
        }
        .card { background: var(--white); width: 100%; max-width: 420px; border-radius: 4px; box-shadow: 0 2px 40px rgba(0,0,0,.10); overflow: hidden; }
        .card-header { background: var(--ink); padding: 2rem 2.5rem 1.8rem; text-align: center; }
        .card-header h1 { font-family: 'Playfair Display', serif; color: var(--gold); font-size: 1.5rem; }
        .card-header p { color: #8fa0b0; font-size: .85rem; margin-top: .35rem; }
        .card-body { padding: 2rem 2.5rem 2.5rem; }
        .alert { padding: .75rem 1rem; border-radius: 3px; font-size: .875rem; margin-bottom: 1.4rem; line-height: 1.6; }
        .alert-error   { background: #fdecea; color: var(--err); border-left: 3px solid var(--err); }
        .alert-success { background: #e6f4ed; color: var(--ok);  border-left: 3px solid var(--ok);  }
        label { display: block; font-size: .78rem; font-weight: 500; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: .4rem; }
        input[type="email"] { width: 100%; padding: .72rem 1rem; border: 1.5px solid #ddd; border-radius: 3px; font-family: inherit; font-size: .95rem; color: var(--ink); transition: border-color .2s; margin-bottom: 1.2rem; }
        input:focus { outline: none; border-color: var(--gold); }
        .btn { width: 100%; padding: .85rem; background: var(--gold); color: var(--ink); border: none; border-radius: 3px; font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: 1rem; cursor: pointer; transition: background .2s; }
        .btn:hover { background: #b8862d; }
        .links { text-align: center; margin-top: 1.4rem; font-size: .875rem; color: var(--muted); }
        .links a { color: var(--gold); text-decoration: none; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h1>Forgot Password</h1>
        <p>We'll send a reset link to your email</p>
    </div>
    <div class="card-body">

        <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="forgot_password.php" novalidate>
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                   required>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">← Back to login</a>
        </div>
    </div>
</div>
</body>
</html>
