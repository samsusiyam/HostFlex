<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_username'])) {
    logActivity('Admin Logged Out', 'Username: ' . $_SESSION['admin_username']);
}

// Clear all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Check if custom access slug is set
$slug = trim(getSetting('admin_access_slug') ?: '');
if (!empty($slug)) {
    header('Location: /admin/index.php?access=' . urlencode($slug) . '&logged_out=1');
} else {
    header('Location: /admin/index.php?logged_out=1');
}
exit;
