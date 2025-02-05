<?php
require_once '../config/Database.php';
require_once '../includes/Security.php';

// Initialize security
Security::initSession();

// Check if user is logged in and is admin
if (!Security::isLoggedIn() || !Security::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // First get current status
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Toggle the status
    $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $userId])) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
