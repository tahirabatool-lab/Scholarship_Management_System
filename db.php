<?php

// ============================================================
// db.php — Database Connection File
// Scholarship Management System
// ============================================================
// This file creates a connection to the MySQL database using
// mysqli. Include this file at the top of any PHP page that
// needs database access:  require_once 'db.php';
// ============================================================

// ------------------------------------
// 1. DATABASE CONFIGURATION
// ------------------------------------
// Simple configuration for InfinityFree and other shared hosting
// Update these values with your database credentials

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'scholarship_system');
define('DB_CHARSET', 'utf8mb4');

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
