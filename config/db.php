<?php
// config/db.php

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
$db   = 'calldesk';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?>
