<?php
require_once __DIR__ . '/../config/config.php';

class SecurityAudit {
    private $db;
    private $mailer;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = new NotificationController();
    }
    
    /**
     * Generate comprehensive security report
     */
    public function generateSecurityReport(): array {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => $this->getSecuritySummary(),
            'failed_logins' => $this->getFailedLogins(),
            'locked_accounts' => $this->getLockedAccounts(),
            'suspicious_ips' => $this->getSuspiciousIPs(),
            'password_resets' => $this->getPasswordResets(),
            'two_factor_stats' => $this->get2FAStats(),
            'security_events' => $this->getSecurityEvents(),
            'user_activities' => $this->getUserActivities(),
            'system_health' => $this->getSystemHealth()
        ];
        
        // Store report
        $this->storeReport($report);
        
        return $report;
    }
    
    /**
     * Get security metrics summary
     */
    private function getSecuritySummary(): array {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM security_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as events_24h,
                (SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as failed_logins_24h,
                (SELECT COUNT(*) FROM users WHERE status = 'locked') as locked_accounts,
                (SELECT COUNT(*) FROM users WHERE two_factor_enabled = 1) as users_with_2fa
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get failed login attempts
     */
    private function getFailedLogins(): array {
        $stmt = $this->db->query("
            SELECT 
                username,
                ip_address,
                COUNT(*) as attempt_count,
                MAX(attempted_at) as last_attempt
            FROM login_attempts 
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY username, ip_address
            HAVING attempt_count >= 3
            ORDER BY attempt_count DESC
            LIMIT 10
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get locked accounts
     */
    private function getLockedAccounts(): array {
        $stmt = $this->db->query("
            SELECT 
                username,
                email,
                failed_login_attempts,
                lockout_until,
                last_login_at,
                last_login_ip
            FROM users 
            WHERE status = 'locked'
            ORDER BY lockout_until DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get suspicious IP addresses
     */
    private function getSuspiciousIPs(): array {
        $stmt = $this->db->query("
            SELECT 
                ip_address,
                COUNT(DISTINCT username) as unique_users,
                COUNT(*) as total_attempts,
                MIN(attempted_at) as first_attempt,
                MAX(attempted_at) as last_attempt
            FROM login_attempts
            WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip_address
            HAVING total_attempts > 10
            ORDER BY total_attempts DESC
            LIMIT 10
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get password reset statistics
     */
    private function getPasswordResets(): array {
        $stmt = $this->db->query("
            SELECT 
                u.username,
                u.email,
                prt.created_at,
                prt.used,
                prt.expires_at
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY prt.created_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get 2FA statistics
     */
    private function get2FAStats(): array {
        $stmt = $this->db->query("
            SELECT 
                verification_type,
                COUNT(*) as total_attempts,
                SUM(success) as successful_attempts,
                COUNT(DISTINCT user_id) as unique_users
            FROM two_factor_logs
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY verification_type
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get security events
     */
    private function getSecurityEvents(): array {
        $stmt = $this->db->query("
            SELECT 
                se.event_type,
                se.description,
                se.ip_address,
                se.created_at,
                u.username
            FROM security_events se
            LEFT JOIN users u ON se.user_id = u.id
            WHERE se.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY se.created_at DESC
            LIMIT 50
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user activities
     */
    private function getUserActivities(): array {
        $stmt = $this->db->query("
            SELECT 
                ual.action,
                ual.entity_type,
                ual.entity_id,
                ual.ip_address,
                ual.created_at,
                u.username
            FROM user_activity_log ual
            LEFT JOIN users u ON ual.user_id = u.id
            WHERE ual.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ual.created_at DESC
            LIMIT 50
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get system health metrics
     */
    private function getSystemHealth(): array {
        return [
            'disk_space' => $this->getDiskSpace(),
            'backup_status' => $this->getBackupStatus(),
            'error_logs' => $this->getErrorLogs(),
            'session_count' => $this->getActiveSessions()
        ];
    }
    
    /**
     * Get disk space usage
     */
    private function getDiskSpace(): array {
        $totalSpace = disk_total_space(ROOT_PATH);
        $freeSpace = disk_free_space(ROOT_PATH);
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'usage_percent' => round(($usedSpace / $totalSpace) * 100, 2)
        ];
    }
    
    /**
     * Get backup status
     */
    private function getBackupStatus(): array {
        $stmt = $this->db->query("
            SELECT 
                filename,
                type,
                size,
                created_at
            FROM backup_logs
            ORDER BY created_at DESC
            LIMIT 5
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get error logs
     */
    private function getErrorLogs(): array {
        $logFile = CONFIG_PATH . 'error.log';
        $errors = [];
        
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -50);
            foreach ($lines as $line) {
                if (preg_match('/^\[(.*?)\] (.*)$/', $line, $matches)) {
                    $errors[] = [
                        'timestamp' => $matches[1],
                        'message' => $matches[2]
                    ];
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Get active sessions count
     */
    private function getActiveSessions(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM user_sessions 
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Store security report
     */
    private function storeReport(array $report): void {
        $stmt = $this->db->prepare("
            INSERT INTO security_reports 
            (report_data, created_at) 
            VALUES (?, NOW())
        ");
        
        $stmt->execute([json_encode($report)]);
    }
    
    /**
     * Send security alert
     */
    public function sendSecurityAlert(string $type, string $message, array $details = []): void {
        $subject = "Security Alert: {$type}";
        $body = "
            <h2>Security Alert: {$type}</h2>
            <p>{$message}</p>
            <h3>Details:</h3>
            <pre>" . print_r($details, true) . "</pre>
            <p>Time: " . date('Y-m-d H:i:s') . "</p>
            <p>IP: " . Security::getClientIP() . "</p>
        ";
        
        // Get admin emails
        $stmt = $this->db->query("
            SELECT email 
            FROM users 
            WHERE role = 'admin' 
            AND status = 'active'
        ");
        
        $adminEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Send alerts
        foreach ($adminEmails as $email) {
            $this->mailer->sendEmail($email, $subject, $body);
        }
    }
}
