<?php
// api/dashboard.php
require_once 'auth_check.php';

$org_id = (int)($auth_user['organization_id'] ?? 0);
$user_id = (int)($auth_user['id'] ?? 0);
$role = strtolower(trim($auth_user['role'] ?? 'executive'));

if ($org_id <= 0) {
    sendResponse(false, 'Organization context missing');
}

if ($role === 'admin') {
    // Admin Dashboard Stats (Organization Wide)
    $stats = [
        'total_leads' => 0,
        'today_leads' => 0,
        'today_calls' => 0,
        'today_followups' => 0,
        'converted_leads' => 0,
        'interested_leads' => 0,
        'active_executives' => 0
    ];

    // Total & Converted
    $res = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
        SUM(CASE WHEN status = 'Interested' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM leads WHERE organization_id = $org_id");
    $row = mysqli_fetch_assoc($res);
    $stats['total_leads'] = (int)$row['total'];
    $stats['converted_leads'] = (int)$row['converted'];
    $stats['interested_leads'] = (int)$row['interested'];
    $stats['today_leads'] = (int)$row['today'];

    // Today's Activity
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM call_logs WHERE organization_id = $org_id AND DATE(call_time) = CURDATE()");
    $stats['today_calls'] = (int)mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follow_ups WHERE organization_id = $org_id AND DATE(created_at) = CURDATE()");
    $stats['today_followups'] = (int)mysqli_fetch_assoc($res)['cnt'];

    // Active Executives
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1");
    $stats['active_executives'] = (int)mysqli_fetch_assoc($res)['cnt'];

    // Recent Global Leads
    $recent_sql = "SELECT l.*, s.source_name, u.name as assigned_to_name 
                  FROM leads l 
                  LEFT JOIN lead_sources s ON l.source_id = s.id 
                  LEFT JOIN users u ON l.assigned_to = u.id 
                  WHERE l.organization_id = $org_id ORDER BY l.id DESC LIMIT 5";
    $recent_res = mysqli_query($conn, $recent_sql);
    $recent_leads = [];
    while($row = mysqli_fetch_assoc($recent_res)) $recent_leads[] = $row;

} else {
    // Executive Dashboard Stats (Personal)
    $stats = [
        'my_leads' => 0,
        'today_tasks' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0,
        'my_converted' => 0,
        'performance_percent' => 0
    ];

    // Personal Leads
    $res = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted
        FROM leads WHERE assigned_to = $user_id AND organization_id = $org_id");
    $row = mysqli_fetch_assoc($res);
    $stats['my_leads'] = (int)$row['total'];
    $stats['my_converted'] = (int)$row['converted'];

    // Today's Tasks
    $res = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
        FROM follow_ups 
        WHERE executive_id = $user_id AND organization_id = $org_id AND DATE(followup_date) <= CURDATE()");
    $row = mysqli_fetch_assoc($res);
    $stats['today_tasks'] = (int)$row['total'];
    $stats['completed_tasks'] = (int)$row['completed'];
    $stats['pending_tasks'] = $stats['today_tasks'] - $stats['completed_tasks'];
    
    if ($stats['today_tasks'] > 0) {
        $stats['performance_percent'] = round(($stats['completed_tasks'] / $stats['today_tasks']) * 100);
    }

    // Recent Personal Leads
    $recent_sql = "SELECT l.*, s.source_name 
                  FROM leads l 
                  LEFT JOIN lead_sources s ON l.source_id = s.id 
                  WHERE l.assigned_to = $user_id AND l.organization_id = $org_id 
                  ORDER BY l.id DESC LIMIT 5";
    $recent_res = mysqli_query($conn, $recent_sql);
    $recent_leads = [];
    while($row = mysqli_fetch_assoc($recent_res)) $recent_leads[] = $row;
}

sendResponse(true, 'Dashboard data fetched', [
    'role' => $role,
    'stats' => $stats,
    'recent_leads' => $recent_leads
]);
?>
