<?php
// Check if session is already started to avoid errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// Load all available models
$models = [];
$modelsDir = __DIR__;

// Define all model files that have class structures
$modelFiles = [
    'ContentRepository' => $modelsDir . '/ContentRepository.php',
    'TargetGroupSegmentation' => $modelsDir . '/TargetGroupSegmentation.php'
];

// Load available models
foreach ($modelFiles as $className => $filePath) {
    if (file_exists($filePath)) {
        require_once $filePath;
        if (class_exists($className)) {
            try {
                $models[$className] = new $className();
            } catch (Exception $e) {
                error_log("Failed to instantiate $className: " . $e->getMessage());
            }
        }
    }
}

// Set primary model for content repository
$contentRepo = null;
if (isset($models['ContentRepository'])) {
    $contentRepo = $models['ContentRepository'];
}

// Validate that we have at least one content repository model
if (!$contentRepo) {
    die("Error: No content repository model found. Please ensure ContentRepository.php exists.");
}

try {
    // Fetch data from primary content repository model
    if ($contentRepo) {
        $stats = method_exists($contentRepo, 'getContentStats') ? $contentRepo->getContentStats() : [];
        $contentItems = method_exists($contentRepo, 'getContentItems') ? $contentRepo->getContentItems() : [];
        $categories = method_exists($contentRepo, 'getCategories') ? $contentRepo->getCategories() : [];
        
        // If no stats found, create sample data for demonstration
        if (empty($stats)) {
            $stats = [
                'total' => 156,
                'by_status' => [
                    'draft' => 12,
                    'pending' => 23,
                    'approved' => 121,
                    'rejected' => 0
                ],
                'by_category' => [
                    ['name' => 'documents', 'count' => 45],
                    ['name' => 'images', 'count' => 67],
                    ['name' => 'videos', 'count' => 23],
                    ['name' => 'audio', 'count' => 21]
                ],
                'recent' => [
                    ['name' => 'Emergency_Protocol_v1.pdf', 'size' => '2.4 MB', 'created_at' => date('Y-m-d H:i:s')],
                    ['name' => 'Safety_Brochure_v2.pdf', 'size' => '1.8 MB', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
                    ['name' => 'Health_Awareness_Poster.jpg', 'size' => '3.2 MB', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
                    ['name' => 'COVID_Safety_Video.mp4', 'size' => '45.6 MB', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
                    ['name' => 'Fire_Evacuation_Guide.pdf', 'size' => '1.1 MB', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))]
                ],
                'expiring_soon' => []
            ];
        }
        
        // If no content items found, create sample data
        if (empty($contentItems)) {
            $contentItems = [
                [
                    'id' => 1,
                    'name' => 'Emergency Protocol Manual',
                    'file_path' => '/uploads/emergency_protocol.pdf',
                    'category' => 'documents',
                    'size' => '2.4 MB',
                    'file_type' => 'pdf',
                    'description' => 'Comprehensive emergency response protocol',
                    'status' => 'approved',
                    'version' => '1.2',
                    'tags' => 'emergency,protocol,response',
                    'created_at' => date('Y-m-d H:i:s'),
                    'download_count' => 124
                ],
                [
                    'id' => 2,
                    'name' => 'Safety Brochure',
                    'file_path' => '/uploads/safety_brochure.pdf',
                    'category' => 'documents',
                    'size' => '1.8 MB',
                    'file_type' => 'pdf',
                    'description' => 'General safety awareness brochure',
                    'status' => 'approved',
                    'version' => '2.1',
                    'tags' => 'safety,brochure,awareness',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'download_count' => 89
                ]
            ];
        }
        
        // If no categories found, create sample categories
        if (empty($categories)) {
            $categories = [
                ['name' => 'documents', 'icon_class' => 'fa-file-pdf'],
                ['name' => 'images', 'icon_class' => 'fa-file-image'],
                ['name' => 'videos', 'icon_class' => 'fa-file-video'],
                ['name' => 'audio', 'icon_class' => 'fa-file-audio']
            ];
        }
    } else {
        // Fallback data if no content repository model available
        $stats = [];
        $contentItems = [];
        $categories = [];
    }
    
    // Enhance data with information from other models if available
    if (isset($models['ContentRepository'])) {
        $contentRepo = $models['ContentRepository'];
        
        // Cross-module integration functions for linking with Event Management
        if (!function_exists('getContentForEvent')) {
            function getContentForEvent($eventId) {
                global $models;
                if (isset($models['ContentRepository'])) {
                    $repo = $models['ContentRepository'];
                    if (method_exists($repo, 'getContentForEvent')) {
                        return $repo->getContentForEvent($eventId);
                    }
                }
                return [];
            }
        }
        
        if (!function_exists('linkContentToEvent')) {
            function linkContentToEvent($contentId, $eventId, $relevanceScore = 5) {
                global $models;
                if (isset($models['ContentRepository'])) {
                    $repo = $models['ContentRepository'];
                    if (method_exists($repo, 'linkContentToEvent')) {
                        return $repo->linkContentToEvent($contentId, $eventId, $relevanceScore);
                    }
                }
                return false;
            }
        }
        
        if (!function_exists('getEventsForContent')) {
            function getEventsForContent($contentId) {
                global $models;
                if (isset($models['ContentRepository'])) {
                    $repo = $models['ContentRepository'];
                    if (method_exists($repo, 'getEventsForContent')) {
                        return $repo->getEventsForContent($contentId);
                    }
                }
                return [];
            }
        }
        
        // Cross-module integration functions for linking with Target Group Segmentation
        if (!function_exists('getContentBySegment')) {
            function getContentBySegment($segmentId) {
                global $models;
                if (isset($models['ContentRepository'])) {
                    $repo = $models['ContentRepository'];
                    if (method_exists($repo, 'getContentItems')) {
                        $filters = ['search' => ''];
                        $allContent = $repo->getContentItems($filters);
                        
                        // In a real implementation, we would filter based on segment,
                        // but for now we return all content
                        return $allContent;
                    }
                }
                return [];
            }
        }
        
        if (!function_exists('linkContentToSegment')) {
            function linkContentToSegment($contentId, $segmentId, $scenario = 'general') {
                global $models;
                if (isset($models['TargetGroupSegmentation'])) {
                    $segModel = $models['TargetGroupSegmentation'];
                    if (method_exists($segModel, 'linkContentToSegment')) {
                        return $segModel->linkContentToSegment($contentId, $segmentId, $scenario);
                    }
                }
                return false;
            }
        }
        
        if (!function_exists('getSegmentsForContent')) {
            function getSegmentsForContent($contentId) {
                global $models;
                if (isset($models['TargetGroupSegmentation'])) {
                    $segModel = $models['TargetGroupSegmentation'];
                    if (method_exists($segModel, 'getSegments')) {
                        // This would need a custom method in TargetGroupSegmentation to get segments for specific content
                        // For now, return all segments
                        return $segModel->getSegments();
                    }
                }
                return [];
            }
        }
        
        // Additional cross-module functions for enhanced integration
        if (!function_exists('getSegmentContentScenarios')) {
            function getSegmentContentScenarios($contentId) {
                global $models;
                if (isset($models['TargetGroupSegmentation'])) {
                    $segModel = $models['TargetGroupSegmentation'];
                    if (method_exists($segModel, 'getSegments')) {
                        // Return all segments associated with this content
                        return $segModel->getSegments();
                    }
                }
                return [];
            }
        }
        
        if (!function_exists('getContentForSegment')) {
            function getContentForSegment($segmentId) {
                global $models;
                if (isset($models['TargetGroupSegmentation'])) {
                    $segModel = $models['TargetGroupSegmentation'];
                    if (method_exists($segModel, 'getContentBySegment')) {
                        return $segModel->getContentBySegment($segmentId);
                    }
                }
                // Fallback to getting all content
                if (isset($models['ContentRepository'])) {
                    $repo = $models['ContentRepository'];
                    if (method_exists($repo, 'getContentItems')) {
                        return $repo->getContentItems();
                    }
                }
                return [];
            }
        }
    }
    
} catch (Exception $e) {
    // If methods don't exist, use fallback data
    $stats = [];
    $contentItems = [];
    $categories = [];
    error_log("Model Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/repo.css">
    <title>Content Repository</title>
    <style>
        /* Content Repository Specific Styles */
    </style>
</head>
<body>
    <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h1>Public Safety</h1>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="../home.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Module-1.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Campaign Planning & Calendar</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Content-Repository.php" class="nav-link active">
                    <i class="fas fa-database"></i>
                    <span class="nav-text">Content Repository</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Target-Group-Segmentation.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Target Group Segmentation</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="EventSeminarManagement.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span class="nav-text">Event & Seminar Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="SurveyFeedbackCollection.php" class="nav-link">
                    <i class="fas fa-clipboard-check"></i>
                    <span class="nav-text">Survey & Feedback Collection</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="CampaignAnalyticsReports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Campaign Analytics & Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="HealthPoliceIntegration.php" class="nav-link">
                    <i class="fas fa-link"></i>
                    <span class="nav-text">Community</span>
                </a>
            </li>
        </ul>
    </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h2>Content Repository</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search content, tags, categories..." id="searchInput">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)); ?></div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></div>
                            <div style="font-size: 13px; color: var(--text-gray);">Content Manager</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Content -->
            <div class="module-header">
                <div>
                    <h1 class="module-title">Content Repository</h1>
                    <p class="module-subtitle">Centralized storage for all public safety communication materials</p>
                </div>
                <button class="btn" onclick="uploadNewContent()">
                    <i class="fas fa-upload"></i> Upload New
                </button>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter">
                <input type="text" placeholder="Search by filename, tags, description..." id="filterSearch">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <div class="module-grid">
                <!-- Asset Library -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Asset Library</div>
                        <div class="card-icon">
                            <i class="fas fa-images"></i>
                        </div>
                    </div>
                    <div class="asset-categories">
                        <div class="category-item">
                            <i class="fas fa-file-alt"></i>
                            <span>Documents</span>
                            <span class="category-count"><?php echo $stats['by_category']['documents'] ?? 0; ?> files</span>
                        </div>
                        <div class="category-item">
                            <i class="fas fa-image"></i>
                            <span>Images</span>
                            <span class="category-count"><?php echo $stats['by_category']['images'] ?? 0; ?> files</span>
                        </div>
                        <div class="category-item">
                            <i class="fas fa-video"></i>
                            <span>Videos</span>
                            <span class="category-count"><?php echo $stats['by_category']['videos'] ?? 0; ?> files</span>
                        </div>
                        <div class="category-item">
                            <i class="fas fa-microphone"></i>
                            <span>Audio Files</span>
                            <span class="category-count"><?php echo $stats['by_category']['audio'] ?? 0; ?> files</span>
                        </div>
                        <div class="category-item">
                            <i class="fas fa-language"></i>
                            <span>Translations</span>
                            <span class="category-count">12 languages</span>
                        </div>
                    </div>
                </div>

                <!-- Recently Added -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Recently Added</div>
                        <div class="card-icon">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                    <div class="recent-items">
                        <?php foreach (array_slice($stats['recent'], 0, 4) as $item): ?>
                        <div class="recent-item">
                            <i class="fas fa-file-<?php echo pathinfo($item['name'], PATHINFO_EXTENSION) ?: 'alt'; ?>"></i>
                            <div>
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-date">Added: <?php echo date('M j, Y', strtotime($item['created_at'])); ?> • <?php echo $item['size']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Approval Workflow -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Approval Workflow</div>
                        <div class="card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="approval-stats">
                        <div class="approval-item pending">
                            <span>Pending Review</span>
                            <span class="count"><?php echo $stats['by_status']['pending']; ?></span>
                        </div>
                        <div class="approval-item approved">
                            <span>Approved</span>
                            <span class="count"><?php echo $stats['by_status']['approved']; ?></span>
                        </div>
                        <div class="approval-item rejected">
                            <span>Needs Revision</span>
                            <span class="count"><?php echo $stats['by_status']['rejected']; ?></span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="openReviewQueue()">
                        <i class="fas fa-tasks"></i> Review Queue
                    </button>
                </div>

                <!-- Expiration Management -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Expiring Soon</div>
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="expiration-list">
                        <?php foreach (array_slice($stats['expiring_soon'], 0, 3) as $item): ?>
                        <div class="expiring-item">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-expiry">Expires: <?php echo date('M j, Y', strtotime($item['expiry_date'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="setReminders()">
                        <i class="fas fa-bell"></i> Set Reminders
                    </button>
                </div>

                <!-- Content Analytics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Content Analytics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo round(array_sum(array_column($stats['by_category'], 'count')) * 2.5); ?>MB</div>
                            <div class="stat-label">Total Storage</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">247</div>
                            <div class="stat-label">Active Users</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">85%</div>
                            <div class="stat-label">Reuse Rate</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo array_sum(array_column($stats['recent'], 'download_count')); ?></div>
                            <div class="stat-label">Downloads Today</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="quick-actions-grid">
                        <button class="action-btn" onclick="uploadNewContent()">
                            <i class="fas fa-upload"></i>
                            <span>Upload New</span>
                        </button>
                        <button class="action-btn" onclick="advancedSearch()">
                            <i class="fas fa-search"></i>
                            <span>Advanced Search</span>
                        </button>
                        <button class="action-btn" onclick="manageTags()">
                            <i class="fas fa-tags"></i>
                            <span>Manage Tags</span>
                        </button>
                        <button class="action-btn" onclick="bulkExport()">
                            <i class="fas fa-download"></i>
                            <span>Bulk Export</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Content Display -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">Uploaded Content</div>
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div id="content-display-area">
                    <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>No content uploaded yet</p>
                        <p style="font-size: 14px;">Upload safety content using the buttons above</p>
                    </div>
                </div>
            </div>

            <!-- Metadata Management -->
            <div class="module-grid" style="margin-top: 30px;">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Popular Tags</div>
                        <div class="card-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #emergency
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #safety
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #health
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #prevention
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #awareness
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #training
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #covid19
                        </span>
                        <span style="padding: 8px 15px; background-color: var(--dark-gray); border-radius: 20px; font-size: 14px;">
                            #vaccination
                        </span>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 20px;">
                        <i class="fas fa-plus"></i> Add New Tag
                    </button>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Storage Overview</div>
                        <div class="card-icon">
                            <i class="fas fa-hard-drive"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Documents</span>
                            <span>650 MB (54%)</span>
                        </div>
                        <div style="height: 8px; background-color: var(--dark-gray); border-radius: 4px;">
                            <div style="width: 54%; height: 100%; background-color: var(--accent); border-radius: 4px;"></div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin: 20px 0 10px;">
                            <span>Images</span>
                            <span>350 MB (29%)</span>
                        </div>
                        <div style="height: 8px; background-color: var(--dark-gray); border-radius: 4px;">
                            <div style="width: 29%; height: 100%; background-color: var(--success); border-radius: 4px;"></div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin: 20px 0 10px;">
                            <span>Videos</span>
                            <span>180 MB (15%)</span>
                        </div>
                        <div style="height: 8px; background-color: var(--dark-gray); border-radius: 4px;">
                            <div style="width: 15%; height: 100%; background-color: var(--warning); border-radius: 4px;"></div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin: 20px 0 10px;">
                            <span>Audio</span>
                            <span>20 MB (2%)</span>
                        </div>
                        <div style="height: 8px; background-color: var(--dark-gray); border-radius: 4px;">
                            <div style="width: 2%; height: 100%; background-color: var(--danger); border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../Scripts/mod2.js"></script>
    <script src="../Scripts/nodeserver.js"></script>

    <!-- <script src="../Scripts/userprofile.js"></script> -->
    
    <script>
        // Update content display area with actual content
        function updateContentDisplay(items) {
            const displayArea = document.getElementById('content-display-area');
            if (!items || items.length === 0) {
                displayArea.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>No content found matching your criteria</p>
                        <p style="font-size: 14px;">Try adjusting your search or filter settings</p>
                    </div>
                `;
                return;
            }

            let contentHTML = '';
            items.forEach(item => {
                contentHTML += `
                    <div class="content-card" style="
                        background: var(--dark-gray);
                        border-radius: 8px;
                        padding: 15px;
                        margin-bottom: 15px;
                        transition: all 0.3s ease;
                        border: 1px solid transparent;
                    ">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="
                                width: 40px;
                                height: 40px;
                                background: linear-gradient(135deg, var(--accent), #ff6b35);
                                border-radius: 8px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-size: 16px;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-file-${item.file_type || 'alt'}"></i>
                            </div>
                            <div style="flex-grow: 1; overflow: hidden;">
                                <div style="font-weight: 600; font-size: 15px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                     title="${item.name}">
                                    ${item.name}
                                </div>
                                <div style="font-size: 12px; color: var(--text-gray);">
                                    v${item.version || '1.0'} • ${item.size}
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 13px;">
                            <div>
                                <div style="color: var(--text-gray);">Category</div>
                                <div style="color: white; font-weight: 500;">${item.category}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-gray);">Status</div>
                                <div>
                                    <span class="status-badge status-${item.status}" style="
                                        padding: 3px 10px;
                                        border-radius: 15px;
                                        font-size: 11px;
                                        font-weight: 600;
                                        text-transform: uppercase;
                                    ">
                                        ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button onclick="previewContent(${item.id})" 
                                    style="flex: 1; min-width: 70px; background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button onclick="downloadContent(${item.id})" 
                                    style="flex: 1; min-width: 70px; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                                <i class="fas fa-download"></i> Get
                            </button>
                        </div>
                        
                        ${item.status === 'pending' ? `
                        <div style="display: flex; gap: 5px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <button onclick="approveContent(${item.id})" 
                                    style="flex: 1; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="rejectContent(${item.id})" 
                                    style="flex: 1; background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                        ` : ''}
                    </div>
                `;
            });
            
            displayArea.innerHTML = contentHTML;
        }

        // Example: Load content when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial content (you would get this from your PHP backend)
            const initialContent = <?php echo json_encode($contentItems); ?>;
            if (initialContent && initialContent.length > 0) {
                updateContentDisplay(initialContent);
            }
        });

        // Add event listeners for filters
        document.getElementById('filterSearch').addEventListener('input', function() {
            applyFilters();
        });

        document.getElementById('categoryFilter').addEventListener('change', function() {
            applyFilters();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
            applyFilters();
        });

        function applyFilters() {
            const searchTerm = document.getElementById('filterSearch').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            const initialContent = <?php echo json_encode($contentItems); ?>;
            let filtered = initialContent.filter(item => {
                const matchesSearch = !searchTerm || 
                    item.name.toLowerCase().includes(searchTerm) ||
                    item.description.toLowerCase().includes(searchTerm);
                
                const matchesCategory = !category || item.category === category;
                const matchesStatus = !status || item.status === status;
                
                return matchesSearch && matchesCategory && matchesStatus;
            });
            
            updateContentDisplay(filtered);
        }
    </script>
</body>
</html>