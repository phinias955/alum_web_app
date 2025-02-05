<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/SecurityMetrics.php';
require_once __DIR__ . '/../includes/SecurityAlerts.php';

class MetricsCollector {
    private $db;
    private $metrics;
    private $alerts;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->metrics = new SecurityMetrics();
        $this->alerts = new SecurityAlerts();
    }
    
    /**
     * Collect and store all metrics
     */
    public function collect(): void {
        try {
            $this->db->beginTransaction();
            
            // Collect metrics
            $allMetrics = $this->metrics->getMetrics();
            
            // Store each metric type
            foreach ($allMetrics as $type => $metrics) {
                $this->storeMetrics($type, $metrics);
            }
            
            // Check for security issues
            $this->alerts->checkSecurityConditions();
            
            // Clean up old metrics
            $this->cleanupOldMetrics();
            
            $this->db->commit();
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logError('metrics_collection', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Store metrics in database
     */
    private function storeMetrics(string $type, array $metrics): void {
        $stmt = $this->db->prepare("
            INSERT INTO security_metrics 
            (metric_type, metric_name, metric_value, collected_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        foreach ($metrics as $name => $value) {
            $stmt->execute([
                $type,
                $name,
                json_encode($value)
            ]);
        }
    }
    
    /**
     * Clean up old metrics
     */
    private function cleanupOldMetrics(): void {
        // Keep last 30 days of metrics
        $stmt = $this->db->prepare("
            DELETE FROM security_metrics 
            WHERE collected_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $stmt->execute();
    }
    
    /**
     * Log error
     */
    private function logError(string $operation, string $message): void {
        $stmt = $this->db->prepare("
            INSERT INTO error_logs 
            (error_type, message, created_at)
            VALUES (?, ?, NOW())
        ");
        
        $stmt->execute([$operation, $message]);
    }
}

// Run metrics collection
try {
    $collector = new MetricsCollector();
    $collector->collect();
    
} catch (Exception $e) {
    error_log("Metrics Collection Error: " . $e->getMessage());
    exit(1);
}
