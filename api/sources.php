<?php
// api/sources.php
require_once 'auth_check.php';

$org_id = $auth_user['organization_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC";
    $result = mysqli_query($conn, $sql);
    $sources = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sources[] = $row;
    }
    sendResponse(true, 'Lead sources fetched successfully', $sources);

} elseif ($method === 'POST') {
    if ($auth_user['role'] !== 'admin') {
        sendResponse(false, 'Unauthorized: Admin access required', null, 403);
    }

    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['source_name'] ?? '');
        if (empty($name)) {
            sendResponse(false, 'Source name is required');
        }

        $sql = "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$name')";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Lead source added successfully');
        } else {
            // Check for duplicate
            if (mysqli_errno($conn) == 1062) {
                sendResponse(false, 'Lead source already exists');
            }
            sendResponse(false, 'Failed to add lead source: ' . mysqli_error($conn));
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (mysqli_query($conn, "DELETE FROM lead_sources WHERE id = $id AND organization_id = $org_id")) {
            sendResponse(true, 'Lead source deleted');
        } else {
            sendResponse(false, 'Failed to delete lead source');
        }
    }
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
