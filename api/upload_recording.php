<?php
// api/upload_recording.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];

// Check if file is uploaded
if (!isset($_FILES['recording'])) {
    sendResponse(false, 'No recording file provided', null, 400);
}

// Metadata
$mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$call_time = mysqli_real_escape_string($conn, $_POST['call_time'] ?? ''); // Expected 'YYYY-MM-DD HH:MM:SS'
$filename = $_FILES['recording']['name'];

if (empty($mobile) || empty($call_time)) {
    sendResponse(false, 'Missing mobile or call_time metadata', null, 400);
}

// Create directory if not exists
$upload_dir = '../uploads/recordings/' . $org_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_ext = pathinfo($filename, PATHINFO_EXTENSION);
$new_filename = $mobile . '_' . str_replace([' ', ':'], ['_', '-'], $call_time) . '.' . $file_ext;
$target_path = $upload_dir . $new_filename;

if (move_uploaded_file($_FILES['recording']['tmp_name'], $target_path)) {
    $db_path = 'uploads/recordings/' . $org_id . '/' . $new_filename;
    
    // Find the matching call log
    // Increased window to 600 seconds (10 mins) for better matching
    $sql = "UPDATE call_logs 
            SET recording_path = '$db_path' 
            WHERE mobile = '$mobile' 
            AND ABS(TIMESTAMPDIFF(SECOND, call_time, '$call_time')) < 600
            AND organization_id = $org_id 
            AND recording_path IS NULL
            LIMIT 1";

    // For debugging, let's see if there are ANY logs for this mobile
    $debug_sql = "SELECT id, call_time, TIMESTAMPDIFF(SECOND, call_time, '$call_time') as diff 
                 FROM call_logs 
                 WHERE mobile = '$mobile' 
                 AND organization_id = $org_id 
                 ORDER BY ABS(TIMESTAMPDIFF(SECOND, call_time, '$call_time')) ASC 
                 LIMIT 3";
    $debug_res = mysqli_query($conn, $debug_sql);
    $nearby_logs = [];
    while($drow = mysqli_fetch_assoc($debug_res)) {
        $nearby_logs[] = $drow;
    }

    if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) > 0) {
        sendResponse(true, 'Recording uploaded and matched successfully', ['path' => $db_path]);
    } else {
        // If no match found, we still save it but provide debug info
        sendResponse(true, 'Recording uploaded but no matching call log found in time window', [
            'path' => $db_path, 
            'unmatched' => true,
            'debug' => [
                'target_mobile' => $mobile,
                'target_time' => $call_time,
                'nearby_logs' => $nearby_logs,
                'sql' => $sql
            ]
        ]);
    }
} else {
    sendResponse(false, 'Failed to save uploaded file', null, 500);
}
?>
