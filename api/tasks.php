<?php
// api/tasks.php
require_once 'auth_check.php';

$executive_id = $auth_user['id'];
$role = $auth_user['role'];
$today = date('Y-m-d');

$filter = $_REQUEST['filter'] ?? 'all'; // 'today', 'upcoming', 'all'

$org_id = $auth_user['organization_id'];
$where = ($role === 'admin') ? "l.organization_id = $org_id" : "l.organization_id = $org_id AND f.executive_id = $executive_id";

if ($filter === 'today') {
    $where .= " AND f.next_follow_up_date = '$today'";
} elseif ($filter === 'upcoming') {
    $where .= " AND f.next_follow_up_date > '$today'";
}

$sql = "SELECT f.*, l.name as lead_name, l.mobile as lead_mobile, l.status as lead_status 
        FROM follow_ups f
        JOIN leads l ON f.lead_id = l.id 
        WHERE $where 
        ORDER BY f.is_completed ASC, f.next_follow_up_date ASC, f.id DESC";

$result = mysqli_query($conn, $sql);
$tasks = [];

while ($row = mysqli_fetch_assoc($result)) {
    $tasks[] = $row;
}

sendResponse(true, 'Tasks fetched successfully', [
    'count' => count($tasks),
    'filter' => $filter,
    'tasks' => $tasks
]);
?>
