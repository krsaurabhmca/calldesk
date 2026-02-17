<?php
// config/db.php

// Auto-detect environment
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1');

if ($is_local) {
    // Local Settings (XAMPP)
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'calldesk';
    define('BASE_URL', '/calldesk/');
} else {
    // Live Server Settings
    $host = 'localhost';
    $user = 'u965320534_calldesk';
    $pass = '@Call_2001';
    $db   = 'u965320534_calldesk';
    define('BASE_URL', '/'); // Based on domain root https://calldesk.fastbloom.co.in/
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?>
