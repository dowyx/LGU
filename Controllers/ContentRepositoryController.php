<?php
/**
 * Content Repository Controller
 * Handles all content repository operations
 */

require_once '../Models/ContentRepository.php';

class ContentRepositoryController {
    private $contentRepo;
    
    public function __construct() {
        $this->contentRepo = new ContentRepository();
    }
    
    /**
     * Handle file upload
     */
    public function uploadContent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error'];
        }
        
        $file = $_FILES['file'];
        $name = $_POST['name'] ?? basename($file['name']);
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $uploadedBy = $_SESSION['user_id'] ?? 1; // Default to first user if not set
        
        // Validate inputs
        if (empty($category) || empty($description)) {
            return ['success' => false, 'message' => 'Category and description are required'];
        }
        
        // Define upload directory
        $uploadDir = '../uploads/content_repository/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to save file'];
        }
        
        // Prepare data for insertion
        $data = [
            'name' => $name,
            'file_path' => $filePath,
            'category' => $category,
            'size' => $this->formatFileSize($file['size']),
            'file_type' => pathinfo($fileName, PATHINFO_EXTENSION),
            'description' => $description,
            'version' => '1.0',
            'tags' => $tags,
            'uploaded_by' => $uploadedBy
        ];
        
        // Create content item
        if ($this->contentRepo->createContentItem($data)) {
            return [
                'success' => true, 
                'message' => 'Content uploaded successfully',
                'file_path' => $filePath
            ];
        } else {
            // Remove file if database insert fails
            unlink($filePath);
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update content item
     */
    public function updateContent($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? '';
        $version = $_POST['version'] ?? '1.0';
        $tags = $_POST['tags'] ?? '';
        $expiryDate = $_POST['expiry_date'] ?? null;
        
        // Validate required fields
        if (empty($name) || empty($category) || empty($description)) {
            return ['success' => false, 'message' => 'Name, category, and description are required'];
        }
        
        $data = [
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'status' => $status,
            'version' => $version,
            'tags' => $tags,
            'expiry_date' => $expiryDate
        ];
        
        if ($this->contentRepo->updateContentItem($id, $data)) {
            return ['success' => true, 'message' => 'Content updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update content'];
        }
    }
    
    /**
     * Delete content item
     */
    public function deleteContent($id) {
        // First get the content item to check file path
        $contentItem = $this->contentRepo->getContentItemById($id);
        
        if (!$contentItem) {
            return ['success' => false, 'message' => 'Content not found'];
        }
        
        // Delete the physical file if it exists
        if (file_exists($contentItem['file_path'])) {
            unlink($contentItem['file_path']);
        }
        
        // Delete from database
        if ($this->contentRepo->deleteContentItem($id)) {
            return ['success' => true, 'message' => 'Content deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete content'];
        }
    }
    
    /**
     * Update content status (for approval workflow)
     */
    public function updateStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        $status = $_POST['status'] ?? '';
        $approverId = $_SESSION['user_id'] ?? null;
        $comments = $_POST['comments'] ?? '';
        
        if (!in_array($status, ['draft', 'pending', 'approved', 'rejected'])) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        if ($this->contentRepo->updateContentStatus($id, $status, $approverId, $comments)) {
            return ['success' => true, 'message' => 'Status updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
    
    /**
     * Handle content download
     */
    public function downloadContent($id) {
        $contentItem = $this->contentRepo->getContentItemById($id);
        
        if (!$contentItem) {
            http_response_code(404);
            return false;
        }
        
        $filePath = $contentItem['file_path'];
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            return false;
        }
        
        // Increment download count
        $this->contentRepo->incrementDownloadCount($id);
        
        // Log download
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->contentRepo->logDownload($id, $userId, $ipAddress);
        
        // Serve the file
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = $this->getMimeType($fileExtension);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($filePath);
        exit();
    }
    
    /**
     * Search content
     */
    public function searchContent() {
        $searchTerm = $_GET['q'] ?? '';
        
        if (empty($searchTerm)) {
            return ['success' => false, 'message' => 'Search term is required'];
        }
        
        $results = $this->contentRepo->searchContent($searchTerm);
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
    
    /**
     * Get all content items with filters
     */
    public function getContentItems() {
        $filters = [];
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $contentItems = $this->contentRepo->getContentItems($filters);
        
        return [
            'success' => true,
            'data' => $contentItems
        ];
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get MIME type based on file extension
     */
    private function getMimeType($extension) {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            'csv' => 'text/csv',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            'tar' => 'application/x-tar'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $controller = new ContentRepositoryController();
    $action = $_GET['action'];
    $response = null;
    
    switch ($action) {
        case 'upload':
            $response = $controller->uploadContent();
            break;
        case 'update':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->updateContent($id);
            } else {
                $response = ['success' => false, 'message' => 'Content ID is required'];
            }
            break;
        case 'delete':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->deleteContent($id);
            } else {
                $response = ['success' => false, 'message' => 'Content ID is required'];
            }
            break;
        case 'updateStatus':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $response = $controller->updateStatus($id);
            } else {
                $response = ['success' => false, 'message' => 'Content ID is required'];
            }
            break;
        case 'download':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $controller->downloadContent($id);
            }
            break;
        case 'search':
            $response = $controller->searchContent();
            break;
        case 'getContent':
            $response = $controller->getContentItems();
            break;
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>