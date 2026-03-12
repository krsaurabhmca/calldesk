<?php
// api/upload_recording.php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];

if (!isset($_FILES['recording'])) {
    sendResponse(false, 'No recording file provided', null, 400);
}

// Metadata from app
$mobile_from_app   = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
$calltime_from_app = mysqli_real_escape_string($conn, $_POST['call_time'] ?? '');

// Use original filename (sanitized)
$original_filename = basename($_FILES['recording']['name']);
$new_filename      = preg_replace('/[^A-Za-z0-9._()-]/', '_', $original_filename);

// ─── Parse filename server-side for reliability ──────────────────────────────
// This catches cases where app sends wrong call_time (e.g. 0091-country-code bug)
//
// Supported patterns:
//   00918252669396(00918252669396)_20251128101805.mp3
//   8207472547(8207472547)_20251128103815.mp3
//   9876543210_2025-12-03_16-37-13.mp3

$mobile   = $mobile_from_app;
$calltime = $calltime_from_app;

// Pattern 1: _YYYYMMDDHHMMSS. (14 digits after underscore, before dot)
if (preg_match('/_(\d{14})\./', $new_filename, $m)) {
    $t = $m[1];
    $parsed_time = sprintf('%s-%s-%s %s:%s:%s',
        substr($t, 0, 4), substr($t, 4, 2), substr($t, 6, 2),
        substr($t, 8, 2), substr($t, 10, 2), substr($t, 12, 2)
    );
    $calltime = $parsed_time; // Always trust filename timestamp
}
// Pattern 2: YYYY-MM-DD_HH-MM-SS
elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})[_-](\d{2})-(\d{2})-(\d{2})/', $new_filename, $m)) {
    $calltime = "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}";
}

// Always take last 10 digits of mobile (strip country code 91/0091)
if (preg_match('/(\d{10,})/', $new_filename, $pm)) {
    $mobile = substr($pm[1], -10);
}

if (empty($mobile) || empty($calltime)) {
    sendResponse(false, 'Could not determine mobile or call_time from filename or metadata', null, 400);
}

$mobile   = mysqli_real_escape_string($conn, $mobile);
$calltime = mysqli_real_escape_string($conn, $calltime);

// Create directory
$upload_dir = '../uploads/recordings/' . $org_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$target_path = $upload_dir . $new_filename;

if (move_uploaded_file($_FILES['recording']['tmp_name'], $target_path)) {
    $db_path = 'uploads/recordings/' . $org_id . '/' . $new_filename;

    // ── 1. Try exact match within 10-minute window ──
    $sql = "UPDATE call_logs 
            SET recording_path = '$db_path' 
            WHERE mobile = '$mobile' 
            AND ABS(TIMESTAMPDIFF(SECOND, call_time, '$calltime')) < 600
            AND organization_id = $org_id 
            AND recording_path IS NULL
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, call_time, '$calltime')) ASC
            LIMIT 1";
    mysqli_query($conn, $sql);
    $matched = mysqli_affected_rows($conn) > 0;

    // ── 2. If no match, insert an unmatched recording log entry ──
    if (!$matched) {
        $ins = "INSERT IGNORE INTO call_logs 
                (organization_id, executive_id, mobile, call_time, type, duration, recording_path)
                VALUES ($org_id, $executive_id, '$mobile', '$calltime', 'Incoming', 0, '$db_path')";
        mysqli_query($conn, $ins);
    }

    sendResponse(true, $matched ? 'Uploaded and matched' : 'Uploaded, no matching log (saved as new entry)', [
        'path'    => $db_path,
        'mobile'  => $mobile,
        'time'    => $calltime,
        'matched' => $matched,
    ]);
} else {
    sendResponse(false, 'Failed to save uploaded file', null, 500);
}
?>
