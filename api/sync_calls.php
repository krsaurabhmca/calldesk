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
$executive_id = (int)$auth_user['id'];
$user_role = $auth_user['role'];

// If data is wrapped in a 'logs' key
if (isset($data['logs']) && is_array($data['logs'])) {
    $data = $data['logs'];
}

foreach ($data as $call) {
    if (!is_array($call)) continue;
    
    $mobile = mysqli_real_escape_string($conn, $call['mobile'] ?? '');
    $type = mysqli_real_escape_string($conn, $call['type'] ?? '');
    $duration = (int)($call['duration'] ?? 0);
    $call_time = mysqli_real_escape_string($conn, $call['call_time'] ?? ''); // Expected 'YYYY-MM-DD HH:MM:SS'
    
    if (empty($mobile) || empty($type) || empty($call_time)) continue;

    // Check if lead exists and if it belongs to this executive
    $lead_sql = "SELECT id, assigned_to FROM leads WHERE mobile = '$mobile' LIMIT 1";
    $lead_res = mysqli_query($conn, $lead_sql);
    
    $lead_id = "NULL";
    if (mysqli_num_rows($lead_res) > 0) {
        $lead_row = mysqli_fetch_assoc($lead_res);
        $lead_id = (int)$lead_row['id'];
        
        // If searching/syncing logs, we might want to auto-assign the lead if it's unassigned
        // Or at least link the log to the executive who actually made the call
    }
    
    // Check for duplicate (same number and exact time by any executive)
    $check_sql = "SELECT id FROM call_logs WHERE mobile = '$mobile' AND call_time = '$call_time'";
    $check = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO call_logs (mobile, type, duration, call_time, lead_id, executive_id) 
                VALUES ('$mobile', '$type', $duration, '$call_time', $lead_id, $executive_id)";
        if (mysqli_query($conn, $sql)) {
            $synced_count++;
        }
    }
}

sendResponse(true, "Successfully synced $synced_count call logs", ['synced' => $synced_count]);
?>
