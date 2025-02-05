<?php
require_once __DIR__ . '/../config/config.php';

class SecurityDataCleanup {
    private $db;
    private $retentionPeriods = [
        'security_events' => '90 days',
        'login_attempts' => '30 days',
        'password_reset_tokens' => '24 hours',
        'user_sessions' => '30 days',
        'security_reports' => '365 days',
        'security_report_logs' => '90 days',
        'user_activity_log' => '180 days',
        'rate_limit_logs' => '7 days',
        'two_factor_logs' => '90 days'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Clean up old security data
     */
    public function cleanup(): array {
        $stats = [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($this->retentionPeriods as $table => $period) {
                $deleted = $this->cleanupTable($table, $period);
                $stats[$table] = $deleted;
            }
            
            // Log cleanup
            $this->logCleanup($stats);
            
            $this->db->commit();
            return $stats;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Clean up a specific table
     */
    private function cleanupTable(string $table, string $period): int {
        $stmt = $this->db->prepare("
            DELETE FROM $table 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL $period)
        ");
        
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * Log cleanup operation
     */
    private function logCleanup(array $stats): void {
        $stmt = $this->db->prepare("
            INSERT INTO maintenance_logs 
            (operation_type, details, created_at)
            VALUES ('security_cleanup', ?, NOW())
        ");
        
        $stmt->execute([json_encode($stats)]);
    }
}

// Run cleanup
try {
    $cleanup = new SecurityDataCleanup();
    $stats = $cleanup->cleanup();
    
    // Log results
    foreach ($stats as $table => $count) {
        error_log("Cleaned up $count records from $table");
    }
    
} catch (Exception $e) {
    error_log("Security Data Cleanup Error: " . $e->getMessage());
    exit(1);
}
