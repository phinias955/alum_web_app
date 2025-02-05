<?php
require_once 'BaseController.php';

class NewsController extends BaseController {
    public function __construct() {
        parent::__construct('news');
    }

    public function createNews($data, $image = null) {
        try {
            $this->validateNewsData($data);

            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $data['image_url'] = $this->handleFileUpload($image, 'news');
            }

            $data['author_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');

            $newsId = $this->create($data);
            Security::logActivity($_SESSION['user_id'], "Created news article: {$data['title']}");

            return $newsId;
        } catch (Exception $e) {
            error_log("Create News Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateNews($id, $data, $image = null) {
        try {
            $this->validateNewsData($data, true);
            $existingNews = $this->getById($id);

            if (!$existingNews) {
                throw new Exception("News article not found");
            }

            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $data['image_url'] = $this->handleFileUpload($image, 'news');
                // Delete old image if exists
                if ($existingNews['image_url']) {
                    $this->deleteImage($existingNews['image_url']);
                }
            }

            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
            Security::logActivity($_SESSION['user_id'], "Updated news article: {$data['title']}");

            return true;
        } catch (Exception $e) {
            error_log("Update News Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteNews($id) {
        try {
            $news = $this->getById($id);
            if (!$news) {
                throw new Exception("News article not found");
            }

            // Delete associated image if exists
            if ($news['image_url']) {
                $this->deleteImage($news['image_url']);
            }

            $this->delete($id);
            Security::logActivity($_SESSION['user_id'], "Deleted news article: {$news['title']}");

            return true;
        } catch (Exception $e) {
            error_log("Delete News Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function searchNews($term, $page = 1, $limit = 10) {
        return $this->search($term, ['title', 'content'], $page, $limit);
    }

    public function getNewsByStatus($status, $page = 1, $limit = 10) {
        return $this->getAll($page, $limit, 'status = ?', [$status]);
    }

    public function toggleStatus($id) {
        try {
            $news = $this->getById($id);
            if (!$news) {
                throw new Exception("News article not found");
            }

            $newStatus = $news['status'] === 'published' ? 'draft' : 'published';
            $this->update($id, ['status' => $newStatus]);
            
            Security::logActivity(
                $_SESSION['user_id'], 
                "Changed news article status: {$news['title']} to {$newStatus}"
            );

            return $newStatus;
        } catch (Exception $e) {
            error_log("Toggle Status Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateNewsData($data, $isUpdate = false) {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = "Title is required";
        } elseif (strlen($data['title']) > 255) {
            $errors[] = "Title must be less than 255 characters";
        }

        if (empty($data['content'])) {
            $errors[] = "Content is required";
        }

        if (!$isUpdate && empty($data['status'])) {
            $errors[] = "Status is required";
        }

        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }
    }

    private function deleteImage($imagePath) {
        $fullPath = UPLOAD_PATH . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    // Get news with author details
    public function getNewsWithAuthor($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT n.*, u.username as author_name 
                   FROM news n 
                   LEFT JOIN users u ON n.author_id = u.id 
                   ORDER BY n.created_at DESC 
                   LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get News With Author Error: " . $e->getMessage());
            throw new Exception("Error fetching news with author details");
        }
    }
}
