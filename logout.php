<?php
/**
 * logout.php
 *
 * Public logout endpoint: logs the logout action (activity_logs), clears
 * the session, removes the session cookie, and redirects to the login page.
 */

require_once 'db.php';
require_once 'auth_helper.php';

// Only log the activity if someone was actually logged in
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Record logout in activity log
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
        VALUES (?, 'LOGOUT', 'users', ?, 'User logged out', ?)
    ");
    $stmt->bind_param("iis", $user_id, $user_id, $ip);
    $stmt->execute();
    $stmt->close();
}

// 1. Unset all session variables
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect to login with a goodbye message
header("Location: login.php?success=You+have+been+logged+out+successfully.");
exit();
