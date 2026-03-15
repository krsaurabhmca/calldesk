<?php
// api/leads.php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'executives') {
        // Fetch active executives for dropdown (Admin only)
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }
        $sql = "SELECT id, name FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1";
        $result = mysqli_query($conn, $sql);
        $executives = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $executives[] = $row;
        }
        sendResponse(true, 'Executives fetched', $executives);

    } else {
        // List leads
        $search = mysqli_real_escape_string($conn, $_REQUEST['search'] ?? '');
        $where = "l.organization_id = " . (int)$org_id;
        
        if ($role !== 'admin') {
            $where .= " AND l.assigned_to = " . (int)$executive_id;
        }
        
        if ($search) {
            $where .= " AND (l.name LIKE '%$search%' OR l.mobile LIKE '%$search%')";
        }
        
        $sql = "SELECT l.*, s.source_name, u.name as assigned_to_name 
                FROM leads l 
                LEFT JOIN lead_sources s ON l.source_id = s.id 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE $where ORDER BY l.id DESC LIMIT 50";
        
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
        }
        
        $leads = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $leads[] = $row;
        }
        sendResponse(true, 'Leads fetched successfully', $leads);
    }

} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'bulk_assign') {
        // Bulk assign leads (Admin only)
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $lead_ids_str = $_POST['lead_ids'] ?? ''; // Comma separated IDs: "1,2,3"
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);

        if (empty($lead_ids_str) || empty($assigned_to)) {
             sendResponse(false, 'Lead IDs and Executive ID are required', null, 400);
        }

        // Sanitize IDs
        $ids_array = explode(',', $lead_ids_str);
        $ids_array = array_map('intval', $ids_array);
        $sanitized_ids = implode(',', $ids_array);

        if (empty($sanitized_ids)) {
            sendResponse(false, 'Invalid Lead IDs', null, 400);
        }

        $sql = "UPDATE leads SET assigned_to = $assigned_to WHERE id IN ($sanitized_ids) AND organization_id = $org_id";
        
        if (mysqli_query($conn, $sql)) {
             sendResponse(true, 'Leads assigned successfully');
        } else {
             sendResponse(false, 'Error assigning leads: ' . mysqli_error($conn), null, 500);
        }

    } else {
        // Add new lead
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
        $source_id = (int)($_POST['source_id'] ?? 0);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
        
        if (empty($name) || empty($mobile)) {
            sendResponse(false, 'Name and Mobile are required', null, 400);
        }

        // --- DUPLICATE CHECK START ---
        $check_sql = "SELECT id FROM leads WHERE mobile = '$mobile' AND organization_id = $org_id LIMIT 1";
        $check_res = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_res) > 0) {
            sendResponse(false, 'This mobile number is already registered as a lead.', null, 400);
        }
        // --- DUPLICATE CHECK END ---
        
        $sql = "INSERT INTO leads (organization_id, name, mobile, source_id, assigned_to, remarks) 
                VALUES ($org_id, '$name', '$mobile', " . ($source_id ?: "NULL") . ", $executive_id, '$remarks')";
                
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Lead added successfully', ['id' => mysqli_insert_id($conn)]);
        } else {
            sendResponse(false, 'Error adding lead: ' . mysqli_error($conn), null, 500);
        }
    }

} elseif ($method === 'DELETE') {
    // Delete lead
    // Expect lead_id in the URL
    $lead_id = (int)($_GET['id'] ?? 0);
    
    if ($lead_id <= 0) {
        sendResponse(false, 'Lead ID is required', null, 400);
    }
    
    // Check if the lead belongs to the organization (Security check)
    // Only Admin can delete leads, or the executive who owns it? 
    // Usually admin should manage deletions.
    $check_sql = "SELECT id FROM leads WHERE id = $lead_id AND organization_id = $org_id";
    $check_res = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_res) === 0) {
        sendResponse(false, 'Lead not found or unauthorized', null, 404);
    }

    $sql = "DELETE FROM leads WHERE id = $lead_id AND organization_id = $org_id";
    
    if (mysqli_query($conn, $sql)) {
        sendResponse(true, 'Lead deleted successfully');
    } else {
        sendResponse(false, 'Error deleting lead: ' . mysqli_error($conn), null, 500);
    }

} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
