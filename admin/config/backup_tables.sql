-- Security Events Table
CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Backup Logs Table
CREATE TABLE IF NOT EXISTS backup_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type ENUM('full', 'partial', 'restore') NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Backup Schedule Table
CREATE TABLE IF NOT EXISTS backup_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('full', 'partial') NOT NULL,
    frequency INT UNSIGNED NOT NULL COMMENT 'Frequency in hours',
    scheduled_time TIME NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_run TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_lastrun (status, last_run)
) ENGINE=InnoDB;
