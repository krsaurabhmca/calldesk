<?php
// migrate_recordings.php
require_once 'config/db.php';

header('Content-Type: text/plain');
echo "Starting Migration: Adding recording_path to call_logs...\n";

// Check if column already exists
$check_sql = "SHOW COLUMNS FROM call_logs LIKE 'recording_path'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) == 0) {
    $alter_sql = "ALTER TABLE call_logs ADD COLUMN recording_path VARCHAR(255) NULL AFTER executive_id";
    if (mysqli_query($conn, $alter_sql)) {
        echo "SUCCESS: Column 'recording_path' added to 'call_logs' table.\n";
    } else {
        echo "ERROR: Could not add column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "NOTICE: Column 'recording_path' already exists in 'call_logs' table.\n";
}

echo "Migration process finished.\n";
?>
