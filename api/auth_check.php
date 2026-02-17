<?php
// api/auth_check.php
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

$headers = getallheaders();
$token = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (strpos($token, 'Bearer ') === 0) {
    $token = substr($token, 7);
}

if (empty($token)) {
    sendResponse(false, 'Unauthorized: No token provided', null, 401);
}

$token = mysqli_real_escape_string($conn, $token);
$user_sql = "SELECT id, name, role FROM users WHERE api_token = '$token' AND status = 1";
$user_res = mysqli_query($conn, $user_sql);

if (!$user_res || mysqli_num_rows($user_res) === 0) {
    sendResponse(false, 'Unauthorized: Invalid or expired token', null, 401);
}

$auth_user = mysqli_fetch_assoc($user_res);
?>
