<?php
require_once 'config/config.php';
require_once 'includes/Security.php';

// Ensure user is logged in
Security::redirectIfNotLoggedIn();

// Get user ID from session
$userId = $_SESSION['user_id'];

// Mark all unread notifications as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
