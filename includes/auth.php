<?php
// includes/auth.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getOrgId() {
    return $_SESSION['organization_id'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function checkAuth() {
    if (!isLoggedIn()) {
        redirect('/calldesk/login.php');
    }
}

function checkAdmin() {
    checkAuth();
    if (!isAdmin()) {
        redirect('/calldesk/index.php');
    }
}
?>
