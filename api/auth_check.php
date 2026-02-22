<?php
// api/auth_check.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
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

$headers = function_exists('getallheaders') ? getallheaders() : [];
$token = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Fallback for some PHP-FPM / FastCGI setups where Authorization is stripped from headers
if (empty($token)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
        $token = $_SERVER['PHP_AUTH_USER'];
    } elseif (isset($_REQUEST['token'])) {
        // Ultimate fallback: pass token as a normal parameter
        $token = $_REQUEST['token'];
    }
}

if (strpos($token, 'Bearer ') === 0) {
    $token = substr($token, 7);
}

if (empty($token)) {
    sendResponse(false, 'Unauthorized: No token provided', null, 401);
}

$token = mysqli_real_escape_string($conn, $token);
$user_sql = "SELECT id, name, role, organization_id FROM users WHERE api_token = '$token' AND status = 1";
$user_res = mysqli_query($conn, $user_sql);

if (!$user_res) {
    if (strpos(mysqli_error($conn), 'Unknown column \'organization_id\'') !== false) {
        sendResponse(false, 'System Migration Required: Please run https://calldesk.offerplant.com/run_saas_migration.php to update your database schema.', ['error' => mysqli_error($conn)], 500);
    }
    sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
}

if (mysqli_num_rows($user_res) === 0) {
    sendResponse(false, 'Unauthorized: Invalid or expired token', null, 401);
}

$auth_user = mysqli_fetch_assoc($user_res);
?>
