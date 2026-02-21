<?php
// api/sources.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$org_id = $auth_user['organization_id'];
$sql = "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC";
$result = mysqli_query($conn, $sql);
$sources = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sources[] = $row;
}

sendResponse(true, 'Lead sources fetched successfully', $sources);
?>
