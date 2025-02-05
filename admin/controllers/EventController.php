<?php
require_once 'BaseController.php';

class EventController extends BaseController {
    public function __construct() {
        parent::__construct('events');
    }

    public function createEvent($data, $image = null) {
        try {
            $this->validateEventData($data);

            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $data['image_url'] = $this->handleFileUpload($image, 'events');
            }

            $data['organizer_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');

            $eventId = $this->create($data);
            Security::logActivity($_SESSION['user_id'], "Created event: {$data['title']}");

            return $eventId;
        } catch (Exception $e) {
            error_log("Create Event Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateEvent($id, $data, $image = null) {
        try {
            $this->validateEventData($data, true);
            $existingEvent = $this->getById($id);

            if (!$existingEvent) {
                throw new Exception("Event not found");
            }

            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $data['image_url'] = $this->handleFileUpload($image, 'events');
                // Delete old image if exists
                if ($existingEvent['image_url']) {
                    $this->deleteImage($existingEvent['image_url']);
                }
            }

            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->update($id, $data);
            Security::logActivity($_SESSION['user_id'], "Updated event: {$data['title']}");

            return true;
        } catch (Exception $e) {
            error_log("Update Event Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteEvent($id) {
        try {
            $event = $this->getById($id);
            if (!$event) {
                throw new Exception("Event not found");
            }

            // Delete associated image if exists
            if ($event['image_url']) {
                $this->deleteImage($event['image_url']);
            }

            $this->delete($id);
            Security::logActivity($_SESSION['user_id'], "Deleted event: {$event['title']}");

            return true;
        } catch (Exception $e) {
            error_log("Delete Event Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function searchEvents($term, $page = 1, $limit = 10) {
        return $this->search($term, ['title', 'description', 'location', 'type'], $page, $limit);
    }

    public function getEventsByStatus($status, $page = 1, $limit = 10) {
        return $this->getAll($page, $limit, 'status = ?', [$status]);
    }

    public function getUpcomingEvents($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM events 
                WHERE event_date >= CURRENT_DATE 
                AND status = 'upcoming'
                ORDER BY event_date ASC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Upcoming Events Error: " . $e->getMessage());
            throw new Exception("Error fetching upcoming events");
        }
    }

    public function updateEventStatus($id, $status) {
        try {
            $event = $this->getById($id);
            if (!$event) {
                throw new Exception("Event not found");
            }

            $this->update($id, ['status' => $status]);
            Security::logActivity(
                $_SESSION['user_id'], 
                "Updated event status: {$event['title']} to {$status}"
            );

            return true;
        } catch (Exception $e) {
            error_log("Update Event Status Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateEventData($data, $isUpdate = false) {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = "Title is required";
        } elseif (strlen($data['title']) > 255) {
            $errors[] = "Title must be less than 255 characters";
        }

        if (empty($data['description'])) {
            $errors[] = "Description is required";
        }

        if (empty($data['event_date'])) {
            $errors[] = "Event date is required";
        } elseif (!$isUpdate && strtotime($data['event_date']) < strtotime('today')) {
            $errors[] = "Event date cannot be in the past";
        }

        if (empty($data['location'])) {
            $errors[] = "Location is required";
        }

        if (empty($data['type'])) {
            $errors[] = "Event type is required";
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

    // Get events with organizer details
    public function getEventsWithOrganizer($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT e.*, u.username as organizer_name 
                   FROM events e 
                   LEFT JOIN users u ON e.organizer_id = u.id 
                   ORDER BY e.event_date DESC 
                   LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Events With Organizer Error: " . $e->getMessage());
            throw new Exception("Error fetching events with organizer details");
        }
    }
}
