<?php
require_once __DIR__ . '/../config/config.php';

class ThreatIntelligence {
    private $db;
    private $mailer;
    
    // Threat confidence thresholds
    private const CONFIDENCE_THRESHOLDS = [
        'low' => 0.3,
        'medium' => 0.6,
        'high' => 0.8
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mailer = new NotificationController();
    }
    
    /**
     * Generate threat intelligence report
     */
    public function generateReport(): array {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => $this->getThreatSummary(),
            'attack_patterns' => $this->getAttackPatterns(),
            'suspicious_ips' => $this->getSuspiciousIPs(),
            'threat_indicators' => $this->getThreatIndicators(),
            'recommendations' => []
        ];
        
        // Generate recommendations based on findings
        $report['recommendations'] = $this->generateRecommendations($report);
        
        // Store report
        $this->storeReport($report);
        
        // Send notifications if high threats detected
        if ($this->hasHighThreats($report)) {
            $this->sendThreatNotification($report);
        }
        
        return $report;
    }
    
    /**
     * Get threat summary
     */
    private function getThreatSummary(): array {
        // Get various threat metrics
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM security_events WHERE severity = 'high' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as high_severity_events,
                (SELECT COUNT(DISTINCT ip_address) FROM blocked_ips WHERE expires_at > NOW()) as blocked_ips,
                (SELECT COUNT(*) FROM security_alerts WHERE severity IN ('high', 'critical') AND status = 'new') as active_alerts,
                (SELECT COUNT(DISTINCT user_id) FROM login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as affected_users
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get attack patterns
     */
    private function getAttackPatterns(): array {
        // Analyze security events for attack patterns
        $stmt = $this->db->query("
            SELECT 
                event_type,
                COUNT(*) as occurrence_count,
                COUNT(DISTINCT ip_address) as unique_sources,
                MIN(created_at) as first_seen,
                MAX(created_at) as last_seen
            FROM security_events
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND event_type LIKE 'attack_%'
            GROUP BY event_type
            HAVING occurrence_count >= 5
            ORDER BY occurrence_count DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get suspicious IPs
     */
    private function getSuspiciousIPs(): array {
        // Analyze IP addresses showing suspicious behavior
        $stmt = $this->db->query("
            SELECT 
                ip_address,
                COUNT(*) as event_count,
                GROUP_CONCAT(DISTINCT event_type) as event_types,
                MIN(created_at) as first_seen,
                MAX(created_at) as last_seen
            FROM security_events
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip_address
            HAVING event_count >= 10
            ORDER BY event_count DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get threat indicators
     */
    private function getThreatIndicators(): array {
        $indicators = [];
        
        // Get failed login patterns
        $failedLogins = $this->getFailedLoginPatterns();
        if (!empty($failedLogins)) {
            $indicators['failed_logins'] = $failedLogins;
        }
        
        // Get suspicious user activities
        $suspiciousActivities = $this->getSuspiciousActivities();
        if (!empty($suspiciousActivities)) {
            $indicators['suspicious_activities'] = $suspiciousActivities;
        }
        
        // Get potential brute force attempts
        $bruteForceAttempts = $this->getBruteForceAttempts();
        if (!empty($bruteForceAttempts)) {
            $indicators['brute_force_attempts'] = $bruteForceAttempts;
        }
        
        return $indicators;
    }
    
    /**
     * Get failed login patterns
     */
    private function getFailedLoginPatterns(): array {
        $stmt = $this->db->query("
            SELECT 
                username,
                ip_address,
                COUNT(*) as attempt_count,
                MIN(attempted_at) as first_attempt,
                MAX(attempted_at) as last_attempt
            FROM login_attempts
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY username, ip_address
            HAVING attempt_count >= 5
            ORDER BY attempt_count DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get suspicious activities
     */
    private function getSuspiciousActivities(): array {
        $stmt = $this->db->query("
            SELECT 
                user_id,
                action,
                COUNT(*) as action_count,
                GROUP_CONCAT(DISTINCT ip_address) as ip_addresses
            FROM user_activity_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY user_id, action
            HAVING action_count >= 20
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get potential brute force attempts
     */
    private function getBruteForceAttempts(): array {
        $stmt = $this->db->query("
            SELECT 
                ip_address,
                COUNT(DISTINCT username) as targeted_users,
                COUNT(*) as total_attempts,
                MAX(attempted_at) - MIN(attempted_at) as time_span_seconds
            FROM login_attempts
            WHERE success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY ip_address
            HAVING total_attempts >= 20
            AND time_span_seconds <= 3600
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate security recommendations
     */
    private function generateRecommendations(array $report): array {
        $recommendations = [];
        
        // Check for high number of failed logins
        if ($report['summary']['high_severity_events'] > 10) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'authentication',
                'recommendation' => 'Consider implementing additional authentication protection measures',
                'actions' => [
                    'Increase login attempt cooldown period',
                    'Implement CAPTCHA for failed attempts',
                    'Enable geographic-based access restrictions'
                ]
            ];
        }
        
        // Check for suspicious IP patterns
        if (count($report['suspicious_ips']) > 5) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'network',
                'recommendation' => 'Implement IP-based security measures',
                'actions' => [
                    'Review and update IP blacklist',
                    'Configure rate limiting by IP',
                    'Enable WAF rules for suspicious IPs'
                ]
            ];
        }
        
        // Check for attack patterns
        if (!empty($report['attack_patterns'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'security',
                'recommendation' => 'Review and update security rules',
                'actions' => [
                    'Update firewall rules',
                    'Review application security settings',
                    'Enable additional logging for detected patterns'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Store threat intelligence report
     */
    private function storeReport(array $report): void {
        $stmt = $this->db->prepare("
            INSERT INTO threat_intelligence 
            (threat_type, indicator, confidence_score, source, details, expires_at, created_at)
            VALUES ('report', 'system_analysis', ?, 'internal', ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())
        ");
        
        $confidence = $this->calculateThreatConfidence($report);
        $stmt->execute([$confidence, json_encode($report)]);
    }
    
    /**
     * Calculate threat confidence score
     */
    private function calculateThreatConfidence(array $report): float {
        $score = 0;
        $maxScore = 0;
        
        // Weight different factors
        if ($report['summary']['high_severity_events'] > 0) {
            $score += min($report['summary']['high_severity_events'] * 0.1, 0.3);
            $maxScore += 0.3;
        }
        
        if (count($report['suspicious_ips']) > 0) {
            $score += min(count($report['suspicious_ips']) * 0.05, 0.2);
            $maxScore += 0.2;
        }
        
        if (!empty($report['attack_patterns'])) {
            $score += min(count($report['attack_patterns']) * 0.1, 0.3);
            $maxScore += 0.3;
        }
        
        if (!empty($report['threat_indicators'])) {
            $score += min(0.05 * (
                count($report['threat_indicators']['failed_logins'] ?? []) +
                count($report['threat_indicators']['suspicious_activities'] ?? []) +
                count($report['threat_indicators']['brute_force_attempts'] ?? [])
            ), 0.2);
            $maxScore += 0.2;
        }
        
        return $maxScore > 0 ? $score / $maxScore : 0;
    }
    
    /**
     * Check if report contains high threats
     */
    private function hasHighThreats(array $report): bool {
        return $report['summary']['high_severity_events'] > 0 ||
               count($report['suspicious_ips']) > 5 ||
               !empty($report['threat_indicators']['brute_force_attempts']);
    }
    
    /**
     * Send threat notification
     */
    private function sendThreatNotification(array $report): void {
        // Get admin emails
        $stmt = $this->db->query("
            SELECT email 
            FROM users 
            WHERE role = 'admin' 
            AND status = 'active'
        ");
        
        $adminEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Prepare email content
        $subject = "Threat Intelligence Alert - " . date('Y-m-d H:i:s');
        
        $body = $this->generateThreatEmailBody($report);
        
        // Send notifications
        foreach ($adminEmails as $email) {
            $this->mailer->sendEmail($email, $subject, $body);
        }
    }
    
    /**
     * Generate threat email body
     */
    private function generateThreatEmailBody(array $report): string {
        ob_start();
        ?>
        <h2>Threat Intelligence Report</h2>
        <p><strong>Generated at:</strong> <?php echo $report['timestamp']; ?></p>
        
        <h3>Summary</h3>
        <ul>
            <li>High Severity Events: <?php echo $report['summary']['high_severity_events']; ?></li>
            <li>Blocked IPs: <?php echo $report['summary']['blocked_ips']; ?></li>
            <li>Active Alerts: <?php echo $report['summary']['active_alerts']; ?></li>
            <li>Affected Users: <?php echo $report['summary']['affected_users']; ?></li>
        </ul>
        
        <?php if (!empty($report['suspicious_ips'])): ?>
        <h3>Suspicious IP Addresses</h3>
        <ul>
            <?php foreach (array_slice($report['suspicious_ips'], 0, 5) as $ip): ?>
            <li><?php echo $ip['ip_address']; ?> (<?php echo $ip['event_count']; ?> events)</li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <?php if (!empty($report['recommendations'])): ?>
        <h3>Recommendations</h3>
        <ul>
            <?php foreach ($report['recommendations'] as $rec): ?>
            <li>
                <strong><?php echo ucfirst($rec['priority']); ?>:</strong>
                <?php echo $rec['recommendation']; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <p>Please check the security dashboard for more details.</p>
        <?php
        return ob_get_clean();
    }
}

// Run threat intelligence report generation
try {
    $intelligence = new ThreatIntelligence();
    $report = $intelligence->generateReport();
    
    // Log success
    error_log("Threat Intelligence Report generated successfully");
    
} catch (Exception $e) {
    error_log("Threat Intelligence Error: " . $e->getMessage());
    exit(1);
}
