<?php
// enforce_saas.php
require_once 'config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;
ALTER TABLE lead_sources ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;
ALTER TABLE call_logs ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;
ALTER TABLE follow_ups ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;
ALTER TABLE whatsapp_messages ADD COLUMN IF NOT EXISTS organization_id INT NULL AFTER id;

INSERT IGNORE INTO organizations (id, name) VALUES (1, 'Default Organization');

UPDATE users SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE lead_sources SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE leads SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE call_logs SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE follow_ups SET organization_id = 1 WHERE organization_id IS NULL;
UPDATE whatsapp_messages SET organization_id = 1 WHERE organization_id IS NULL;
";

// Use multi_query for multiple statements
if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    echo "SaaS Schema enforced successfully.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
