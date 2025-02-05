<?php
require_once 'config.php';
require_once 'Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Add notification_preferences column if it doesn't exist
    $sql = "SHOW COLUMNS FROM users LIKE 'notification_preferences'";
    $result = $db->query($sql);
    
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE users ADD COLUMN notification_preferences JSON DEFAULT NULL";
        if ($db->query($sql)) {
            echo "Added notification_preferences column to users table\n";
        } else {
            echo "Error adding notification_preferences column: " . $db->error . "\n";
        }
    } else {
        echo "notification_preferences column already exists\n";
    }

    // Add 2fa_enabled column if it doesn't exist
    $sql = "SHOW COLUMNS FROM users LIKE '2fa_enabled'";
    $result = $db->query($sql);
    
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE users ADD COLUMN 2fa_enabled TINYINT(1) NOT NULL DEFAULT 0";
        if ($db->query($sql)) {
            echo "Added 2fa_enabled column to users table\n";
        } else {
            echo "Error adding 2fa_enabled column: " . $db->error . "\n";
        }
    } else {
        echo "2fa_enabled column already exists\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
