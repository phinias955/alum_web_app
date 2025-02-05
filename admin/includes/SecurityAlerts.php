<?php
require_once __DIR__ . '/../config/config.php';

class SecurityAlerts {
    private $db;
    private $mailer;
    private $metrics;
    
    // Alert thresholds
    private $thresholds = [
        'failed_logins' => [
            'medium' => 20,
            'high' => 50,
            'critical' => 100
        ],
        'suspicious_ips' => [
            'medium' => 3,
            'high' => 5,
            'critical' => 10
        ],
        'error_rate' => [
            'medium' => 20,
            'high' => 50,
            'critical' => 100
        ],
        'disk_usage' => [
            'medium' => 80,
            'high' => 90,
            'critical' => 95
        ],
        'session_duration' => [
            'medium' => 4,  // hours
            'high' => 8,
            'critical' => 12
        ],
        'password_age' => [
            'medium' => 60, // days
            'high' => 90,
            'critical' => 120
        ]
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = new NotificationController();
        $this->metrics = new SecurityMetrics();
    }
    
    /**
     * Check all security conditions and generate alerts
     */
    public function checkSecurityConditions(): array {
        $alerts = [];
        
        // Get current metrics
        $metrics = $this->metrics->getMetrics();
        
        // Check authentication metrics
        $alerts = array_merge($alerts, $this->checkAuthenticationMetrics($metrics['authentication_metrics']));
        
        // Check vulnerability metrics
        $alerts = array_merge($alerts, $this->checkVulnerabilityMetrics($metrics['vulnerability_metrics']));
        
        // Check performance metrics
        $alerts = array_merge($alerts, $this->checkPerformanceMetrics($metrics['performance_metrics']));
        
        // Check compliance metrics
        $alerts = array_merge($alerts, $this->checkComplianceMetrics($metrics['compliance_metrics']));
        
        // Check threat metrics
        $alerts = array_merge($alerts, $this->checkThreatMetrics($metrics['threat_metrics']));
        
        // Process and store alerts
        $this->processAlerts($alerts);
        
        return $alerts;
    }
    
    /**
     * Check authentication metrics
     */
    private function checkAuthenticationMetrics(array $metrics): array {
        $alerts = [];
        
        // Check failed logins
        $failedLogins = $metrics['failed_logins_24h'];
        if ($failedLogins >= $this->thresholds['failed_logins']['critical']) {
            $alerts[] = $this->createAlert(
                'failed_logins',
                'critical',
                "Critical: $failedLogins failed login attempts in 24 hours",
                ['count' => $failedLogins]
            );
        } elseif ($failedLogins >= $this->thresholds['failed_logins']['high']) {
            $alerts[] = $this->createAlert(
                'failed_logins',
                'high',
                "High number of failed login attempts: $failedLogins in 24 hours",
                ['count' => $failedLogins]
            );
        }
        
        // Check 2FA failures
        $failed2FA = $metrics['failed_2fa_24h'];
        if ($failed2FA > 10) {
            $alerts[] = $this->createAlert(
                'two_factor_auth',
                'high',
                "High number of 2FA failures: $failed2FA in 24 hours",
                ['count' => $failed2FA]
            );
        }
        
        return $alerts;
    }
    
    /**
     * Check vulnerability metrics
     */
    private function checkVulnerabilityMetrics(array $metrics): array {
        $alerts = [];
        
        // Check suspicious IPs
        $suspiciousIPs = count($metrics['suspicious_activities']);
        if ($suspiciousIPs >= $this->thresholds['suspicious_ips']['critical']) {
            $alerts[] = $this->createAlert(
                'suspicious_ips',
                'critical',
                "Critical: $suspiciousIPs suspicious IPs detected",
                ['count' => $suspiciousIPs]
            );
        }
        
        // Check password strength
        if ($metrics['password_strength']['weak_passwords'] > 0) {
            $alerts[] = $this->createAlert(
                'weak_passwords',
                'high',
                "{$metrics['password_strength']['weak_passwords']} users have weak passwords",
                ['count' => $metrics['password_strength']['weak_passwords']]
            );
        }
        
        return $alerts;
    }
    
    /**
     * Check performance metrics
     */
    private function checkPerformanceMetrics(array $metrics): array {
        $alerts = [];
        
        // Check response times
        $avgResponseTime = $metrics['response_times']['avg_response_time'];
        if ($avgResponseTime > 1000) { // 1 second
            $alerts[] = $this->createAlert(
                'high_response_time',
                'medium',
                "High average response time: " . round($avgResponseTime) . "ms",
                ['avg_time' => $avgResponseTime]
            );
        }
        
        // Check error rates
        $errorCount = array_sum(array_column($metrics['error_rates'], 'count'));
        if ($errorCount >= $this->thresholds['error_rate']['critical']) {
            $alerts[] = $this->createAlert(
                'high_error_rate',
                'critical',
                "Critical error rate: $errorCount errors in 24 hours",
                ['count' => $errorCount]
            );
        }
        
        return $alerts;
    }
    
