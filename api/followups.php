<?php
// api/followups.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$lead_id = (int)($_POST['lead_id'] ?? 0);
$executive_id = $auth_user['id'];
$remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? '');
$next_date = mysqli_real_escape_string($conn, $_POST['next_follow_up_date'] ?? '');
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? ''); // Optional: update lead status

if ($lead_id <= 0 || empty($remark)) {
    sendResponse(false, 'Lead ID and Remark are required', null, 400);
}

// Check if lead belongs to executive or if admin
if ($auth_user['role'] !== 'admin') {
    $check = mysqli_query($conn, "SELECT id FROM leads WHERE id = $lead_id AND assigned_to = $executive_id");
    if (mysqli_num_rows($check) === 0) {
        sendResponse(false, 'Permission denied: Lead not assigned to you', null, 403);
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Insert Follow-up
    $sql = "INSERT INTO follow_ups (lead_id, executive_id, remark, next_follow_up_date) 
            VALUES ($lead_id, $executive_id, '$remark', " . ($next_date ? "'$next_date'" : "NULL") . ")";
    mysqli_query($conn, $sql);

    // 2. Update Lead Status if provided
    if ($status) {
        mysqli_query($conn, "UPDATE leads SET status = '$status' WHERE id = $lead_id");
    }

    mysqli_commit($conn);
    sendResponse(true, 'Follow-up updated successfully');

} catch (Exception $e) {
    mysqli_rollback($conn);
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
