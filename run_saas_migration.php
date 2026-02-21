<?php
// run_saas_migration.php
require_once 'config/db.php';

echo "<h2>Starting SaaS Migration...</h2>";

$sql = file_get_contents('saas_migration.sql');

// Split SQL by semicolons, but be careful with ENUM strings
// A better way is to use mysqli_multi_query
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
        if (mysqli_more_results($conn)) {
            echo "Step completed...<br>";
        }
    } while (mysqli_next_result($conn));
    
    if (mysqli_errno($conn)) {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    } else {
        echo "<p style='color:green'>SaaS Migration Successful!</p>";
    }
} else {
    echo "<p style='color:red'>Initial Error: " . mysqli_error($conn) . "</p>";
}

echo "<a href='index.php'>Go to Dashboard</a>";
?>
