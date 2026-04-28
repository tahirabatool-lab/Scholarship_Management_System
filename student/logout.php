<?php
// ============================================================
// student/logout.php
// Secure Logout for Student Panel
// ============================================================
// This file safely ends the student's session and sends
// them back to the login page.
//
// HOW LOGOUT WORKS (step by step):
//   Step 1 — session_start()      : Load the current session data
//   Step 2 — session_unset()      : Clear all $_SESSION variables
//   Step 3 — Delete session cookie: Remove cookie from browser
//   Step 4 — session_destroy()    : Wipe session from server
//   Step 5 — header(Location)     : Redirect to login page
// ============================================================

// -----------------------------------------------------------
// STEP 1: Start (resume) the session so we can access its data
// -----------------------------------------------------------
session_start();

// -----------------------------------------------------------
// STEP 2: Unset all session variables
// This empties the $_SESSION array completely.
// Example: $_SESSION['user_id'], $_SESSION['role'], etc.
// are all removed here.
// -----------------------------------------------------------
session_unset();

// -----------------------------------------------------------
// STEP 3: Delete the session cookie from the browser
// Without this, the browser might keep the old session ID
// stored and could potentially reuse it.
// We set the cookie's expiry time to the past (time() - 3600)
// so the browser deletes it immediately.
// -----------------------------------------------------------
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),    // The cookie name (usually "PHPSESSID")
        '',                // Empty value
        time() - 3600,    // Expiry in the past = delete immediately
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// -----------------------------------------------------------
// STEP 4: Destroy the session on the server
// This removes the session file / data stored on the server.
// After this, the session ID is completely invalid.
// -----------------------------------------------------------
session_destroy();

// -----------------------------------------------------------
// STEP 5: Redirect to the login page
// We pass a success message in the URL so the login page
// can display "You have been logged out successfully."
// exit() after header() is important — it stops any more
// PHP code from running after the redirect.
// -----------------------------------------------------------
header("Location: ../login.php?success=" . urlencode("You have been logged out successfully."));
exit();
