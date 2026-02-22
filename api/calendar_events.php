<?php
// api/calendar_events.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$org_id = getOrgId();
$role = $_SESSION['role'];

$events = [];

// 1. Fetch Follow-ups
$where = "WHERE l.organization_id = $org_id AND f.next_follow_up_date IS NOT NULL";
if ($role !== 'admin') {
    $where .= " AND l.assigned_to = $user_id";
}

$sql = "SELECT f.*, l.name as lead_name, l.status as lead_status 
        FROM follow_ups f 
        JOIN leads l ON f.lead_id = l.id 
        $where";

$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $color = '#cbd5e1'; // Gray default
    
    // Status color mapping
    switch ($row['lead_status']) {
        case 'Converted': $color = '#10b981'; break;
        case 'Interested': $color = '#3b82f6'; break;
        case 'Pending': $color = '#f59e0b'; break;
        case 'Lost': $color = '#ef4444'; break;
    }

    $events[] = [
        'id' => 'task_' . $row['id'],
        'title' => '⚡ ' . $row['lead_name'],
        'start' => $row['next_follow_up_date'],
        'description' => $row['remark'],
        'color' => $color,
        'url' => 'lead_view.php?id=' . $row['lead_id'],
        'allDay' => true
    ];
}

// 2. Fetch Lead Creation dates (Optional but helpful)
$lead_sql = "SELECT id, name, created_at, status FROM leads WHERE organization_id = $org_id";
if ($role !== 'admin') {
    $lead_sql .= " AND assigned_to = $user_id";
}

$lead_res = mysqli_query($conn, $lead_sql);
while ($row = mysqli_fetch_assoc($lead_res)) {
    $events[] = [
        'id' => 'lead_' . $row['id'],
        'title' => '👤 New: ' . $row['name'],
        'start' => date('Y-m-d', strtotime($row['created_at'])),
        'color' => '#1e293b',
        'url' => 'lead_view.php?id=' . $row['id'],
        'allDay' => true
    ];
}

echo json_encode($events);
?>
