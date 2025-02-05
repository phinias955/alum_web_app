<?php
require_once 'BaseController.php';

class ActivityLogController extends BaseController {
    public function __construct() {
        parent::__construct('activity_logs');
    }

    public function getActivityLogs($page = 1, $limit = 50, $filters = []) {
        try {
            $conditions = [];
            $params = [];
            $whereClause = "";

            // Apply filters
            if (!empty($filters['user_id'])) {
                $conditions[] = "a.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['action_type'])) {
                $conditions[] = "a.action LIKE ?";
                $params[] = "%{$filters['action_type']}%";
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "a.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "a.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($conditions)) {
                $whereClause = "WHERE " . implode(" AND ", $conditions);
            }

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM activity_logs a 
                        LEFT JOIN users u ON a.user_id = u.id 
                        $whereClause";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            // Get paginated results
            $sql = "SELECT a.*, u.username, u.email 
                   FROM activity_logs a 
                   LEFT JOIN users u ON a.user_id = u.id 
                   $whereClause 
                   ORDER BY a.created_at DESC 
                   LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            
            return [
                'data' => $stmt->fetchAll(),
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Get Activity Logs Error: " . $e->getMessage());
            throw new Exception("Error fetching activity logs");
        }
    }

    public function getActionTypes() {
        try {
            $sql = "SELECT DISTINCT SUBSTRING_INDEX(action, ':', 1) as action_type 
                   FROM activity_logs 
                   ORDER BY action_type";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get Action Types Error: " . $e->getMessage());
            throw new Exception("Error fetching action types");
        }
    }

    public function getActivityStatistics() {
        try {
            $stats = [];
            
            // Total activities
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM activity_logs");
            $stats['total'] = $stmt->fetch()['total'];
            
            // Activities by type
            $stmt = $this->db->query("
                SELECT 
                    SUBSTRING_INDEX(action, ':', 1) as action_type,
                    COUNT(*) as count 
                FROM activity_logs 
                GROUP BY action_type 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $stats['by_type'] = $stmt->fetchAll();
            
            // Most active users
            $stmt = $this->db->query("
                SELECT u.username, COUNT(*) as count 
                FROM activity_logs a 
                JOIN users u ON a.user_id = u.id 
                GROUP BY a.user_id 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $stats['most_active_users'] = $stmt->fetchAll();
            
            // Activity by hour
            $stmt = $this->db->query("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as count 
                FROM activity_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY HOUR(created_at)
                ORDER BY hour
            ");
            $stats['by_hour'] = $stmt->fetchAll();

            return $stats;
        } catch (PDOException $e) {
            error_log("Get Activity Statistics Error: " . $e->getMessage());
            throw new Exception("Error fetching activity statistics");
        }
    }

    public function exportToCSV($filters = []) {
        try {
            $conditions = [];
            $params = [];
            $whereClause = "";

            // Apply filters (same as getActivityLogs)
            if (!empty($filters['user_id'])) {
                $conditions[] = "a.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['action_type'])) {
                $conditions[] = "a.action LIKE ?";
                $params[] = "%{$filters['action_type']}%";
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "a.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "a.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($conditions)) {
                $whereClause = "WHERE " . implode(" AND ", $conditions);
            }

            $sql = "SELECT 
                        u.username,
                        u.email,
                        a.action,
                        a.ip_address,
                        a.created_at
                    FROM activity_logs a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    $whereClause 
                    ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $filename = "activity_logs_" . date('Y-m-d_His') . ".csv";
            $filepath = UPLOAD_PATH . 'exports/' . $filename;
            
            if (!file_exists(UPLOAD_PATH . 'exports')) {
                mkdir(UPLOAD_PATH . 'exports', 0777, true);
            }
            
            $file = fopen($filepath, 'w');
            
            // Add headers
            fputcsv($file, [
                'Username',
                'Email',
                'Action',
                'IP Address',
                'Timestamp'
            ]);
            
            // Add data
            while ($row = $stmt->fetch()) {
                fputcsv($file, $row);
            }
            
            fclose($file);
            return 'exports/' . $filename;
        } catch (PDOException $e) {
            error_log("Export Activity Logs Error: " . $e->getMessage());
            throw new Exception("Error exporting activity logs");
        }
    }

    public function purgeOldLogs($daysToKeep = 90) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Purge Activity Logs Error: " . $e->getMessage());
            throw new Exception("Error purging old activity logs");
        }
    }
}
