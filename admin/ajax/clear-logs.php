<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Security.php';

// Check if user is logged in and is admin
session_start();
if (!Security::isLoggedIn() || !Security::isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$logFile = __DIR__ . '/../config/error.log';

try {
    // Clear the log file
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Log file not found']);
    }
} catch (Exception $e) {
    error_log("Error clearing logs: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to clear logs']);
}
?>
