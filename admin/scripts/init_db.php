<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Connect without database first
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    $result = $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    if (!$result) {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Switch to the database
    $conn->select_db(DB_NAME);

    // Drop tables in correct order (child tables first)
    $tables = [
        'password_reset_tokens',
        'activity_logs',
        'security_logs',
        'login_attempts',
        'alumni',
        'users'
    ];

    foreach ($tables as $table) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("DROP TABLE IF EXISTS $table");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    // Create users table
    $result = $conn->query("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    // Create alumni table
    $result = $conn->query("
        CREATE TABLE alumni (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20),
            course VARCHAR(100) NOT NULL,
            graduation_year INT NOT NULL,
            current_position VARCHAR(100),
            company VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating alumni table: " . $conn->error);
    }

    // Create password reset tokens table
    $result = $conn->query("
        CREATE TABLE password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(100) NOT NULL UNIQUE,
            used BOOLEAN DEFAULT FALSE,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating password reset tokens table: " . $conn->error);
    }

    // Create activity logs table
    $result = $conn->query("
        CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating activity logs table: " . $conn->error);
    }

    // Create security logs table
    $result = $conn->query("
        CREATE TABLE security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating security logs table: " . $conn->error);
    }

    // Create login attempts table
    $result = $conn->query("
        CREATE TABLE login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success BOOLEAN DEFAULT FALSE,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (email, ip_address, attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
        throw new Exception("Error creating login attempts table: " . $conn->error);
    }

    echo "Database tables created successfully!\n";
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
