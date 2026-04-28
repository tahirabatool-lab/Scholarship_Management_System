<?php
/**
 * auth_helper.php
 *
 * Shared authentication and utility helpers used across public,
 * admin, and student areas. Place `require_once 'auth_helper.php'`
 * near the top of pages that need session-based auth checks.
 *
 * Improvements made: added PHPDoc blocks for clarity and
 * to provide quick reference for future maintainers.
 *
 * Note: This file starts the session when required.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in.
 *
 * @return bool True when a user_id exists in the session.
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current user's role from session.
 *
 * @return string|null Role name (e.g. 'admin' or 'student') or null when not set.
 */
function get_role()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Require a logged-in user. Redirects to login page if not authenticated.
 *
 * @return void
 */
function require_login()
{
    if (!is_logged_in()) {
        header("Location: login.php?error=Please+log+in+to+continue.");
        exit();
    }
}

/**
 * Require a specific role. Redirects users without the role to their
 * appropriate dashboard.
 *
 * @param string $role Expected role name (e.g. 'admin').
 * @return void
 */
function require_role($role)
{
    require_login();
    if (get_role() !== $role) {
        // Wrong role — send them to their own dashboard
        if (get_role() === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: student/dashboard.php");
        }
        exit();
    }
}

/**
 * Redirect already-logged-in users away from auth pages.
 * Useful on `login.php` and `register.php` to prevent access by
 * authenticated users.
 *
 * @return void
 */
function redirect_if_logged_in()
{
    if (is_logged_in()) {
        if (get_role() === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: student/dashboard.php");
        }
        exit();
    }
}

/**
 * Sanitize user input (basic XSS prevention).
 * Trims, removes tags and escapes special HTML characters.
 *
 * @param string $data Raw input string
 * @return string Cleaned string safe for HTML output
 */
function clean($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate a secure random token.
 * Default length is 64 characters (hex encoded).
 *
 * @param int $length Token length in characters (must be even for hex)
 * @return string Hex-encoded random token
 * @throws Exception When random_bytes() cannot gather sufficient entropy
 */
function generate_token($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}
