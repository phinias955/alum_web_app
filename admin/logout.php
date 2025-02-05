<?php
session_start();
require_once 'config/config.php';
require_once 'controllers/UserController.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        $userController = new UserController();
        $userController->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    } catch (Exception $e) {
        error_log("Logout Activity Log Error: " . $e->getMessage());
    }
}

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php');
exit;
?>
