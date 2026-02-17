<?php
// api/login.php
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

$mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($mobile) || empty($password)) {
    sendResponse(false, 'Mobile and password are required', null, 400);
}

$sql = "SELECT * FROM users WHERE mobile = '$mobile'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['password'])) {
        if ($user['status'] == 0) {
            sendResponse(false, 'Account is disabled', null, 403);
        }
        
        // Generate or reuse token
        $token = $user['api_token'];
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            mysqli_query($conn, "UPDATE users SET api_token = '$token' WHERE id = " . $user['id']);
        }
        
        sendResponse(true, 'Login successful', [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }
}

sendResponse(false, 'Invalid credentials', null, 401);
?>
