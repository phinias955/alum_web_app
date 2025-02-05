<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/SecurityAudit.php';

class SecurityReportGenerator {
    private $audit;
    private $mailer;
    private $db;
    
    public function __construct() {
        $this->audit = new SecurityAudit();
        $this->mailer = new NotificationController();
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate and send daily security report
     */
    public function generateDailyReport(): void {
        try {
            // Generate report
            $report = $this->audit->generateSecurityReport();
            
            // Create HTML report
            $html = $this->createReportHTML($report);
            
            // Get admin emails
            $adminEmails = $this->getAdminEmails();
            
            // Send report
            foreach ($adminEmails as $email) {
                $this->mailer->sendEmail(
                    $email,
                    'Daily Security Report - ' . date('Y-m-d'),
                    $html
                );
            }
            
            // Log success
            $this->logReportGeneration('daily', true);
            
        } catch (Exception $e) {
            // Log error
            $this->logReportGeneration('daily', false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create HTML report
     */
    private function createReportHTML(array $report): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .section { margin-bottom: 30px; }
                .alert { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; }
                .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Security Report - <?php echo date('Y-m-d'); ?></h1>
                
                <!-- Summary -->
                <div class="section">
                    <h2>Summary</h2>
                    <p>Security events in last 24h: <?php echo $report['summary']['events_24h']; ?></p>
                    <p>Failed logins in last 24h: <?php echo $report['summary']['failed_logins_24h']; ?></p>
                    <p>Currently locked accounts: <?php echo $report['summary']['locked_accounts']; ?></p>
                    <p>Users with 2FA enabled: <?php echo $report['summary']['users_with_2fa']; ?></p>
                </div>
                
                <!-- Suspicious Activity -->
                <?php if (!empty($report['suspicious_ips'])): ?>
                <div class="section">
                    <h2>Suspicious IP Addresses</h2>
                    <table>
                        <tr>
                            <th>IP Address</th>
                            <th>Unique Users</th>
                            <th>Total Attempts</th>
                            <th>First Attempt</th>
                            <th>Last Attempt</th>
                        </tr>
                        <?php foreach ($report['suspicious_ips'] as $ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                            <td><?php echo $ip['unique_users']; ?></td>
                            <td><?php echo $ip['total_attempts']; ?></td>
                            <td><?php echo $ip['first_attempt']; ?></td>
                            <td><?php echo $ip['last_attempt']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Locked Accounts -->
                <?php if (!empty($report['locked_accounts'])): ?>
                <div class="section">
                    <h2>Locked Accounts</h2>
                    <table>
                        <tr>
                            <th>Username</th>
                            <th>Failed Attempts</th>
                            <th>Locked Until</th>
                            <th>Last Login</th>
                        </tr>
                        <?php foreach ($report['locked_accounts'] as $account): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account['username']); ?></td>
                            <td><?php echo $account['failed_login_attempts']; ?></td>
                            <td><?php echo $account['lockout_until']; ?></td>
                            <td><?php echo $account['last_login_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- System Health -->
                <div class="section">
                    <h2>System Health</h2>
                    <p>Disk Usage: <?php echo round($report['system_health']['disk_space']['usage_percent'], 2); ?>%</p>
                    <p>Active Sessions: <?php echo $report['system_health']['session_count']; ?></p>
                    <p>Latest Backup: <?php 
                        $backup = reset($report['system_health']['backup_status']);
                        echo $backup ? date('Y-m-d H:i', strtotime($backup['created_at'])) : 'No recent backup';
                    ?></p>
                    <p>Recent Errors: <?php echo count($report['system_health']['error_logs']); ?></p>
                </div>
                
                <!-- Recent Security Events -->
                <?php if (!empty($report['security_events'])): ?>
                <div class="section">
                    <h2>Recent Security Events</h2>
                    <table>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>User</th>
                            <th>IP Address</th>
                        </tr>
                        <?php foreach (array_slice($report['security_events'], 0, 10) as $event): ?>
                        <tr>
                            <td><?php echo $event['created_at']; ?></td>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($event['username'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($event['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get admin email addresses
     */
    private function getAdminEmails(): array {
        $stmt = $this->db->query("
            SELECT email 
            FROM users 
            WHERE role = 'admin' 
            AND status = 'active'
        ");
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Log report generation
     */
    private function logReportGeneration(string $type, bool $success, string $error = null): void {
        $stmt = $this->db->prepare("
            INSERT INTO security_report_logs 
            (report_type, success, error_message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$type, $success, $error]);
    }
}

// Run report generation
try {
    $generator = new SecurityReportGenerator();
    $generator->generateDailyReport();
} catch (Exception $e) {
    error_log("Security Report Generation Error: " . $e->getMessage());
    exit(1);
}
