<?php
/**
 * Target Group Segmentation Controller
 * Handles all target group segmentation operations
 */

require_once '../Models/TargetGroupSegmentation.php';

class TargetGroupSegmentationController {
    private $segModel;
    
    public function __construct() {
        $this->segModel = new TargetGroupSegmentation();
    }
    
    /**
     * Get all segments with filters
     */
    public function getSegments() {
        $filters = [];
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $segments = $this->segModel->getSegments($filters);
        
        return [
            'success' => true,
            'data' => $segments
        ];
    }
    
    /**
     * Get a single segment by ID
     */
    public function getSegment($id) {
        $segment = $this->segModel->getSegmentById($id);
        
        if ($segment) {
            return [
                'success' => true,
                'data' => $segment
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Segment not found'
            ];
        }
    }
    
    /**
     * Create a new segment
     */
    public function createSegment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? 'demographic';
        $sizeEstimate = $_POST['size_estimate'] ?? 0;
        $engagementRate = $_POST['engagement_rate'] ?? 0;
        $status = $_POST['status'] ?? 'draft';
        $criteria = $_POST['criteria'] ?? '{}';
        $createdBy = $_SESSION['user_id'] ?? 1; // Default to first user if not set
        
        // Validate required fields
        if (empty($name)) {
            return ['success' => false, 'message' => 'Segment name is required'];
        }
        
        if (!in_array($type, ['demographic', 'behavioral', 'geographic', 'psychographic'])) {
            return ['success' => false, 'message' => 'Invalid segment type'];
        }
        
        if (!in_array($status, ['active', 'draft', 'archived'])) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'size_estimate' => $sizeEstimate,
            'engagement_rate' => $engagementRate,
            'status' => $status,
            'criteria' => $criteria,
            'created_by' => $createdBy
        ];
        
        if ($this->segModel->createSegment($data)) {
            return [
                'success' => true,
                'message' => 'Segment created successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to create segment'];
        }
    }
    
    /**
     * Update a segment
     */
    public function updateSegment($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? 'demographic';
        $sizeEstimate = $_POST['size_estimate'] ?? 0;
        $engagementRate = $_POST['engagement_rate'] ?? 0;
        $status = $_POST['status'] ?? 'draft';
        $criteria = $_POST['criteria'] ?? '{}';
        
        // Validate required fields
        if (empty($name)) {
            return ['success' => false, 'message' => 'Segment name is required'];
        }
        
        if (!in_array($type, ['demographic', 'behavioral', 'geographic', 'psychographic'])) {
            return ['success' => false, 'message' => 'Invalid segment type'];
        }
        
        if (!in_array($status, ['active', 'draft', 'archived'])) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'size_estimate' => $sizeEstimate,
            'engagement_rate' => $engagementRate,
            'status' => $status,
            'criteria' => $criteria
        ];
        
        if ($this->segModel->updateSegment($id, $data)) {
            return [
                'success' => true,
                'message' => 'Segment updated successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update segment'];
        }
    }
    
    /**
     * Delete a segment
     */
    public function deleteSegment($id) {
        if ($this->segModel->deleteSegment($id)) {
            return [
                'success' => true,
                'message' => 'Segment deleted successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to delete segment'];
        }
    }
    
    /**
     * Get segment analytics
     */
    public function getAnalytics() {
        $analytics = $this->segModel->getSegmentAnalytics();
        
        return [
            'success' => true,
            'data' => $analytics
        ];
    }
    
    /**
     * Get communication channels
     */
    public function getChannels() {
        $channels = $this->segModel->getCommunicationChannels();
        
        return [
            'success' => true,
            'data' => $channels
        ];
    }
    
    /**
     * Get A/B testing groups for a segment
     */
    public function getAbTestingGroups($segmentId) {
        $groups = $this->segModel->getAbTestingGroups($segmentId);
        
        return [
            'success' => true,
            'data' => $groups
        ];
    }
    
    /**
     * Get privacy compliance for a segment
     */
    public function getPrivacyCompliance($segmentId) {
        $compliance = $this->segModel->getPrivacyCompliance($segmentId);
        
        return [
            'success' => true,
            'data' => $compliance
        ];
    }
    
    /**
     * Search segments
     */
    public function searchSegments() {
        $searchTerm = $_GET['q'] ?? '';
        
        if (empty($searchTerm)) {
            return ['success' => false, 'message' => 'Search term is required'];
        }
        
        $results = $this->segModel->searchSegments($searchTerm);
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
    
    /**
     * Add member to a segment
     */
    public function addMemberToSegment($segmentId, $memberId) {
        if ($this->segModel->addMemberToSegment($segmentId, $memberId)) {
            return [
                'success' => true,
                'message' => 'Member added to segment successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add member to segment'];
        }
    }
    
    /**
     * Remove member from a segment
     */
    public function removeMemberFromSegment($segmentId, $memberId) {
        if ($this->segModel->removeMemberFromSegment($segmentId, $memberId)) {
            return [
                'success' => true,
                'message' => 'Member removed from segment successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to remove member from segment'];
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new TargetGroupSegmentationController();
    $action = $_GET['action'];
    $response = null;
    
    switch ($action) {
        case 'getSegments':
            $response = $controller->getSegments();
            break;
        case 'getSegment':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->getSegment($id);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID is required'];
            }
            break;
        case 'createSegment':
            $response = $controller->createSegment();
            break;
        case 'updateSegment':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->updateSegment($id);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID is required'];
            }
            break;
        case 'deleteSegment':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->deleteSegment($id);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID is required'];
            }
            break;
        case 'getAnalytics':
            $response = $controller->getAnalytics();
            break;
        case 'getChannels':
            $response = $controller->getChannels();
            break;
        case 'getAbTestingGroups':
            $segmentId = $_GET['segmentId'] ?? null;
            if ($segmentId) {
                $response = $controller->getAbTestingGroups($segmentId);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID is required'];
            }
            break;
        case 'getPrivacyCompliance':
            $segmentId = $_GET['segmentId'] ?? null;
            if ($segmentId) {
                $response = $controller->getPrivacyCompliance($segmentId);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID is required'];
            }
            break;
        case 'searchSegments':
            $response = $controller->searchSegments();
            break;
        case 'addMember':
            $segmentId = $_GET['segmentId'] ?? null;
            $memberId = $_GET['memberId'] ?? null;
            if ($segmentId && $memberId) {
                $response = $controller->addMemberToSegment($segmentId, $memberId);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID and Member ID are required'];
            }
            break;
        case 'removeMember':
            $segmentId = $_GET['segmentId'] ?? null;
            $memberId = $_GET['memberId'] ?? null;
            if ($segmentId && $memberId) {
                $response = $controller->removeMemberFromSegment($segmentId, $memberId);
            } else {
                $response = ['success' => false, 'message' => 'Segment ID and Member ID are required'];
            }
            break;
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>