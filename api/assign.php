<?php
// api/assign.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$lead_id = (int)($_POST['lead_id'] ?? 0);
$assign_to = (int)($_POST['assign_to'] ?? 0); // User id of the executive

if ($lead_id <= 0 || $assign_to <= 0) {
    sendResponse(false, 'Lead ID and Executive ID (assign_to) are required', null, 400);
}

// Check if user exists and is an executive
$user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $assign_to AND role = 'executive' AND status = 1");
if (mysqli_num_rows($user_check) === 0) {
    sendResponse(false, 'Invalid executive ID or executive is inactive', null, 400);
}

$sql = "UPDATE leads SET assigned_to = $assign_to WHERE id = $lead_id";

if (mysqli_query($conn, $sql)) {
    sendResponse(true, 'Lead assigned successfully', ['lead_id' => $lead_id, 'assigned_to' => $assign_to]);
} else {
    sendResponse(false, 'Database error: ' . mysqli_error($conn), null, 500);
}
?>