    /**
     * Check compliance metrics
     */
    private function checkComplianceMetrics(array $metrics): array {
        $alerts = [];
        
        // Check password policy compliance
        $passwordMetrics = $metrics['password_policy'];
        $complianceRate = ($passwordMetrics['compliant_users'] / $passwordMetrics['total_users']) * 100;
        if ($complianceRate < 90) {
            $alerts[] = $this->createAlert(
                'password_policy_compliance',
                'high',
                "Low password policy compliance: " . round($complianceRate) . "%",
                ['compliance_rate' => $complianceRate]
            );
        }
        
        // Check session policy compliance
        $sessionMetrics = $metrics['session_policy'];
        $sessionComplianceRate = ($sessionMetrics['compliant_sessions'] / $sessionMetrics['total_sessions']) * 100;
        if ($sessionComplianceRate < 95) {
            $alerts[] = $this->createAlert(
                'session_policy_compliance',
                'medium',
                "Session policy compliance below threshold: " . round($sessionComplianceRate) . "%",
                ['compliance_rate' => $sessionComplianceRate]
            );
        }
        
        return $alerts;
    }
    
    /**
     * Check threat metrics
     */
    private function checkThreatMetrics(array $metrics): array {
        $alerts = [];
        
        // Check current threat level
        if ($metrics['current_threat_level'] === 'critical') {
            $alerts[] = $this->createAlert(
                'threat_level',
                'critical',
                "System is at CRITICAL threat level",
                $metrics['threat_indicators']
            );
        } elseif ($metrics['current_threat_level'] === 'high') {
            $alerts[] = $this->createAlert(
                'threat_level',
                'high',
                "System is at HIGH threat level",
                $metrics['threat_indicators']
            );
        }
        
        // Check attack patterns
        foreach ($metrics['attack_patterns'] as $pattern) {
            if ($pattern['count'] > 10) {
                $alerts[] = $this->createAlert(
                    'attack_pattern',
                    'high',
                    "High frequency of {$pattern['attack_type']} attacks detected",
                    $pattern
                );
            }
        }
        
        return $alerts;
    }
    
    /**
     * Create alert object
     */
    private function createAlert(string $type, string $severity, string $message, array $details = []): array {
        return [
            'alert_type' => $type,
            'severity' => $severity,
            'message' => $message,
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Process and store alerts
     */
    private function processAlerts(array $alerts): void {
        foreach ($alerts as $alert) {
            // Store alert
            $stmt = $this->db->prepare("
                INSERT INTO security_alerts 
                (alert_type, severity, message, details, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $alert['alert_type'],
                $alert['severity'],
                $alert['message'],
                json_encode($alert['details'])
            ]);
            
            // Send notifications for high/critical alerts
            if (in_array($alert['severity'], ['high', 'critical'])) {
                $this->sendAlertNotification($alert);
            }
        }
    }
    
    /**
     * Send alert notification
     */
    private function sendAlertNotification(array $alert): void {
        // Get admin emails
        $stmt = $this->db->query("
            SELECT email 
            FROM users 
            WHERE role = 'admin' 
            AND status = 'active'
        ");
        
        $adminEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Prepare email content
        $subject = "Security Alert: " . ucfirst($alert['severity']) . " - " . $alert['alert_type'];
        
        $body = "
            <h2>Security Alert</h2>
            <p><strong>Type:</strong> {$alert['alert_type']}</p>
            <p><strong>Severity:</strong> " . strtoupper($alert['severity']) . "</p>
            <p><strong>Message:</strong> {$alert['message']}</p>
            <p><strong>Time:</strong> {$alert['created_at']}</p>
            <h3>Details:</h3>
            <pre>" . print_r($alert['details'], true) . "</pre>
            <p>Please check the security dashboard for more information.</p>
        ";
        
        // Send emails
        foreach ($adminEmails as $email) {
            $this->mailer->sendEmail($email, $subject, $body);
        }
    }
    
    /**
     * Get active alerts
     */
    public function getActiveAlerts(): array {
        $stmt = $this->db->query("
            SELECT *
            FROM security_alerts
            WHERE status = 'new'
            ORDER BY 
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                created_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(int $alertId, int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE security_alerts
            SET 
                status = 'acknowledged',
                resolved_by = ?,
                resolved_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$userId, $alertId]);
    }
    
    /**
     * Resolve alert
     */
    public function resolveAlert(int $alertId, int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE security_alerts
            SET 
                status = 'resolved',
                resolved_by = ?,
                resolved_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$userId, $alertId]);
    }
}
