<?php
// api/dashboard.php
require_once 'auth_check.php';

$org_id = (int)($auth_user['organization_id'] ?? 0);
$user_id = (int)($auth_user['id'] ?? 0);
$role = strtolower(trim($auth_user['role'] ?? 'executive'));

if ($org_id <= 0) {
    sendResponse(false, 'Organization context missing');
}

function safe_query($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        sendResponse(false, 'Database error: ' . mysqli_error($conn), ['sql' => $sql], 500);
    }
    return $res;
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
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
        SUM(CASE WHEN status = 'Interested' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM leads WHERE organization_id = $org_id";
    $res = safe_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    $stats['total_leads'] = (int)$row['total'];
    $stats['converted_leads'] = (int)$row['converted'];
    $stats['interested_leads'] = (int)$row['interested'];
    $stats['today_leads'] = (int)$row['today'];

    // Today's Activity
    $sql = "SELECT COUNT(*) as cnt FROM call_logs WHERE organization_id = $org_id AND DATE(call_time) = CURDATE()";
    $res = safe_query($conn, $sql);
    $stats['today_calls'] = (int)mysqli_fetch_assoc($res)['cnt'];

    $sql = "SELECT COUNT(*) as cnt FROM follow_ups WHERE organization_id = $org_id AND DATE(created_at) = CURDATE()";
    $res = safe_query($conn, $sql);
    $stats['today_followups'] = (int)mysqli_fetch_assoc($res)['cnt'];

    // Active Executives
    $sql = "SELECT COUNT(*) as cnt FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1";
    $res = safe_query($conn, $sql);
    $stats['active_executives'] = (int)mysqli_fetch_assoc($res)['cnt'];

    // Recent Global Leads
    $recent_sql = "SELECT l.*, s.source_name, u.name as assigned_to_name 
                  FROM leads l 
                  LEFT JOIN lead_sources s ON l.source_id = s.id 
                  LEFT JOIN users u ON l.assigned_to = u.id 
                  WHERE l.organization_id = $org_id ORDER BY l.id DESC LIMIT 5";
    $recent_res = safe_query($conn, $recent_sql);
    $recent_leads = [];
    while($row = mysqli_fetch_assoc($recent_res)) $recent_leads[] = $row;

    // Executive Performance (Today)
    $exec_stats_sql = "SELECT u.id, u.name,
        (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND DATE(c.call_time) = CURDATE()) as total_calls,
        (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND c.type = 'Missed' AND DATE(c.call_time) = CURDATE()) as missed_calls,
        (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND c.type = 'Incoming' AND DATE(c.call_time) = CURDATE()) as incoming_calls,
        (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND c.type = 'Outgoing' AND DATE(c.call_time) = CURDATE()) as outgoing_calls,
        (SELECT COUNT(*) FROM follow_ups f WHERE f.executive_id = u.id AND DATE(f.next_follow_up_date) = CURDATE() AND f.is_completed = 0) as pending_tasks
        FROM users u 
        WHERE u.organization_id = $org_id AND u.role = 'executive' AND u.status = 1";
    $exec_res = safe_query($conn, $exec_stats_sql);
    $executive_performance = [];
    while($row = mysqli_fetch_assoc($exec_res)) $executive_performance[] = $row;

    $extra_data = ['executive_performance' => $executive_performance];

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
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted
        FROM leads WHERE assigned_to = $user_id AND organization_id = $org_id";
    $res = safe_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    $stats['my_leads'] = (int)$row['total'];
    $stats['my_converted'] = (int)$row['converted'];

    // Today's Tasks
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
        FROM follow_ups 
        WHERE executive_id = $user_id AND organization_id = $org_id AND DATE(next_follow_up_date) <= CURDATE()";
    $res = safe_query($conn, $sql);
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
    $recent_res = safe_query($conn, $recent_sql);
    $recent_leads = [];
    while($row = mysqli_fetch_assoc($recent_res)) $recent_leads[] = $row;
}

sendResponse(true, 'Dashboard data fetched', array_merge([
    'role' => $role,
    'stats' => $stats,
    'recent_leads' => $recent_leads
], $extra_data ?? []));
?>
