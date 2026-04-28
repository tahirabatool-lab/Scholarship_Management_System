<?php
/**
 * db.php
 *
 * Database connection bootstrap for the Scholarship Management System.
 * This file creates a mysqli connection assigned to `$conn`.
 *
 * WARNING: Current configuration uses hardcoded credentials.
 * For production, move credentials to an environment file (.env)
 * or server environment variables and load them securely.
 *
 * Usage: require_once 'db.php'; then use `$conn` for queries.
 */

// ------------------------------------
// 1. DATABASE CONFIGURATION
// ------------------------------------
// Change these values to match your server setup.

define('DB_HOST', 'localhost');       // Database server (usually localhost)
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', '');                // Your MySQL password (empty for XAMPP default)
define('DB_NAME', 'scholarship_system'); // Database name
define('DB_CHARSET', 'utf8mb4');      // Character set (supports emojis & all Unicode)

// ------------------------------------
// 2. CREATE THE CONNECTION
// ------------------------------------

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ------------------------------------
// 3. ERROR HANDLING
// ------------------------------------
// If the connection fails, stop the script and show the error.

if ($conn->connect_error) {
    // In production, log the error instead of displaying it.
    // error_log($conn->connect_error);
    die(
        "<h3 style='color:red; font-family:sans-serif;'>
            &#10060; Database Connection Failed<br>
            <small>" . htmlspecialchars($conn->connect_error) . "</small>
         </h3>"
    );
}

// ------------------------------------
// 4. SET CHARACTER SET
// ------------------------------------
// Ensures all data is sent/received in utf8mb4 (full Unicode support).

if (!$conn->set_charset(DB_CHARSET)) {
    die("Error setting character set: " . $conn->error);
}

// ------------------------------------
// 5. OPTIONAL: TIMEZONE (Pakistan Standard Time)
// ------------------------------------
// Uncomment the line below if your app needs server-side timestamps
// to match Pakistan Standard Time (UTC+5).

// $conn->query("SET time_zone = '+05:00'");

// ------------------------------------
// Connection is ready!
// Use $conn in your queries, e.g.:
//   $result = $conn->query("SELECT * FROM users");
// ------------------------------------
