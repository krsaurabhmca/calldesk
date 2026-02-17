<?php
// api/leads.php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    // List leads
    $where = ($role === 'admin') ? "1=1" : "assigned_to = $executive_id";
    $search = mysqli_real_escape_string($conn, $_REQUEST['search'] ?? '');
    if ($search) {
        $where .= " AND (name LIKE '%$search%' OR mobile LIKE '%$search%')";
    }
    
    $sql = "SELECT l.*, s.source_name FROM leads l LEFT JOIN lead_sources s ON l.source_id = s.id WHERE $where ORDER BY l.id DESC LIMIT 50";
    $result = mysqli_query($conn, $sql);
    $leads = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $leads[] = $row;
    }
    sendResponse(true, 'Leads fetched successfully', $leads);

} elseif ($method === 'POST') {
    // Add new lead
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
    $source_id = (int)($_POST['source_id'] ?? 0);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
    
    if (empty($name) || empty($mobile)) {
        sendResponse(false, 'Name and Mobile are required', null, 400);
    }
    
    $sql = "INSERT INTO leads (name, mobile, source_id, assigned_to, remarks) 
            VALUES ('$name', '$mobile', " . ($source_id ?: "NULL") . ", $executive_id, '$remarks')";
            
    if (mysqli_query($conn, $sql)) {
        sendResponse(true, 'Lead added successfully', ['id' => mysqli_insert_id($conn)]);
    } else {
        sendResponse(false, 'Error adding lead: ' . mysqli_error($conn), null, 500);
    }

} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
