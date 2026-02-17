<?php
// api/signup.php
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

$name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
$mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($mobile) || empty($password)) {
    sendResponse(false, 'Name, mobile, and password are required', null, 400);
}

// Check if user already exists
$check_sql = "SELECT id FROM users WHERE mobile = '$mobile'";
$check_result = mysqli_query($conn, $check_sql);
if (mysqli_num_rows($check_result) > 0) {
    sendResponse(false, 'User with this mobile number already exists', null, 409);
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Generate token
$token = bin2hex(random_bytes(32));

// Insert new executive
$sql = "INSERT INTO users (name, mobile, password, role, status, api_token) VALUES ('$name', '$mobile', '$hashed_password', 'executive', 1, '$token')";

if (mysqli_query($conn, $sql)) {
    $user_id = mysqli_insert_id($conn);
    sendResponse(true, 'Registration successful', [
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'role' => 'executive'
        ]
    ], 201); // 201 Created
} else {
    sendResponse(false, 'Registration failed: ' . mysqli_error($conn), null, 500);
}
?>
