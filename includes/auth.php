<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['is_admin'] === true;
}

function loginUser($user_id, $is_admin = false) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['is_admin'] = $is_admin;
    $_SESSION['last_activity'] = time();
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login');
        exit;
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: index');
        exit;
    }
}

// Check for session timeout (30 minutes)
if (isLoggedIn() && time() - $_SESSION['last_activity'] > 1800) {
    logoutUser();
    header('Location: login.php?timeout=1');
    exit;
}

// Update last activity time
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}
?>