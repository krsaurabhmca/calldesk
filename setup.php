<?php
// setup.php
// RUN THIS FILE ONCE TO SETUP THE ADMIN USER CORRECTLY
require_once 'config/db.php';

echo "<h2>Calldesk CRM Setup</h2>";

// 1. Create tables if they don't exist
$sql = file_get_contents('database.sql');
// Since database.sql might contain multiple queries, we split them
$queries = explode(';', $sql);
$success = 0;
$errors = 0;

foreach ($queries as $query) {
    if (trim($query)) {
        if (mysqli_query($conn, $query)) {
            $success++;
        } else {
            // echo "Error: " . mysqli_error($conn) . "<br>";
            $errors++;
        }
    }
}

echo "Database initialized ($success queries success, $errors skipped/errors).<br>";

// 2. Create Admin with verified hash
$name = "System Admin";
$mobile = "9999999999";
$password = "admin123";
$role = "admin";
$hash = password_hash($password, PASSWORD_DEFAULT);

// Delete existing to be sure
mysqli_query($conn, "DELETE FROM users WHERE mobile = '$mobile'");

$stmt = mysqli_prepare($conn, "INSERT INTO users (name, mobile, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $name, $mobile, $hash, $role);

if (mysqli_stmt_execute($stmt)) {
    echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<strong>Success!</strong> Admin account created.<br>";
    echo "Mobile: <strong>$mobile</strong><br>";
    echo "Password: <strong>$password</strong><br>";
    echo "URL: <a href='login.php'>Go to Login</a>";
    echo "</div>";
} else {
    echo "Error creating admin: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
?>
