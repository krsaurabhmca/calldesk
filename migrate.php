<?php
// migrate.php
require_once 'config/db.php';

echo "<h2>Database Migration</h2>";

// Add Lead Sources Table
$source_table = "CREATE TABLE IF NOT EXISTS lead_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $source_table);

// Add Call Logs Table
$call_logs_table = "CREATE TABLE IF NOT EXISTS call_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    type ENUM('Incoming', 'Outgoing', 'Missed') NOT NULL,
    duration INT DEFAULT 0,
    call_time DATETIME NOT NULL,
    lead_id INT NULL,
    executive_id INT NULL,
    is_converted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (executive_id) REFERENCES users(id) ON DELETE SET NULL
)";
mysqli_query($conn, $call_logs_table);

// Add source_id column to leads if not exists and drop old source column or migrate it
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM leads LIKE 'source_id'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE leads ADD COLUMN source_id INT NULL AFTER mobile");
    mysqli_query($conn, "ALTER TABLE leads ADD CONSTRAINT fk_source FOREIGN KEY (source_id) REFERENCES lead_sources(id) ON DELETE SET NULL");
    
    // Attempt to migrate existing text sources to the new table
    $existing = mysqli_query($conn, "SELECT DISTINCT source FROM leads WHERE source IS NOT NULL AND source != ''");
    while ($row = mysqli_fetch_assoc($existing)) {
        $sname = mysqli_real_escape_string($conn, $row['source']);
        mysqli_query($conn, "INSERT IGNORE INTO lead_sources (source_name) VALUES ('$sname')");
        $res = mysqli_query($conn, "SELECT id FROM lead_sources WHERE source_name = '$sname'");
        $sid = mysqli_fetch_assoc($res)['id'];
        mysqli_query($conn, "UPDATE leads SET source_id = $sid WHERE source = '$sname'");
    }
}

// Add api_token column to users
mysqli_query($conn, "ALTER TABLE users ADD COLUMN api_token VARCHAR(100) NULL AFTER status");

echo "Migration Successful! <a href='index.php'>Go to Dashboard</a>";
?>
