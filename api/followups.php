<?php
// api/followups.php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    // List followups with filters
    $where = "l.organization_id = $org_id AND f.is_completed = 0"; // Default: only show pending
    
    // Role check
    if ($role !== 'admin') {
        $where .= " AND l.assigned_to = $executive_id";
    }

    // Search (Name/Mobile)
    $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
    if ($search) {
        $where .= " AND (l.name LIKE '%$search%' OR l.mobile LIKE '%$search%')";
    }

    // Status Filter
    $status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');
    if ($status && $status !== 'All') {
        $where .= " AND l.status = '$status'";
    }

    // Date Filter (Today, Tomorrow, etc)
    $date_filter = $_GET['date_filter'] ?? '';
    $today = date('Y-m-d');
    
    if ($date_filter === 'today') {
        $where .= " AND f.next_follow_up_date = '$today'";
    } elseif ($date_filter === 'missed') {
        $where .= " AND f.next_follow_up_date < '$today'";
    } elseif ($date_filter === 'upcoming') {
        $where .= " AND f.next_follow_up_date > '$today'";
    }

    $sql = "SELECT f.*, l.name as lead_name, l.mobile as lead_mobile, l.status as lead_status, l.source_id 
            FROM follow_ups f
            JOIN leads l ON f.lead_id = l.id
            WHERE $where
            ORDER BY f.next_follow_up_date ASC, f.id DESC LIMIT 100";

    $result = mysqli_query($conn, $sql);
    $followups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $followups[] = $row;
    }
    
    sendResponse(true, 'Follow-ups fetched', $followups);

} elseif ($method === 'POST') {

$lead_id = (int)($_POST['lead_id'] ?? 0);
$executive_id = $auth_user['id'];
$remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? '');
$next_date = mysqli_real_escape_string($conn, $_POST['next_follow_up_date'] ?? '');
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? ''); // Optional: update lead status
$lead_name = mysqli_real_escape_string($conn, $_POST['name'] ?? ''); // Optional: update lead name

if ($lead_id <= 0 || empty($remark)) {
    sendResponse(false, 'Lead ID and Remark are required', null, 400);
}

// Check if lead belongs to executive or if admin
if ($auth_user['role'] !== 'admin') {
    $check = mysqli_query($conn, "SELECT id FROM leads WHERE id = $lead_id AND assigned_to = $executive_id AND organization_id = $org_id");
    if (mysqli_num_rows($check) === 0) {
        sendResponse(false, 'Permission denied: Lead not assigned to you', null, 403);
    }
} else {
    $check = mysqli_query($conn, "SELECT id FROM leads WHERE id = $lead_id AND organization_id = $org_id");
    if (mysqli_num_rows($check) === 0) {
        sendResponse(false, 'Permission denied: Lead not in your organization', null, 403);
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Mark previous follow-ups for this lead as completed
    mysqli_query($conn, "UPDATE follow_ups f JOIN leads l ON f.lead_id = l.id SET f.is_completed = 1 WHERE f.lead_id = $lead_id AND l.organization_id = $org_id");

    // 2. Insert New Follow-up
    $sql = "INSERT INTO follow_ups (organization_id, lead_id, executive_id, remark, next_follow_up_date) 
            VALUES ($org_id, $lead_id, $executive_id, '$remark', " . ($next_date ? "'$next_date'" : "NULL") . ")";
    mysqli_query($conn, $sql);

    // 3. Update Lead Status & Name if provided
    $update_parts = [];
    if ($status) $update_parts[] = "status = '$status'";
    if ($lead_name) $update_parts[] = "name = '$lead_name'";
    
    if (!empty($update_parts)) {
        mysqli_query($conn, "UPDATE leads SET " . implode(', ', $update_parts) . " WHERE id = $lead_id AND organization_id = $org_id");
    }

    mysqli_commit($conn);
    sendResponse(true, 'Follow-up updated successfully');

} catch (Exception $e) {
    mysqli_rollback($conn);
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
