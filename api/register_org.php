<?php
// api/register_org.php
header('Content-Type: application/json');
require_once '../config/db.php';

function sendResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

// 1. Sanitize Inputs
$org_name = mysqli_real_escape_string($conn, $_POST['org_name'] ?? '');
$admin_name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
$mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($org_name) || empty($admin_name) || empty($mobile) || empty($password)) {
    sendResponse(false, 'All fields are required', null, 400);
}

// Start Transaction
mysqli_begin_transaction($conn);

try {
    // 2. Check if mobile already exists
    $check_sql = "SELECT id FROM users WHERE mobile = '$mobile'";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        throw new Exception('A user with this mobile number already exists');
    }

    // 3. Create Organization
    $org_sql = "INSERT INTO organizations (name) VALUES ('$org_name')";
    if (!mysqli_query($conn, $org_sql)) {
        throw new Exception('Failed to create organization: ' . mysqli_error($conn));
    }
    $org_id = mysqli_insert_id($conn);

    // 4. Create Admin User
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $api_token = bin2hex(random_bytes(32));
    $user_sql = "INSERT INTO users (organization_id, name, mobile, password, role, status, api_token) 
                 VALUES ($org_id, '$admin_name', '$mobile', '$hashed_password', 'admin', 1, '$api_token')";
    
    if (!mysqli_query($conn, $user_sql)) {
        throw new Exception('Failed to create admin user: ' . mysqli_error($conn));
    }
    $user_id = mysqli_insert_id($conn);

    // 5. Create Default Lead Sources
    $defaults = ['Facebook', 'Google', 'Website', 'WhatsApp', 'Referral'];
    foreach ($defaults as $source) {
        mysqli_query($conn, "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$source')");
    }

    mysqli_commit($conn);

    sendResponse(true, 'Organization and Admin registered successfully', [
        'token' => $api_token,
        'user' => [
            'id' => $user_id,
            'organization_id' => $org_id,
            'organization_name' => $org_name,
            'name' => $admin_name,
            'role' => 'admin'
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    sendResponse(false, $e->getMessage(), null, 500);
}
?>
