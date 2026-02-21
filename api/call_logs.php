<?php
// api/call_logs.php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $auth_user['id'];
$role = $auth_user['role'];

$org_id = $auth_user['organization_id'];

// Only allow executives to see their own logs or admin everything
$where = "c.organization_id = $org_id";
if ($role !== 'admin') {
    $where .= " AND c.executive_id = $user_id";
}

// Search Filter (Name or Mobile)
$search = mysqli_real_escape_string($conn, $_REQUEST['search'] ?? '');
if ($search) {
    $where .= " AND (c.mobile LIKE '%$search%' OR l.name LIKE '%$search%')";
}

// Type Filter (Incoming, Outgoing, Missed)
$type = mysqli_real_escape_string($conn, $_REQUEST['type'] ?? 'All');
if ($type && $type !== 'All') {
    $where .= " AND c.type = '$type'";
}

// Date Filter
$date = mysqli_real_escape_string($conn, $_REQUEST['date'] ?? '');
if ($date) {
    $where .= " AND DATE(c.call_time) = '$date'";
}

$sql = "SELECT c.*, l.id as lead_id, l.name as lead_name, l.status as lead_status
        FROM call_logs c 
        LEFT JOIN leads l ON c.mobile = l.mobile 
        WHERE $where 
        GROUP BY c.id
        ORDER BY c.call_time DESC, c.id DESC 
        LIMIT 100";

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
