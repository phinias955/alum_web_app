<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controllers/BaseController.php';

class AlumniController extends BaseController {
    public function __construct() {
        parent::__construct('alumni');
    }

    public function createAlumni($data) {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'course', 'graduation_year'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Set default values
            $data['status'] = 'active';
            $data['created_at'] = date('Y-m-d H:i:s');

            return $this->create($data);
        } catch (Exception $e) {
            error_log("Error in AlumniController::createAlumni: " . $e->getMessage());
            throw new Exception("Failed to create alumni record: " . $e->getMessage());
        }
    }

    public function updateAlumni($id, $data) {
        try {
            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Set update timestamp
            $data['updated_at'] = date('Y-m-d H:i:s');

            return $this->update($id, $data);
        } catch (Exception $e) {
            error_log("Error in AlumniController::updateAlumni: " . $e->getMessage());
            throw new Exception("Failed to update alumni record: " . $e->getMessage());
        }
    }

    public function deleteAlumni($id) {
        try {
            // Get alumni record first to get profile image path
            $alumni = $this->getById($id);
            
            // Delete the record
            $result = $this->delete($id);
            
            // Delete profile image if exists
            if ($result && $alumni && !empty($alumni['profile_image'])) {
                $this->deleteProfileImage($alumni['profile_image']);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in AlumniController::deleteAlumni: " . $e->getMessage());
            throw new Exception("Failed to delete alumni record: " . $e->getMessage());
        }
    }

    public function searchAlumni($term, $page = 1, $limit = 10) {
        try {
            $searchTerm = "%$term%";
            $where = "first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR course LIKE ?";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            
            return $this->getAll($page, $limit, $where, $params);
        } catch (Exception $e) {
            error_log("Error in AlumniController::searchAlumni: " . $e->getMessage());
            throw new Exception("Failed to search alumni: " . $e->getMessage());
        }
    }

    public function getAlumniByGraduationYear($year, $page = 1, $limit = 10) {
        try {
            $where = "graduation_year = ?";
            $params = [$year];
            
            return $this->getAll($page, $limit, $where, $params);
        } catch (Exception $e) {
            error_log("Error in AlumniController::getAlumniByGraduationYear: " . $e->getMessage());
            throw new Exception("Failed to get alumni by graduation year: " . $e->getMessage());
        }
    }

    public function getActiveAlumni($page = 1, $limit = 10) {
        try {
            $where = "status = 'active'";
            return $this->getAll($page, $limit, $where);
        } catch (Exception $e) {
            error_log("Error in AlumniController::getActiveAlumni: " . $e->getMessage());
            throw new Exception("Failed to get active alumni: " . $e->getMessage());
        }
    }

    public function getInactiveAlumni($page = 1, $limit = 10) {
        try {
            $where = "status = 'inactive'";
            return $this->getAll($page, $limit, $where);
        } catch (Exception $e) {
            error_log("Error in AlumniController::getInactiveAlumni: " . $e->getMessage());
            throw new Exception("Failed to get inactive alumni: " . $e->getMessage());
        }
    }

    public function exportToCSV() {
        try {
            $result = $this->db->query("SELECT * FROM alumni ORDER BY id DESC");
            
            if (!$result) {
                throw new Exception("Failed to fetch alumni data");
            }

            $output = fopen('php://temp', 'w');
            
            // Add headers
            $headers = [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone',
                'Course', 'Graduation Year', 'Current Job', 'Company',
                'LinkedIn URL', 'Status', 'Created At', 'Updated At'
            ];
            fputcsv($output, $headers);
            
            // Add data
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
        } catch (Exception $e) {
            error_log("Error in AlumniController::exportToCSV: " . $e->getMessage());
            throw new Exception("Failed to export alumni data: " . $e->getMessage());
        }
    }

    private function deleteProfileImage($path) {
        $fullPath = ADMIN_PATH . '/' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
