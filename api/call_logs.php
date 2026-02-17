<?php
// api/call_logs.php
require_once 'auth_check.php';

$user_id = $auth_user['id'];
$role = $auth_user['role'];

// Only allow executives to see their own logs or admin everything
$where = "WHERE 1=1";
if ($role !== 'admin') {
    $where .= " AND (c.executive_id = $user_id OR c.executive_id IS NULL)";
}

$sql = "SELECT c.*, l.name as lead_name, l.status as lead_status
        FROM call_logs c 
        LEFT JOIN leads l ON c.lead_id = l.id 
        $where 
        ORDER BY c.call_time DESC 
        LIMIT 50";

$result = mysqli_query($conn, $sql);
if (!$result) {
    sendResponse(false, "Database error: " . mysqli_error($conn), null, 500);
}
$logs = [];

while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}

sendResponse(true, "Call logs fetched", ['logs' => $logs]);
?>
