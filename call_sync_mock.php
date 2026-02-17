<?php
// call_sync_mock.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

// This is a mock script to simulate syncing calls from a mobile device
// In a real app, this would be an API endpoint receiving POST data

$user_id = $_SESSION['user_id'];
$dummy_calls = [
    ['9876543210', 'Incoming', 145, date('Y-m-d H:i:s', strtotime('-10 minutes'))],
    ['8877665544', 'Outgoing', 320, date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['7766554433', 'Missed', 0, date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['9988776655', 'Incoming', 45, date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['9123456789', 'Outgoing', 12, date('Y-m-d H:i:s', strtotime('-2 days'))],
];

foreach ($dummy_calls as $call) {
    $mobile = $call[0];
    $type = $call[1];
    $duration = $call[2];
    $time = $call[3];
    
    // Check if lead exists
    $lead_res = mysqli_query($conn, "SELECT id FROM leads WHERE mobile = '$mobile'");
    $lead_id = mysqli_num_rows($lead_res) > 0 ? mysqli_fetch_assoc($lead_res)['id'] : "NULL";
    
    // Insert if not already exists (simplistic check for mock)
    $check = mysqli_query($conn, "SELECT id FROM call_logs WHERE mobile = '$mobile' AND call_time = '$time'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO call_logs (mobile, type, duration, call_time, lead_id, executive_id) 
                VALUES ('$mobile', '$type', $duration, '$time', $lead_id, $user_id)";
        mysqli_query($conn, $sql);
    }
}

header("Location: call_logs.php?synced=1");
exit();
?>
