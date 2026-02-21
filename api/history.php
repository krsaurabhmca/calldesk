<?php
// api/history.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$lead_id = (int)($_GET['lead_id'] ?? 0);

if ($lead_id <= 0) {
    sendResponse(false, 'Lead ID is required', null, 400);
}

// Permission check
$org_id = $auth_user['organization_id'];
if ($auth_user['role'] !== 'admin') {
    $executive_id = $auth_user['id'];
    $check = mysqli_query($conn, "SELECT id FROM leads WHERE id = $lead_id AND assigned_to = $executive_id AND organization_id = $org_id");
} else {
    $check = mysqli_query($conn, "SELECT id FROM leads WHERE id = $lead_id AND organization_id = $org_id");
}

if (mysqli_num_rows($check) === 0) {
    sendResponse(false, 'Permission denied', null, 403);
}

$sql = "SELECT f.*, u.name as executive_name 
        FROM follow_ups f 
        JOIN users u ON f.executive_id = u.id 
        WHERE f.lead_id = $lead_id 
        ORDER BY f.created_at DESC";

$result = mysqli_query($conn, $sql);
$history = [];

while ($row = mysqli_fetch_assoc($result)) {
    $history[] = $row;
}

sendResponse(true, 'History fetched successfully', $history);
?>
