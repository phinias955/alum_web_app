<?php
require_once 'BaseController.php';

class BackupController extends BaseController {
    private $backupPath;
    private $maxBackups;
    private $tables;

    public function __construct() {
        parent::__construct();
        $this->backupPath = UPLOAD_PATH . 'backups/';
        $this->maxBackups = 10; // Keep last 10 backups
        $this->tables = $this->getTables();
        
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0777, true);
        }
    }

    private function getTables() {
        try {
            $tables = [];
            $result = $this->db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            return $tables;
        } catch (PDOException $e) {
            error_log("Error getting tables: " . $e->getMessage());
            throw new Exception("Error getting database tables");
        }
    }

    public function createBackup($type = 'full') {
        try {
            $timestamp = date('Y-m-d_His');
            $filename = "backup_{$type}_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;

            $handle = fopen($filepath, 'w');

            // Add header information
            fwrite($handle, "-- Alumni Portal Backup\n");
            fwrite($handle, "-- Type: " . $type . "\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Server: " . $_SERVER['SERVER_NAME'] . "\n\n");

            // Add SET statements for proper restoration
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            foreach ($this->tables as $table) {
                // Skip certain tables for partial backup
                if ($type === 'partial' && in_array($table, ['activity_logs', 'login_attempts', 'rate_limits'])) {
                    continue;
                }

                // Get create table statement
                $stmt = $this->db->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($handle, $row[1] . ";\n\n");

                // Get table data
                $rows = $this->db->query("SELECT * FROM `$table`");
                while ($row = $rows->fetch(PDO::FETCH_NUM)) {
                    $values = array_map(function($value) {
                        if ($value === null) return 'NULL';
                        return $this->db->quote($value);
                    }, $row);
                    
                    fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
                }
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            // Compress the backup
            $zip = new ZipArchive();
            $zipname = $filepath . '.zip';
            if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($filepath, $filename);
                $zip->close();
                unlink($filepath); // Remove the uncompressed file
            }

            // Clean up old backups
            $this->cleanOldBackups();

            // Log the backup
            $this->logBackup($filename . '.zip', $type);

            return [
                'status' => 'success',
                'file' => 'backups/' . $filename . '.zip',
                'size' => filesize($zipname)
            ];
        } catch (Exception $e) {
            error_log("Backup error: " . $e->getMessage());
            throw new Exception("Error creating backup");
        }
    }

    public function restoreBackup($filename) {
        try {
            $filepath = $this->backupPath . $filename;
            
            // Extract zip file
            $zip = new ZipArchive();
            if ($zip->open($filepath) === TRUE) {
                $sqlFile = $zip->getNameIndex(0);
                $zip->extractTo($this->backupPath);
                $zip->close();
                
                $sqlContent = file_get_contents($this->backupPath . $sqlFile);
                unlink($this->backupPath . $sqlFile);

                // Split SQL statements
                $statements = array_filter(
                    array_map('trim', 
                        explode(';', $sqlContent)
                    )
                );

                // Begin transaction
                $this->db->beginTransaction();

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $this->db->exec($statement);
                    }
                }

                $this->db->commit();

                // Log the restore
                $this->logBackup($filename, 'restore');

                return true;
            }
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Restore error: " . $e->getMessage());
            throw new Exception("Error restoring backup");
        }
    }

    private function cleanOldBackups() {
        $files = glob($this->backupPath . "backup_*.sql.zip");
        if (count($files) > $this->maxBackups) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $oldFiles = array_slice($files, $this->maxBackups);
            foreach ($oldFiles as $file) {
                unlink($file);
            }
        }
    }

    private function logBackup($filename, $type) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO backup_logs (filename, type, size, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $filename,
                $type,
                filesize($this->backupPath . $filename),
                $_SESSION['user_id'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error logging backup: " . $e->getMessage());
        }
    }

    public function getBackups($page = 1, $limit = 10) {
        try {
            // Get total count
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM backup_logs");
            $total = $stmt->fetch()['total'];

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Get backup logs with user information
            $stmt = $this->db->prepare("
                SELECT b.*, u.username 
                FROM backup_logs b 
                LEFT JOIN users u ON b.created_by = u.id 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);

            return [
                'data' => $stmt->fetchAll(),
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            error_log("Error fetching backups: " . $e->getMessage());
            throw new Exception("Error fetching backup history");
        }
    }

    public function scheduleBackup($type, $frequency, $time) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO backup_schedule (type, frequency, scheduled_time, status)
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$type, $frequency, $time]);
            return true;
        } catch (PDOException $e) {
            error_log("Error scheduling backup: " . $e->getMessage());
            throw new Exception("Error scheduling backup");
        }
    }

    public function runScheduledBackups() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM backup_schedule 
                WHERE status = 'active' 
                AND last_run < DATE_SUB(NOW(), INTERVAL frequency HOUR)
            ");

            while ($schedule = $stmt->fetch()) {
                $this->createBackup($schedule['type']);
                
                // Update last run time
                $updateStmt = $this->db->prepare("
                    UPDATE backup_schedule 
                    SET last_run = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$schedule['id']]);
            }
        } catch (Exception $e) {
            error_log("Error running scheduled backups: " . $e->getMessage());
        }
    }
}
