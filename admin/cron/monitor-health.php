<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/SecurityAudit.php';

class SystemHealthMonitor {
    private $db;
    private $audit;
    private $mailer;
    
    // Thresholds for alerts
    private $thresholds = [
        'disk_space_percent' => 90, // Alert when disk usage > 90%
        'failed_logins_hour' => 50, // Alert when > 50 failed logins per hour
        'error_logs_hour' => 20,    // Alert when > 20 errors per hour
        'locked_accounts' => 5,      // Alert when > 5 accounts are locked
        'suspicious_ips_hour' => 3   // Alert when > 3 suspicious IPs per hour
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->audit = new SecurityAudit();
        $this->mailer = new NotificationController();
    }
    
    /**
     * Run health check
     */
    public function checkHealth(): array {
        $issues = [];
        
        // Check disk space
        $diskSpace = $this->audit->getSystemHealth()['disk_space'];
        if ($diskSpace['usage_percent'] > $this->thresholds['disk_space_percent']) {
            $issues[] = [
                'type' => 'disk_space',
                'severity' => 'high',
                'message' => "Disk usage at {$diskSpace['usage_percent']}%"
            ];
        }
        
        // Check failed logins
        $failedLogins = $this->getFailedLoginsLastHour();
        if ($failedLogins > $this->thresholds['failed_logins_hour']) {
            $issues[] = [
                'type' => 'failed_logins',
                'severity' => 'high',
                'message' => "$failedLogins failed login attempts in the last hour"
            ];
        }
        
        // Check error logs
        $errorCount = $this->getErrorLogsLastHour();
        if ($errorCount > $this->thresholds['error_logs_hour']) {
            $issues[] = [
                'type' => 'error_logs',
                'severity' => 'medium',
                'message' => "$errorCount errors logged in the last hour"
            ];
        }
        
        // Check locked accounts
        $lockedAccounts = $this->getLockedAccountsCount();
        if ($lockedAccounts > $this->thresholds['locked_accounts']) {
            $issues[] = [
                'type' => 'locked_accounts',
                'severity' => 'medium',
                'message' => "$lockedAccounts accounts are currently locked"
            ];
        }
        
        // Check suspicious IPs
        $suspiciousIPs = $this->getSuspiciousIPsLastHour();
        if ($suspiciousIPs > $this->thresholds['suspicious_ips_hour']) {
            $issues[] = [
                'type' => 'suspicious_ips',
                'severity' => 'high',
                'message' => "$suspiciousIPs suspicious IPs detected in the last hour"
            ];
        }
        
        // Check database connectivity
        if (!$this->checkDatabaseConnectivity()) {
            $issues[] = [
                'type' => 'database',
                'severity' => 'critical',
                'message' => "Database connectivity issues detected"
            ];
        }
        
        // Log health check results
        $this->logHealthCheck($issues);
        
        // Send alerts if there are high severity issues
        if ($this->hasHighSeverityIssues($issues)) {
            $this->sendAlerts($issues);
        }
        
        return $issues;
    }
    
    /**
     * Get failed login attempts in the last hour
     */
    private function getFailedLoginsLastHour(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get error logs count in the last hour
     */
    private function getErrorLogsLastHour(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM security_events 
            WHERE event_type = 'error' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get number of locked accounts
     */
    private function getLockedAccountsCount(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE status = 'locked'
        ");
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get number of suspicious IPs in the last hour
     */
    private function getSuspiciousIPsLastHour(): int {
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT ip_address) 
            FROM login_attempts 
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY ip_address 
            HAVING COUNT(*) > 10
        ");
        
        return $stmt->rowCount();
    }
    
    /**
     * Check database connectivity
     */
    private function checkDatabaseConnectivity(): bool {
        try {
            $this->db->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log health check results
     */
    private function logHealthCheck(array $issues): void {
        $stmt = $this->db->prepare("
            INSERT INTO system_health_logs 
            (issues_found, details, created_at)
            VALUES (?, ?, NOW())
        ");
        
        $stmt->execute([
            count($issues),
            json_encode($issues)
        ]);
    }
    
    /**
     * Check if there are high severity issues
     */
    private function hasHighSeverityIssues(array $issues): bool {
        foreach ($issues as $issue) {
            if (in_array($issue['severity'], ['high', 'critical'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Send alerts for issues
     */
    private function sendAlerts(array $issues): void {
        $subject = "System Health Alert - " . date('Y-m-d H:i:s');
        
        $body = "<h2>System Health Issues Detected</h2>\n\n";
        foreach ($issues as $issue) {
            $body .= sprintf(
                "<p><strong>%s</strong> (%s): %s</p>\n",
                ucfirst($issue['type']),
                strtoupper($issue['severity']),
                $issue['message']
            );
        }
        
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

// Run health check
try {
    $monitor = new SystemHealthMonitor();
    $issues = $monitor->checkHealth();
    
    // Log results
    if (!empty($issues)) {
        error_log("System health issues detected: " . json_encode($issues));
    }
    
} catch (Exception $e) {
    error_log("System Health Monitor Error: " . $e->getMessage());
    exit(1);
}
