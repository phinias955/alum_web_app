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
    $db = new Database();
    $conn = $db->connect();

    // Default password: user@1234
    $defaultPassword = password_hash('user@1234', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$defaultPassword, $userId])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
