<?php
// api/whatsapp_messages.php
require_once 'auth_check.php';

$executive_id = $auth_user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM whatsapp_messages WHERE executive_id = $executive_id ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    sendResponse(true, 'Messages fetched', $messages);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $title = mysqli_real_escape_string($conn, $data['title'] ?? '');
    $message = mysqli_real_escape_string($conn, $data['message'] ?? '');
    $is_default = (int)($data['is_default'] ?? 0);
    $action = $data['action'] ?? 'add'; // 'add', 'set_default', 'delete'

    if ($action === 'add') {
        if (empty($title) || empty($message)) {
            sendResponse(false, 'Title and Message are required');
        }

        if ($is_default) {
            mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id");
        }

        $sql = "INSERT INTO whatsapp_messages (executive_id, title, message, is_default) VALUES ($executive_id, '$title', '$message', $is_default)";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Message saved');
        } else {
            sendResponse(false, 'Failed to save message');
        }

    } elseif ($action === 'set_default') {
        $id = (int)$data['id'];
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id");
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 1 WHERE id = $id AND executive_id = $executive_id");
        sendResponse(true, 'Default message updated');

    } elseif ($action === 'delete') {
        $id = (int)$data['id'];
        mysqli_query($conn, "DELETE FROM whatsapp_messages WHERE id = $id AND executive_id = $executive_id");
        sendResponse(true, 'Message deleted');
    }
}
?>
