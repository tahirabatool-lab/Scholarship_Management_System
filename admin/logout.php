<?php
/**
 * admin/logout.php — Admin Logout
 *
 * Steps:
 *  1. session_start()      — resume the session
 *  2. Log the logout action (if DB available)
 *  3. session_unset()      — clear all session variables
 *  4. Delete session cookie from browser
 *  5. session_destroy()    — destroy session on server
 *  6. Redirect to login.php with success message
 */

session_start();

// ── Log the logout to activity_logs if user was logged in ──
if (isset($_SESSION['user_id'])) {
    // Only attempt DB log if db.php exists and connection succeeds
    $db_file = __DIR__ . '/../db.php';
    if (file_exists($db_file)) {
        include_once $db_file;
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            $user_id = (int)$_SESSION['user_id'];
            $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $action  = 'LOGOUT';
            $table   = 'users';
            $desc    = 'Admin logged out';
            $stmt    = $conn->prepare("
                INSERT INTO activity_logs
                  (user_id, action, table_name, record_id, description, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("isssss", $user_id, $action, $table, $user_id, $desc, $ip);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// ── Step 1: session_unset() — remove all session variables ──
session_unset();

// ── Step 2: Delete the session cookie from the browser ──
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ── Step 3: session_destroy() — destroy session data on server ──
session_destroy();

// ── Step 4: Redirect to admin login page with goodbye message ──
header("Location: login.php?success=" . urlencode("You have been signed out successfully."));
exit();
