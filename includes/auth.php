<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Role checking functions
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_viewer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'viewer';
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ../pages/access_denied.php');
        exit;
    }
}
