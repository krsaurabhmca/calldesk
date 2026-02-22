<?php
// api/sync_calls.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

// Expecting JSON array of calls
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    sendResponse(false, 'Invalid data format. Expected JSON array.', null, 400);
}

$synced_count = 0;
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];

foreach ($data as $call) {
    $mobile = mysqli_real_escape_string($conn, $call['mobile'] ?? '');
    $caller_name = mysqli_real_escape_string($conn, $call['name'] ?? '');
    $type = mysqli_real_escape_string($conn, $call['type'] ?? '');
    $duration = (int)($call['duration'] ?? 0);
    $call_time = mysqli_real_escape_string($conn, $call['call_time'] ?? ''); // Expected 'YYYY-MM-DD HH:MM:SS'
    
    if (empty($mobile) || empty($type) || empty($call_time)) continue;

    // Check if lead exists
    $lead_res = mysqli_query($conn, "SELECT id FROM leads WHERE mobile = '$mobile' AND organization_id = $org_id");
    $lead_id = (mysqli_num_rows($lead_res) > 0) ? mysqli_fetch_assoc($lead_res)['id'] : "NULL";
    
    // Check for duplicate (same number and exact time)
    $check = mysqli_query($conn, "SELECT id FROM call_logs WHERE mobile = '$mobile' AND call_time = '$call_time' AND organization_id = $org_id");
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO call_logs (organization_id, mobile, caller_name, type, duration, call_time, lead_id, executive_id) 
                VALUES ($org_id, '$mobile', '$caller_name', '$type', $duration, '$call_time', $lead_id, $executive_id)";
        if (mysqli_query($conn, $sql)) {
            $synced_count++;
        }
    }
}

sendResponse(true, "Successfully synced $synced_count call logs", ['synced' => $synced_count]);
?>
