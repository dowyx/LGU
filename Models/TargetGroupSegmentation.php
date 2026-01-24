<?php
/**
 * Target Group Segmentation Model
 * Handles all database operations for the target group segmentation module
 */

require_once '../config/database.php';

class TargetGroupSegmentation {
    private $conn;
    
    public function __construct() {
        global $pdo; // Use the global PDO instance from database.php
        $this->conn = $pdo;
    }
    
    /**
     * Get all segments with optional filtering
     */
    public function getSegments($filters = []) {
        $query = "SELECT s.*, 
                         (SELECT COUNT(*) FROM segment_members sm WHERE sm.segment_id = s.id) as member_count
                  FROM segments s";
        
        $conditions = [];
        $params = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $conditions[] = "s.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['type']) && !empty($filters['type'])) {
            $conditions[] = "s.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $conditions[] = "(s.name LIKE :search OR s.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a single segment by ID
     */
    public function getSegmentById($id) {
        $query = "SELECT s.*, 
                         (SELECT COUNT(*) FROM segment_members sm WHERE sm.segment_id = s.id) as member_count
                  FROM segments s 
                  WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new segment
     */
    public function createSegment($data) {
        $query = "INSERT INTO segments 
                  (name, description, type, size_estimate, engagement_rate, status, criteria, created_by) 
                  VALUES (:name, :description, :type, :size_estimate, :engagement_rate, :status, :criteria, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':size_estimate', $data['size_estimate']);
        $stmt->bindParam(':engagement_rate', $data['engagement_rate']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':criteria', $data['criteria']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        return $stmt->execute();
    }
    
    /**
     * Update a segment
     */
    public function updateSegment($id, $data) {
        $query = "UPDATE segments 
                  SET name = :name, description = :description, type = :type, 
                      size_estimate = :size_estimate, engagement_rate = :engagement_rate, 
                      status = :status, criteria = :criteria
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':size_estimate', $data['size_estimate']);
        $stmt->bindParam(':engagement_rate', $data['engagement_rate']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':criteria', $data['criteria']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a segment
     */
    public function deleteSegment($id) {
        $query = "DELETE FROM segments WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get segment analytics
     */
    public function getSegmentAnalytics() {
        $analytics = [];
        
        // Total segments count
        $query = "SELECT COUNT(*) as total FROM segments";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['total_segments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Count by status
        $query = "SELECT status, COUNT(*) as count FROM segments GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $analytics['by_status'] = [
            'active' => 0,
            'draft' => 0,
            'archived' => 0
        ];
        
        foreach ($statusCounts as $row) {
            $analytics['by_status'][$row['status']] = $row['count'];
        }
        
        // Count by type
        $query = "SELECT type, COUNT(*) as count FROM segments GROUP BY type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Engagement rate stats
        $query = "SELECT AVG(engagement_rate) as avg_engagement, 
                         MAX(engagement_rate) as max_engagement,
                         MIN(engagement_rate) as min_engagement
                  FROM segments 
                  WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $engagementStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $analytics['engagement_stats'] = [
            'average' => round($engagementStats['avg_engagement'], 2) ?: 0,
            'highest' => round($engagementStats['max_engagement'], 2) ?: 0,
            'lowest' => round($engagementStats['min_engagement'], 2) ?: 0
        ];
        
        // Total members across all segments
        $query = "SELECT SUM(
                    (SELECT COUNT(*) FROM segment_members sm WHERE sm.segment_id = s.id)
                  ) as total_members
                  FROM segments s";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $analytics['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_members'] ?: 0;
        
        return $analytics;
    }
    
    /**
     * Get all communication channels
     */
    public function getCommunicationChannels() {
        $query = "SELECT * FROM communication_channels ORDER BY preference_score DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get channel preferences for a specific segment
     */
    public function getChannelPreferences($segmentId) {
        $query = "SELECT sc.*, cc.name as channel_name, cc.description as channel_description
                  FROM segment_channel_preferences sc
                  JOIN communication_channels cc ON sc.channel_id = cc.id
                  WHERE sc.segment_id = :segment_id
                  ORDER BY sc.preference_score DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get A/B testing groups for a segment
     */
    public function getAbTestingGroups($segmentId) {
        $query = "SELECT * FROM ab_testing_groups 
                  WHERE segment_id = :segment_id
                  ORDER BY response_rate DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get privacy compliance status for a segment
     */
    public function getPrivacyCompliance($segmentId) {
        $query = "SELECT * FROM privacy_compliance 
                  WHERE segment_id = :segment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get segment overlap analysis
     */
    public function getSegmentOverlap($segmentId) {
        $query = "SELECT so.*, s1.name as segment1_name, s2.name as segment2_name
                  FROM segment_overlap so
                  JOIN segments s1 ON so.segment1_id = s1.id
                  JOIN segments s2 ON so.segment2_id = s2.id
                  WHERE so.segment1_id = :segment_id OR so.segment2_id = :segment_id
                  ORDER BY so.overlap_percentage DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get demographic criteria for a segment
     */
    public function getDemographicCriteria($segmentId) {
        $query = "SELECT * FROM demographic_criteria 
                  WHERE segment_id = :segment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get behavioral criteria for a segment
     */
    public function getBehavioralCriteria($segmentId) {
        $query = "SELECT * FROM behavioral_criteria 
                  WHERE segment_id = :segment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get geographic criteria for a segment
     */
    public function getGeographicCriteria($segmentId) {
        $query = "SELECT * FROM geographic_criteria 
                  WHERE segment_id = :segment_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search segments
     */
    public function searchSegments($searchTerm) {
        $query = "SELECT s.*, 
                         (SELECT COUNT(*) FROM segment_members sm WHERE sm.segment_id = s.id) as member_count
                  FROM segments s 
                  WHERE s.name LIKE :search 
                  OR s.description LIKE :search
                  ORDER BY s.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search', '%' . $searchTerm . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get members of a segment
     */
    public function getSegmentMembers($segmentId, $limit = 50, $offset = 0) {
        $query = "SELECT sm.*, u.name as member_name
                  FROM segment_members sm
                  LEFT JOIN users u ON sm.member_id = u.id
                  WHERE sm.segment_id = :segment_id
                  ORDER BY sm.joined_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add member to a segment
     */
    public function addMemberToSegment($segmentId, $memberId) {
        $query = "INSERT IGNORE INTO segment_members (segment_id, member_id) 
                  VALUES (:segment_id, :member_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->bindParam(':member_id', $memberId);
        
        return $stmt->execute();
    }
    
    /**
     * Remove member from a segment
     */
    public function removeMemberFromSegment($segmentId, $memberId) {
        $query = "DELETE FROM segment_members 
                  WHERE segment_id = :segment_id AND member_id = :member_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->bindParam(':member_id', $memberId);
        
        return $stmt->execute();
    }
}
?>