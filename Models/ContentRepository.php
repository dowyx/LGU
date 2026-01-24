<?php
/**
 * Content Repository Model
 * Handles all database operations for the content repository module
 */

require_once '../config/database.php';

class ContentRepository {
    private $conn;
    
    public function __construct() {
        global $pdo; // Use the global PDO instance from database.php
        $this->conn = $pdo;
    }
    
    /**
     * Get all content items with optional filtering
     */
    public function getContentItems($filters = []) {
        $query = "SELECT ci.*, cc.name as category_name, cc.icon_class 
                  FROM content_items ci 
                  LEFT JOIN content_categories cc ON ci.category = cc.name";
        
        $conditions = [];
        $params = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $conditions[] = "ci.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['category']) && !empty($filters['category'])) {
            $conditions[] = "ci.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $conditions[] = "(ci.name LIKE :search OR ci.description LIKE :search OR ci.tags LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY ci.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a single content item by ID
     */
    public function getContentItemById($id) {
        $query = "SELECT ci.*, cc.name as category_name, cc.icon_class 
                  FROM content_items ci 
                  LEFT JOIN content_categories cc ON ci.category = cc.name
                  WHERE ci.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new content item
     */
    public function createContentItem($data) {
        $query = "INSERT INTO content_items 
                  (name, file_path, category, size, file_type, description, version, tags, uploaded_by) 
                  VALUES (:name, :file_path, :category, :size, :file_type, :description, :version, :tags, :uploaded_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':file_path', $data['file_path']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':size', $data['size']);
        $stmt->bindParam(':file_type', $data['file_type']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':version', $data['version']);
        $stmt->bindParam(':tags', $data['tags']);
        $stmt->bindParam(':uploaded_by', $data['uploaded_by']);
        
        return $stmt->execute();
    }
    
    /**
     * Update a content item
     */
    public function updateContentItem($id, $data) {
        $query = "UPDATE content_items 
                  SET name = :name, category = :category, description = :description, 
                      status = :status, version = :version, tags = :tags, expiry_date = :expiry_date
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':version', $data['version']);
        $stmt->bindParam(':tags', $data['tags']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a content item
     */
    public function deleteContentItem($id) {
        $query = "DELETE FROM content_items WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Update content status (for approval workflow)
     */
    public function updateContentStatus($id, $status, $approverId = null, $comments = null) {
        // Get current status for workflow log
        $currentItem = $this->getContentItemById($id);
        $statusBefore = $currentItem['status'];
        
        $query = "UPDATE content_items SET status = :status";
        
        if ($status == 'approved') {
            $query .= ", approved_date = NOW()";
        } elseif ($status == 'rejected') {
            $query .= ", rejected_date = NOW()";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        
        $result = $stmt->execute();
        
        // Log the approval workflow action
        if ($result && $approverId) {
            $workflowQuery = "INSERT INTO approval_workflow 
                              (content_item_id, approver_id, status_before, status_after, comments) 
                              VALUES (:content_item_id, :approver_id, :status_before, :status_after, :comments)";
            
            $workflowStmt = $this->conn->prepare($workflowQuery);
            $workflowStmt->bindParam(':content_item_id', $id);
            $workflowStmt->bindParam(':approver_id', $approverId);
            $workflowStmt->bindParam(':status_before', $statusBefore);
            $workflowStmt->bindParam(':status_after', $status);
            $workflowStmt->bindParam(':comments', $comments);
            $workflowStmt->execute();
        }
        
        return $result;
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($id) {
        $query = "UPDATE content_items SET download_count = download_count + 1 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Log download activity
     */
    public function logDownload($contentItemId, $userId = null, $ipAddress = null) {
        $query = "INSERT INTO download_logs (content_item_id, user_id, ip_address) 
                  VALUES (:content_item_id, :user_id, :ip_address)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':content_item_id', $contentItemId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':ip_address', $ipAddress);
        
        return $stmt->execute();
    }
    
    /**
     * Get content statistics
     */
    public function getContentStats() {
        $stats = [];
        
        // Total content count
        $query = "SELECT COUNT(*) as total FROM content_items";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Count by status
        $query = "SELECT status, COUNT(*) as count FROM content_items GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['by_status'] = [
            'draft' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];
        
        foreach ($statusCounts as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Count by category
        $query = "SELECT category, COUNT(*) as count FROM content_items GROUP BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recently added items
        $query = "SELECT * FROM content_items ORDER BY created_at DESC LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Expiring content (within 30 days)
        $query = "SELECT * FROM content_items 
                  WHERE expiry_date IS NOT NULL 
                  AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) 
                  AND expiry_date >= NOW()
                  ORDER BY expiry_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['expiring_soon'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Get all content categories
     */
    public function getCategories() {
        $query = "SELECT * FROM content_categories ORDER BY name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all content tags
     */
    public function getTags() {
        $query = "SELECT * FROM content_tags ORDER BY name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new tag
     */
    public function addTag($tagName) {
        $query = "INSERT IGNORE INTO content_tags (name) VALUES (:name)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $tagName);
        
        return $stmt->execute();
    }
    
    /**
     * Search content by various criteria
     */
    public function searchContent($searchTerm) {
        $query = "SELECT ci.*, cc.name as category_name, cc.icon_class 
                  FROM content_items ci 
                  LEFT JOIN content_categories cc ON ci.category = cc.name
                  WHERE ci.name LIKE :search 
                  OR ci.description LIKE :search 
                  OR ci.tags LIKE :search
                  ORDER BY ci.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search', '%' . $searchTerm . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get content items related to a specific event (cross-module integration)
     */
    public function getContentForEvent($eventId) {
        $query = "SELECT ci.*, ec.relevance_score, e.title as event_title
                  FROM content_items ci
                  JOIN event_content ec ON ci.id = ec.content_item_id
                  JOIN events e ON ec.event_id = e.id
                  WHERE ec.event_id = :event_id
                  AND ci.status = 'approved'
                  ORDER BY ec.relevance_score DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Link content to an event
     */
    public function linkContentToEvent($contentId, $eventId, $relevanceScore = 5) {
        $query = "INSERT IGNORE INTO event_content 
                  (content_item_id, event_id, relevance_score, created_at)
                  VALUES (:content_id, :event_id, :relevance_score, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':content_id', $contentId);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':relevance_score', $relevanceScore);
        
        return $stmt->execute();
    }
    
    /**
     * Get events associated with specific content
     */
    public function getEventsForContent($contentId) {
        $query = "SELECT e.*, ec.relevance_score, ci.name as content_name
                  FROM events e
                  JOIN event_content ec ON e.id = ec.event_id
                  JOIN content_items ci ON ec.content_item_id = ci.id
                  WHERE ec.content_item_id = :content_id
                  ORDER BY ec.relevance_score DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':content_id', $contentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>