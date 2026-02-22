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
        redirect(BASE_URL . 'login.php');
    }
}

function checkAdmin() {
    checkAuth();
    if (!isAdmin()) {
        redirect(BASE_URL . 'index.php');
    }
}
?>
