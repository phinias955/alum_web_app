<?php
require_once __DIR__ . '/../config/config.php';

class SecurityMetrics {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get comprehensive security metrics
     */
    public function getMetrics(): array {
        return [
            'user_metrics' => $this->getUserMetrics(),
            'authentication_metrics' => $this->getAuthenticationMetrics(),
            'session_metrics' => $this->getSessionMetrics(),
            'vulnerability_metrics' => $this->getVulnerabilityMetrics(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'compliance_metrics' => $this->getComplianceMetrics(),
            'threat_metrics' => $this->getThreatMetrics(),
            'backup_metrics' => $this->getBackupMetrics()
        ];
    }
    
    /**
     * Get user-related security metrics
     */
    private function getUserMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM users WHERE two_factor_enabled = 1) as users_with_2fa,
                (SELECT COUNT(*) FROM users WHERE last_password_change < DATE_SUB(NOW(), INTERVAL 90 DAY)) as users_need_password_change,
                (SELECT COUNT(*) FROM users WHERE failed_login_attempts >= 3) as users_with_failed_attempts,
                (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_count,
                (SELECT COUNT(*) FROM users WHERE last_login_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) as inactive_users,
                (SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_users_24h
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get authentication-related metrics
     */
    private function getAuthenticationMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM login_attempts WHERE success = 1 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as successful_logins_24h,
                (SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as failed_logins_24h,
                (SELECT COUNT(DISTINCT ip_address) FROM login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as unique_failed_ips_24h,
                (SELECT COUNT(*) FROM two_factor_logs WHERE success = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as successful_2fa_24h,
                (SELECT COUNT(*) FROM two_factor_logs WHERE success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as failed_2fa_24h,
                (SELECT COUNT(*) FROM password_reset_tokens WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as password_resets_24h
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get session-related metrics
     */
    private function getSessionMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_active_sessions,
                COUNT(DISTINCT user_id) as unique_users_with_sessions,
                COUNT(DISTINCT ip_address) as unique_session_ips,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, last_activity)) as avg_session_duration_minutes
            FROM user_sessions
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get vulnerability metrics
     */
    private function getVulnerabilityMetrics(): array {
        return [
            'failed_login_patterns' => $this->getFailedLoginPatterns(),
            'suspicious_activities' => $this->getSuspiciousActivities(),
            'password_strength' => $this->getPasswordStrengthMetrics(),
            'rate_limiting' => $this->getRateLimitingMetrics()
        ];
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array {
        return [
            'response_times' => $this->getResponseTimeMetrics(),
            'error_rates' => $this->getErrorRates(),
            'resource_usage' => $this->getResourceUsageMetrics(),
            'database_metrics' => $this->getDatabaseMetrics()
        ];
    }
    
    /**
     * Get compliance metrics
     */
    private function getComplianceMetrics(): array {
        return [
            'password_policy' => $this->getPasswordPolicyCompliance(),
            'session_policy' => $this->getSessionPolicyCompliance(),
            'access_control' => $this->getAccessControlMetrics(),
            'audit_logs' => $this->getAuditLogMetrics()
        ];
    }
    
    /**
     * Get threat metrics
     */
    private function getThreatMetrics(): array {
        return [
            'blocked_ips' => $this->getBlockedIPMetrics(),
            'attack_patterns' => $this->getAttackPatternMetrics(),
            'security_events' => $this->getSecurityEventMetrics(),
            'threat_levels' => $this->getThreatLevelMetrics()
        ];
    }
    
    /**
     * Get backup metrics
     */
    private function getBackupMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_backups,
                MAX(created_at) as last_backup_time,
                AVG(size) as avg_backup_size,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_backups
            FROM backup_logs
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get failed login patterns
     */
    private function getFailedLoginPatterns(): array {
        $stmt = $this->db->query("
            SELECT 
                HOUR(attempted_at) as hour,
                COUNT(*) as attempts
            FROM login_attempts
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(attempted_at)
            ORDER BY hour
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get suspicious activities
     */
    private function getSuspiciousActivities(): array {
        $stmt = $this->db->query("
            SELECT 
                event_type,
                COUNT(*) as count
            FROM security_events
            WHERE severity IN ('high', 'critical')
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY event_type
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get password strength metrics
     */
    private function getPasswordStrengthMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                AVG(LENGTH(password_hash)) as avg_password_length,
                MIN(LENGTH(password_hash)) as min_password_length,
                COUNT(CASE WHEN LENGTH(password_hash) < 60 THEN 1 END) as weak_passwords
            FROM users
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get rate limiting metrics
     */
    private function getRateLimitingMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_hits,
                COUNT(DISTINCT ip_address) as unique_ips,
                MAX(hit_count) as max_hits
            FROM rate_limit_logs
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get response time metrics
     */
    private function getResponseTimeMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                MIN(response_time) as min_response_time
            FROM performance_logs
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get error rates
     */
    private function getErrorRates(): array {
        $stmt = $this->db->query("
            SELECT
                error_type,
                COUNT(*) as count
            FROM error_logs
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY error_type
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get resource usage metrics
     */
    private function getResourceUsageMetrics(): array {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'disk_free' => disk_free_space(__DIR__),
            'disk_total' => disk_total_space(__DIR__)
        ];
    }
    
    /**
     * Get database metrics
     */
    private function getDatabaseMetrics(): array {
        $metrics = [];
        
        // Get table sizes
        $stmt = $this->db->query("
            SELECT 
                table_name,
                table_rows,
                data_length + index_length as size_bytes
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");
        
        $metrics['tables'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get other database metrics
        $stmt = $this->db->query("SHOW GLOBAL STATUS");
        $status = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status[$row['Variable_name']] = $row['Value'];
        }
        
        $metrics['status'] = $status;
        
        return $metrics;
    }
    
    /**
     * Get password policy compliance metrics
     */
    private function getPasswordPolicyCompliance(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_users,
                SUM(CASE WHEN last_password_change > DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as compliant_users,
                SUM(CASE WHEN password_history IS NOT NULL THEN 1 ELSE 0 END) as users_with_history
            FROM users
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get session policy compliance metrics
     */
    private function getSessionPolicyCompliance(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_sessions,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, last_activity) <= 24 THEN 1 ELSE 0 END) as compliant_sessions,
                COUNT(DISTINCT user_id) as unique_users
            FROM user_sessions
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get access control metrics
     */
    private function getAccessControlMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                role,
                COUNT(*) as user_count
            FROM users
            GROUP BY role
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get audit log metrics
     */
    private function getAuditLogMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                action,
                COUNT(*) as count
            FROM user_activity_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY action
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get blocked IP metrics
     */
    private function getBlockedIPMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                COUNT(DISTINCT ip_address) as total_blocked_ips,
                MAX(created_at) as last_block_time
            FROM blocked_ips
            WHERE expires_at > NOW()
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get attack pattern metrics
     */
    private function getAttackPatternMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                attack_type,
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM security_events
            WHERE event_type = 'attack'
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY attack_type
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get security event metrics
     */
    private function getSecurityEventMetrics(): array {
        $stmt = $this->db->query("
            SELECT
                severity,
                COUNT(*) as count
            FROM security_events
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY severity
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get threat level metrics
     */
    private function getThreatLevelMetrics(): array {
        $metrics = [
            'current_threat_level' => $this->calculateThreatLevel(),
            'threat_indicators' => $this->getThreatIndicators()
        ];
        
        return $metrics;
    }
    
    /**
     * Calculate current threat level
     */
    private function calculateThreatLevel(): string {
        $score = 0;
        
        // Check failed logins
        $failedLogins = $this->getFailedLoginsLastHour();
        $score += ($failedLogins > 50) ? 3 : (($failedLogins > 20) ? 2 : (($failedLogins > 10) ? 1 : 0));
        
        // Check suspicious IPs
        $suspiciousIPs = $this->getSuspiciousIPsLastHour();
        $score += ($suspiciousIPs > 5) ? 3 : (($suspiciousIPs > 3) ? 2 : (($suspiciousIPs > 1) ? 1 : 0));
        
        // Check error rates
        $errorCount = $this->getErrorCountLastHour();
        $score += ($errorCount > 100) ? 3 : (($errorCount > 50) ? 2 : (($errorCount > 20) ? 1 : 0));
        
        // Determine threat level
        if ($score >= 7) return 'critical';
        if ($score >= 5) return 'high';
        if ($score >= 3) return 'medium';
        return 'low';
    }
    
    /**
     * Get threat indicators
     */
    private function getThreatIndicators(): array {
        return [
            'failed_logins' => $this->getFailedLoginsLastHour(),
            'suspicious_ips' => $this->getSuspiciousIPsLastHour(),
            'error_count' => $this->getErrorCountLastHour(),
            'blocked_ips' => $this->getBlockedIPCount()
        ];
    }
    
    /**
     * Get failed logins in last hour
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
     * Get suspicious IPs in last hour
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
     * Get error count in last hour
     */
    private function getErrorCountLastHour(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM error_logs 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Get blocked IP count
     */
    private function getBlockedIPCount(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM blocked_ips 
            WHERE expires_at > NOW()
        ");
        
        return (int)$stmt->fetchColumn();
    }
}
