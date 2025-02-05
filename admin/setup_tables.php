<?php
require_once 'config/config.php';
require_once 'config/Database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = Database::getInstance()->getConnection();
    
    // Create activity_logs table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED,
        activity VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    
    if ($db->query($sql)) {
        echo "activity_logs table created successfully\n";
    } else {
        echo "Error creating activity_logs table: " . $db->error . "\n";
    }
    
    // Create events table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        location VARCHAR(255),
        status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
        created_by BIGINT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_event_date (event_date),
        INDEX idx_status (status)
    )";
    
    if ($db->query($sql)) {
        echo "events table created successfully\n";
    } else {
        echo "Error creating events table: " . $db->error . "\n";
    }
    
    // Create alumni table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS alumni (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20),
        graduation_year YEAR NOT NULL,
        course VARCHAR(100) NOT NULL,
        current_occupation VARCHAR(100),
        company VARCHAR(100),
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        profile_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_graduation_year (graduation_year),
        INDEX idx_status (status)
    )";
    
    if ($db->query($sql)) {
        echo "alumni table created successfully\n";
    } else {
        echo "Error creating alumni table: " . $db->error . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
