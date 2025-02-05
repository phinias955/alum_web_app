<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Security.php';

// Check if user is logged in and is admin
session_start();
if (!Security::isLoggedIn() || !Security::isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Unauthorized access';
    exit;
}

$logFile = __DIR__ . '/../config/error.log';

if (file_exists($logFile)) {
    // Set headers for file download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="error_logs_' . date('Y-m-d_H-i-s') . '.log"');
    header('Content-Length: ' . filesize($logFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file contents
    readfile($logFile);
} else {
    header('HTTP/1.1 404 Not Found');
    echo 'Log file not found';
}
?>
