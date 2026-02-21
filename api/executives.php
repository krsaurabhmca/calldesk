<?php
// api/executives.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$org_id = $auth_user['organization_id'];
$sql = "SELECT id, name, mobile FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1 ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
$execs = [];

while ($row = mysqli_fetch_assoc($result)) {
    $execs[] = $row;
}

sendResponse(true, 'Executives list fetched', $execs);
?>
