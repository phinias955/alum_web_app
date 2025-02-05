<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class DashboardController {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception('Database connection failed');
            }
        } catch (Exception $e) {
            error_log("DashboardController initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStats() {
        try {
            // Get total alumni count
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM alumni");
            $totalAlumni = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Get active alumni count
            $stmt = $this->db->query("SELECT COUNT(*) as active FROM alumni WHERE status = 'active'");
            $activeAlumni = $stmt->fetch(PDO::FETCH_ASSOC)['active'] ?? 0;

            // Get recent activities count (last 30 days)
            $stmt = $this->db->query("SELECT COUNT(*) as activities FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $recentActivities = $stmt->fetch(PDO::FETCH_ASSOC)['activities'] ?? 0;

            // Get total events count
            $stmt = $this->db->query("SELECT COUNT(*) as events FROM events");
            $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['events'] ?? 0;

            return [
                'total_alumni' => $totalAlumni,
                'active_alumni' => $activeAlumni,
                'recent_activities' => $recentActivities,
                'total_events' => $totalEvents
            ];
        } catch (Exception $e) {
            error_log("Stats Error: " . $e->getMessage());
            // Return zero values instead of throwing to avoid breaking the dashboard
            return [
                'total_alumni' => 0,
                'active_alumni' => 0,
                'recent_activities' => 0,
                'total_events' => 0
            ];
        }
    }

    public function getRecentActivities($limit = 5) {
        try {
            $query = "SELECT al.*, u.username 
                     FROM activity_logs al
                     LEFT JOIN users u ON al.user_id = u.id
                     ORDER BY al.created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Recent Activities Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentAlumni($limit = 5) {
        try {
            $query = "SELECT * FROM alumni ORDER BY created_at DESC LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Recent Alumni Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
