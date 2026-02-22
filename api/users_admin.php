<?php
// api/users_admin.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

$org_id = (int)($auth_user['organization_id'] ?? 0);

if ($org_id <= 0) {
    sendResponse(false, 'Unauthorized: Organization context missing', ['debug_user' => $auth_user], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Fetch all users with basic stats
        $sql = "SELECT u.id, u.name, u.mobile, u.role, u.status, u.created_at,
                (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id AND l.organization_id = $org_id) as total_leads,
                (SELECT COUNT(*) FROM call_logs c WHERE c.executive_id = u.id AND c.organization_id = $org_id AND DATE(c.call_time) = CURDATE()) as calls_today,
                (SELECT COUNT(*) FROM follow_ups f WHERE f.executive_id = u.id AND f.organization_id = $org_id AND DATE(f.created_at) = CURDATE()) as activities_today
                FROM users u 
                WHERE u.organization_id = $org_id 
                ORDER BY u.role ASC, u.name ASC";
        
        $result = mysqli_query($conn, $sql);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        sendResponse(true, 'Users list fetched', $users);

    } elseif ($action === 'stats') {
        // Organization wide stats
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'calls_today' => 0,
            'leads_today' => 0,
            'follows_today' => 0
        ];

        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt, SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as active FROM users WHERE organization_id = $org_id");
        $row = mysqli_fetch_assoc($res);
        $stats['total_users'] = (int)$row['cnt'];
        $stats['active_users'] = (int)$row['active'];

        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM call_logs WHERE organization_id = $org_id AND DATE(call_time) = CURDATE()");
        $stats['calls_today'] = (int)mysqli_fetch_assoc($res)['cnt'];

        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE organization_id = $org_id AND DATE(created_at) = CURDATE()");
        $stats['leads_today'] = (int)mysqli_fetch_assoc($res)['cnt'];

        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM follow_ups WHERE organization_id = $org_id AND DATE(created_at) = CURDATE()");
        $stats['follows_today'] = (int)mysqli_fetch_assoc($res)['cnt'];

        sendResponse(true, 'Stats fetched', $stats);

    } elseif ($action === 'activity') {
        $user_id = (int)($_GET['user_id'] ?? 0);
        if ($user_id <= 0) sendResponse(false, 'User ID is required');

        // Detailed activity
        $activities = [];
        
        // Recent Calls
        $calls_sql = "SELECT c.*, l.name as lead_name 
                      FROM call_logs c 
                      LEFT JOIN leads l ON c.mobile = l.mobile AND l.organization_id = $org_id
                      WHERE c.executive_id = $user_id AND c.organization_id = $org_id 
                      ORDER BY c.call_time DESC LIMIT 20";
        $calls_res = mysqli_query($conn, $calls_sql);
        while($row = mysqli_fetch_assoc($calls_res)) {
            $row['activity_type'] = 'call';
            $row['time'] = $row['call_time'];
            $activities[] = $row;
        }

        // Recent Follow-ups
        $f_sql = "SELECT f.*, l.name as lead_name 
                  FROM follow_ups f 
                  JOIN leads l ON f.lead_id = l.id 
                  WHERE f.executive_id = $user_id AND f.organization_id = $org_id 
                  ORDER BY f.created_at DESC LIMIT 20";
        $f_res = mysqli_query($conn, $f_sql);
        while($row = mysqli_fetch_assoc($f_res)) {
            $row['activity_type'] = 'followup';
            $row['time'] = $row['created_at'];
            $activities[] = $row;
        }

        // Sort combined activities by time
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        sendResponse(true, 'User activity fetched', array_slice($activities, 0, 50));
    }

} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
        $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'executive');

        if (empty($name) || empty($mobile)) {
            sendResponse(false, 'Name and Mobile are required');
        }

        // Check if mobile exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE mobile = '$mobile'");
        if (mysqli_num_rows($check) > 0) {
            sendResponse(false, 'Mobile number already registered');
        }

        $sql = "INSERT INTO users (organization_id, name, mobile, password, role, status) VALUES ($org_id, '$name', '$mobile', '$password', '$role', 1)";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'User added successfully');
        } else {
            sendResponse(false, 'Failed to add user: ' . mysqli_error($conn));
        }

    } elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($user_id === $auth_user['id']) {
            sendResponse(false, 'Cannot deactivate yourself');
        }

        $sql = "UPDATE users SET status = $status WHERE id = $user_id AND organization_id = $org_id";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'User status updated');
        } else {
            sendResponse(false, 'Failed to update status');
        }
    }
}
?>
