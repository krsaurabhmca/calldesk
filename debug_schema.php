<?php
include 'config/db.php';
$tables = ['leads', 'follow_ups', 'call_logs', 'users', 'lead_sources'];
foreach ($tables as $table) {
    echo "\nTable: $table\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo " - Error: " . mysqli_error($conn) . "\n";
    }
}
?>
