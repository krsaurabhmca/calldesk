<?php
// api/reports.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

$org_id = $auth_user['organization_id'];
$action = $_GET['action'] ?? 'summary';

if ($action === 'summary') {
    // Lead Status Distribution
    $sql = "SELECT COALESCE(status, 'New') as status, COUNT(*) as count 
            FROM leads 
            WHERE organization_id = $org_id 
            GROUP BY status";
    $status_res = mysqli_query($conn, $sql);
    $status_data = [];
    while($row = mysqli_fetch_assoc($status_res)) {
        $status_data[] = $row;
    }

    // Source Distribution
    $sql = "SELECT s.id, s.source_name, COUNT(l.id) as count 
            FROM lead_sources s 
            LEFT JOIN leads l ON s.id = l.source_id AND l.organization_id = $org_id
            WHERE s.organization_id = $org_id
            GROUP BY s.id";
    $source_res = mysqli_query($conn, $sql);
    $source_data = [];
    while($row = mysqli_fetch_assoc($source_res)) {
        $source_data[] = $row;
    }

    // Team Performance (Last 30 days)
    $sql = "SELECT u.id, u.name, 
            (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id AND l.organization_id = $org_id) as total_leads,
            (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND c.organization_id = $org_id) as total_calls,
            (SELECT COUNT(*) FROM follow_ups f WHERE f.executive_id = u.id AND f.organization_id = $org_id) as total_followups
            FROM users u 
            WHERE u.organization_id = $org_id AND u.role = 'executive'
            GROUP BY u.id";
    $team_res = mysqli_query($conn, $sql);
    $team_data = [];
    while($row = mysqli_fetch_assoc($team_res)) {
        // Ensure numeric values are returned as strings/ints correctly
        $row['total_leads'] = (string)($row['total_leads'] ?? '0');
        $row['total_calls'] = (string)($row['total_calls'] ?? '0');
        $row['total_followups'] = (string)($row['total_followups'] ?? '0');
        $team_data[] = $row;
    }

    sendResponse(true, 'Reports fetched', [
        'status_distribution' => $status_data,
        'source_distribution' => $source_data,
        'team_performance' => $team_data
    ]);
} elseif ($action === 'business_calls_report') {
    $sql = "SELECT 
                u.id as executive_id, 
                u.name as executive_name, 
                DATE(c.call_time) as call_date, 
                SUM(c.duration) as total_duration,
                COUNT(c.id) as call_count
            FROM call_logs c
            JOIN leads l ON c.mobile = l.mobile AND c.organization_id = l.organization_id
            JOIN users u ON c.executive_id = u.id
            WHERE c.organization_id = $org_id
            GROUP BY u.id, DATE(c.call_time)
            ORDER BY call_date DESC, u.name ASC";
            
    $res = mysqli_query($conn, $sql);
    $report_data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $report_data[] = $row;
    }
    
    sendResponse(true, 'Business calls report fetched', $report_data);
}
?>
