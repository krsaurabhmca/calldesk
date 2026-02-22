<?php
// api/whatsapp_messages.php
require_once 'auth_check.php';

$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$executive_id || !$org_id) {
        sendResponse(false, 'Missing executive or organization ID');
    }

    $sql = "SELECT * FROM whatsapp_messages WHERE executive_id = $executive_id AND organization_id = $org_id ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        // Table might be missing or other SQL error
        sendResponse(false, 'Database error: ' . mysqli_error($conn), ['sql' => $sql]);
    }

    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    sendResponse(true, 'Messages fetched', $messages);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$executive_id || !$org_id) {
        sendResponse(false, 'Missing executive or organization ID');
    }

    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
    $is_default = (int)($_POST['is_default'] ?? 0);
    $action = $_POST['action'] ?? 'add'; 

    if ($action === 'add') {
        if (empty($title) || empty($message)) {
            sendResponse(false, 'Title and Message are required');
        }

        if ($is_default) {
            mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id");
        }

        $sql = "INSERT INTO whatsapp_messages (organization_id, executive_id, title, message, is_default) VALUES ($org_id, $executive_id, '$title', '$message', $is_default)";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Message saved');
        } else {
            sendResponse(false, 'Failed to save message: ' . mysqli_error($conn));
        }

    } elseif ($action === 'set_default') {
        $id = (int)($_POST['id'] ?? 0);
        
        $res1 = mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id");
        $res2 = mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 1 WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id");
        
        if ($res1 && $res2) {
            sendResponse(true, 'Default message updated');
        } else {
            sendResponse(false, 'Failed to update default: ' . mysqli_error($conn));
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (mysqli_query($conn, "DELETE FROM whatsapp_messages WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id")) {
            sendResponse(true, 'Message deleted');
        } else {
            sendResponse(false, 'Failed to delete message: ' . mysqli_error($conn));
        }
    }
}
?>
