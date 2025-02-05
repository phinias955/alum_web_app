<?php
require_once __DIR__ . '/../config/Database.php';

class BaseController {
    protected $db;
    protected $table;

    public function __construct($table) {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception('Database connection failed');
            }
            $this->table = $table;
        } catch (Exception $e) {
            error_log("BaseController initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAll($page = 1, $limit = 10, $where = '', $params = []) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table}";
            if ($where) {
                $countQuery .= " WHERE $where";
            }
            
            $stmt = $this->db->prepare($countQuery);
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // If no records found, return empty result set
            if ($total == 0) {
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => 0
                ];
            }
            
            // Get paginated data
            $query = "SELECT * FROM {$this->table}";
            if ($where) {
                $query .= " WHERE $where";
            }
            $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare query");
            }

            // Bind pagination parameters
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            // Bind where clause parameters if any
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindValue($key + 1, $value, $paramType);
                }
            }
            
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error in BaseController::getAll: " . $e->getMessage());
            throw $e;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in BaseController::getById: " . $e->getMessage());
            return null;
        }
    }

    public function create($data) {
        try {
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
            $stmt->execute(array_values($data));
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error in BaseController::create: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data) {
        try {
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $values = array_values($data);
            $values[] = $id;
            
            $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error in BaseController::update: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error in BaseController::delete: " . $e->getMessage());
            throw $e;
        }
    }
}
