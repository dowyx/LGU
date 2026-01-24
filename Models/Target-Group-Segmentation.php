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
    'TargetGroupSegmentation' => $modelsDir . '/TargetGroupSegmentation.php',
    'ContentRepository' => $modelsDir . '/ContentRepository.php'
];

// Include EventSeminarManagement separately as it's procedural
$eventSeminarFile = $modelsDir . '/EventSeminarManagement.php';
if (file_exists($eventSeminarFile)) {
    require_once $eventSeminarFile;
}
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


// Set primary model for segmentation
$segModel = null;
if (isset($models['TargetGroupSegmentation'])) {
    $segModel = $models['TargetGroupSegmentation'];
}

// Validate that we have at least one segmentation model
if (!$segModel) {
    die("Error: No segmentation model found. Please ensure TargetGroupSegmentation.php exists.");
}

try {
    // Fetch data from primary segmentation model
    if ($segModel) {
        $segments = method_exists($segModel, 'getSegments') ? $segModel->getSegments() : [];
        $analytics = method_exists($segModel, 'getSegmentAnalytics') ? $segModel->getSegmentAnalytics() : [
            'total_segments' => 0,
            'engagement_stats' => ['average' => 0],
            'total_members' => 0
        ];
        $channels = method_exists($segModel, 'getCommunicationChannels') ? $segModel->getCommunicationChannels() : [];
        
        // If no segments found, create sample data for demonstration
        if (empty($segments)) {
            $segments = [
                [
                    'id' => 1,
                    'name' => 'High-Risk Population',
                    'description' => 'Individuals with high health risk factors',
                    'type' => 'demographic',
                    'size_estimate' => 15420,
                    'engagement_rate' => 78,
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'name' => 'Senior Citizens',
                    'description' => 'Citizens aged 60 and above',
                    'type' => 'demographic',
                    'size_estimate' => 8930,
                    'engagement_rate' => 65,
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'name' => 'Parents with Children',
                    'description' => 'Households with children under 18',
                    'type' => 'demographic',
                    'size_estimate' => 12750,
                    'engagement_rate' => 82,
                    'status' => 'active',
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 4,
                    'name' => 'Young Professionals',
                    'description' => 'Working adults aged 25-40',
                    'type' => 'behavioral',
                    'size_estimate' => 9800,
                    'engagement_rate' => 45,
                    'status' => 'draft',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        // If no analytics data, create sample analytics
        if ($analytics['total_segments'] == 0) {
            $analytics = [
                'total_segments' => 4,
                'engagement_stats' => ['average' => 67.5],
                'total_members' => 46900
            ];
        }
        
        // If no channels, create sample channels
        if (empty($channels)) {
            $channels = [
                [
                    'id' => 1,
                    'name' => 'Email Newsletter',
                    'preference_score' => 75,
                    'reach_percentage' => 68
                ],
                [
                    'id' => 2,
                    'name' => 'SMS Alerts',
                    'preference_score' => 82,
                    'reach_percentage' => 92
                ],
                [
                    'id' => 3,
                    'name' => 'Social Media',
                    'preference_score' => 65,
                    'reach_percentage' => 45
                ],
                [
                    'id' => 4,
                    'name' => 'Community Bulletin',
                    'preference_score' => 55,
                    'reach_percentage' => 78
                ]
            ];
        }
    } else {
        // Fallback data if no segmentation model available
        $segments = [];
        $analytics = [
            'total_segments' => 0,
            'engagement_stats' => ['average' => 0],
            'total_members' => 0
        ];
        $channels = [];
    }
    
    // Enhance data with information from other models if available
    if (isset($models['TargetGroupSegmentation'])) {
        $mainModel = $models['TargetGroupSegmentation'];
        
        // Content and event data will be added via the enhanced cross-module data section below
        // Add campaign performance data to segments
        if (method_exists($mainModel, 'getCampaignPerformanceBySegment') && !empty($segments)) {
            foreach ($segments as &$segment) {
                if (isset($segment['id'])) {
                    $segment['campaign_performance'] = $mainModel->getCampaignPerformanceBySegment($segment['id']);
                }
            }
        }
        
        // Add event data to segments with proper cross-module integration
        if (!empty($segments)) {
            try {
                global $pdo;
                
                // Try to use EventSeminarManagement functions if available
                if (function_exists('getEventsBySegment')) {
                    foreach ($segments as &$segment) {
                        if (isset($segment['id'])) {
                            $segment['related_events'] = getEventsBySegment($segment['id']);
                        }
                    }
                } else {
                    // Fallback to direct database query
                    $eventQuery = "SELECT id, title, description, event_type, start_date, end_date, location 
                                  FROM events 
                                  WHERE status = 'published' 
                                  ORDER BY start_date ASC 
                                  LIMIT 5";
                    $eventStmt = $pdo->prepare($eventQuery);
                    $eventStmt->execute();
                    $allEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($segments as &$segment) {
                        if (isset($segment['id'])) {
                            $segment['related_events'] = $allEvents;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching events: " . $e->getMessage());
            }
        }
        
        // Add cross-module functions for linking with Event Management and Content Repository
        if (isset($models['TargetGroupSegmentation'])) {
            $segModel = $models['TargetGroupSegmentation'];
            
            // Add function to link content to segments
            if (method_exists($segModel, 'linkContentToSegment')) {
                if (!function_exists('linkContentToSegment')) {
                    function linkContentToSegment($contentId, $segmentId, $scenario = 'general') {
                        global $models;
                        if (isset($models['TargetGroupSegmentation'])) {
                            return $models['TargetGroupSegmentation']->linkContentToSegment($contentId, $segmentId, $scenario);
                        }
                        return false;
                    }
                }
            }
            
            // Also provide access to ContentRepository functions for cross-module integration
            if (isset($models['ContentRepository'])) {
                $contentRepo = $models['ContentRepository'];
                
                if (!function_exists('linkContentToSegmentThroughRepo')) {
                    function linkContentToSegmentThroughRepo($contentId, $segmentId, $scenario = 'general') {
                        global $models;
                        if (isset($models['ContentRepository']) && isset($models['TargetGroupSegmentation'])) {
                            // This function allows linking content to segments through either model
                            $segModel = $models['TargetGroupSegmentation'];
                            if (method_exists($segModel, 'linkContentToSegment')) {
                                return $segModel->linkContentToSegment($contentId, $segmentId, $scenario);
                            }
                        }
                        return false;
                    }
                }
            }
            
            // Add function to link campaigns to segments
            if (method_exists($segModel, 'linkCampaignToSegment')) {
                if (!function_exists('linkCampaignToSegment')) {
                    function linkCampaignToSegment($campaignId, $segmentId) {
                        global $models;
                        if (isset($models['TargetGroupSegmentation'])) {
                            return $models['TargetGroupSegmentation']->linkCampaignToSegment($campaignId, $segmentId);
                        }
                        return false;
                    }
                }
            }
            
            // Add function to link events to segments
            if (method_exists($segModel, 'linkEventToSegment')) {
                if (!function_exists('linkEventToSegment')) {
                    function linkEventToSegment($eventId, $segmentId) {
                        global $models;
                        if (isset($models['TargetGroupSegmentation'])) {
                            return $models['TargetGroupSegmentation']->linkEventToSegment($eventId, $segmentId);
                        }
                        return false;
                    }
                }
            }
            
            // Add function to get events by segment
            if (method_exists($segModel, 'getEventsBySegment')) {
                if (!function_exists('getEventsBySegment')) {
                    function getEventsBySegment($segmentId) {
                        global $models;
                        if (isset($models['TargetGroupSegmentation'])) {
                            return $models['TargetGroupSegmentation']->getEventsBySegment($segmentId);
                        }
                        return [];
                    }
                }
            }
            
            // Add function to get content by segment - only define once
            if (method_exists($segModel, 'getContentBySegment') && !function_exists('getContentBySegment')) {
                function getContentBySegment($segmentId) {
                    global $models;
                    if (isset($models['TargetGroupSegmentation'])) {
                        $segModel = $models['TargetGroupSegmentation'];
                        if (method_exists($segModel, 'getContentBySegment')) {
                            return $segModel->getContentBySegment($segmentId);
                        }
                    }
                    // Fallback: return all content if segment-specific function doesn't exist
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
        
        // Enhance segments with cross-module data
        if (!empty($segments) && isset($models['TargetGroupSegmentation'])) {
            $segModel = $models['TargetGroupSegmentation'];
            
            foreach ($segments as &$segment) {
                if (isset($segment['id'])) {
                    // Get related content for this segment
                    if (method_exists($segModel, 'getContentBySegment')) {
                        $segment['related_content'] = $segModel->getContentBySegment($segment['id']);
                    }
                    
                    // Get related events for this segment
                    if (method_exists($segModel, 'getEventsBySegment')) {
                        $segment['related_events'] = $segModel->getEventsBySegment($segment['id']);
                    }
                    
                    // Get campaign performance for this segment
                    if (method_exists($segModel, 'getCampaignPerformanceBySegment')) {
                        $segment['campaign_performance'] = $segModel->getCampaignPerformanceBySegment($segment['id']);
                    }
                }
            }
        }
    }
    
    // Cross-module integration functions for linking with Event Management
    if (!function_exists('getEventsBySegment')) {
        function getEventsBySegment($segmentId) {
            global $models;
            if (isset($models['TargetGroupSegmentation'])) {
                $segModel = $models['TargetGroupSegmentation'];
                if (method_exists($segModel, 'getEventsBySegment')) {
                    return $segModel->getEventsBySegment($segmentId);
                }
            }
            // Fallback: try to use direct database query
            global $pdo;
            try {
                $stmt = $pdo->prepare(
                    "SELECT e.*, sel.segment_id
                     FROM events e
                     JOIN segment_event_link sel ON e.id = sel.event_id
                     WHERE sel.segment_id = ?
                     ORDER BY e.start_date ASC"
                );
                $stmt->execute([$segmentId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error fetching events by segment: " . $e->getMessage());
                return [];
            }
        }
    }
    
    if (!function_exists('linkEventToSegment')) {
        function linkEventToSegment($eventId, $segmentId) {
            global $models;
            if (isset($models['TargetGroupSegmentation'])) {
                $segModel = $models['TargetGroupSegmentation'];
                if (method_exists($segModel, 'linkEventToSegment')) {
                    return $segModel->linkEventToSegment($eventId, $segmentId);
                }
            }
            // Fallback: try to use direct database query
            global $pdo;
            try {
                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO segment_event_link 
                     (segment_id, event_id, created_at)
                     VALUES (?, ?, NOW())"
                );
                return $stmt->execute([$segmentId, $eventId]);
            } catch (Exception $e) {
                error_log("Error linking event to segment: " . $e->getMessage());
                return false;
            }
        }
    }
    
    if (!function_exists('getSegmentEvents')) {
        function getSegmentEvents($segmentId) {
            return getEventsBySegment($segmentId);
        }
    }
} catch (Exception $e) {
    // If methods don't exist, use fallback data
    $segments = [];
    $analytics = [
        'total_segments' => 0,
        'engagement_stats' => ['average' => 0],
        'total_members' => 0
    ];
    $channels = [];
    error_log("Model Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/group.css">
    <link rel="stylesheet" href="../Styles/userprofile.css">
    <title>Target Group Segmentation</title>
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
                <a href="../home.php" class="nav-link">
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
                <a href="Content-Repository.php" class="nav-link">
                    <i class="fas fa-database"></i>
                    <span class="nav-text">Content Repository</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Target-Group-Segmentation.php" class="nav-link active">
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
                <h2>Target Group Segmentation</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search segments, criteria, tags..." id="searchInput">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 2)) : 'AD'; ?></div>
                        <div>
                            <div style="font-weight: 500;"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrator'; ?></div>
                            <div style="font-size: 13px; color: var(--text-gray);">Segmentation Analyst</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Header -->
<div class="module-header">
    <div>
        <h1 class="module-title">Target Group Segmentation</h1>
        <p class="module-subtitle">Create and manage audience segments for targeted communication</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="btn btn-secondary" onclick="exportSegments()">
            <i class="fas fa-download"></i> Export
        </button>
        <label class="btn btn-secondary" style="cursor: pointer;">
            <i class="fas fa-upload"></i> Import
            <input type="file" accept=".json" style="display: none;" onchange="importSegments(event)">
        </label>
        <button class="btn" onclick="openCreateModal()">
            <i class="fas fa-plus"></i> Create Segment
        </button>
    </div>
</div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active">All Segments</div>
                <div class="filter-item">High Priority</div>
                <div class="filter-item">Demographic</div>
                <div class="filter-item">Behavioral</div>
                <div class="filter-item">Geographic</div>
                <div class="filter-item">Active Campaigns</div>
            </div>

            <div class="module-grid">
                <!-- Segment Library -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Library</div>
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="segment-list">
                        <?php 
                        if (!empty($segments)) {
                            foreach (array_slice($segments, 0, 4) as $segment): 
                        ?>
                        <div class="segment-item <?php echo isset($segment['name']) ? strtolower(str_replace(' ', '-', explode(' ', $segment['name'])[0] ?? '')) : ''; ?>">
                            <div class="segment-name"><?php echo isset($segment['name']) ? htmlspecialchars($segment['name']) : 'Unnamed Segment'; ?></div>
                            <div class="segment-count"><?php echo isset($segment['size_estimate']) ? $segment['size_estimate'] : 0; ?> individuals</div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo isset($segment['engagement_rate']) ? $segment['engagement_rate'] : 0; ?>%; background-color: 
                                    <?php 
                                        if (isset($segment['engagement_rate'])) {
                                            if ($segment['engagement_rate'] > 80) echo 'var(--success);';
                                            elseif ($segment['engagement_rate'] > 60) echo 'var(--accent);';
                                            else echo 'var(--warning);';
                                        } else {
                                            echo 'var(--warning);';
                                        }
                                    ?>"></div>
                            </div>
                            <div class="segment-tags">
                                <span class="segment-tag"><?php echo isset($segment['type']) ? ucfirst($segment['type']) : 'demographic'; ?></span>
                                <span class="segment-tag"><?php echo isset($segment['status']) ? $segment['status'] : 'draft'; ?> Priority</span>
                                <span class="segment-tag"><?php echo isset($segment['engagement_rate']) ? round($segment['engagement_rate']) : 0; ?>% Engaged</span>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        } else {
                            echo '<div class="segment-item">No segments found</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Create New Segment -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Create New Segment</div>
                        <div class="card-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                    </div>
                    <div class="segment-builder">
                        <div class="criteria-group">
                            <h4>Demographic Criteria</h4>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Age Range</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Location (City/District)</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Language Preference</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Education Level</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Occupation</span>
                                </label>
                            </div>
                        </div>
                        <div class="criteria-group">
                            <h4>Behavioral Criteria</h4>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Past Campaign Engagement</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Response History</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Preferred Communication Channels</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Service Usage Patterns</span>
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-magic"></i> Build Segment
                        </button>
                    </div>
                </div>

                <!-- Segment Analytics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Analytics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                    <div class="analytics-dashboard">
                        <div class="metric">
                            <div class="metric-value"><?php echo isset($analytics['total_segments']) ? $analytics['total_segments'] : 0; ?></div>
                            <div class="metric-label">Active Segments</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo isset($analytics['engagement_stats']['average']) ? $analytics['engagement_stats']['average'] : 0; ?>%</div>
                            <div class="metric-label">Avg. Engagement Rate</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php 
                                if (isset($analytics['total_members']) && isset($analytics['total_segments']) && $analytics['total_segments'] > 0) {
                                    echo round($analytics['total_members'] / $analytics['total_segments']);
                                } else {
                                    echo 0;
                                }
                            ?></div>
                            <div class="metric-label">Avg. Size per Segment</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo !empty($channels) ? count($channels) : 0; ?></div>
                            <div class="metric-label">Communication Channels</div>
                        </div>
                    </div>
                    <div class="segment-visualization">
                        <div class="visualization-placeholder">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Segment Performance</h4>
                            <p>Visual analytics dashboard</p>
                        </div>
                    </div>
                </div>

                <!-- Related Content -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Related Content</div>
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="content-list">
                        <?php 
                        if (!empty($segments) && isset($segments[0]['related_content']) && !empty($segments[0]['related_content'])) {
                            foreach (array_slice($segments[0]['related_content'], 0, 4) as $content): 
                        ?> 
                        <div class="content-item">
                            <div class="content-icon">
                                <i class="fas fa-file-<?php echo isset($content['file_type']) ? $content['file_type'] : 'alt'; ?>"></i>
                            </div>
                            <div class="content-details">
                                <div class="content-title"><?php echo isset($content['name']) ? htmlspecialchars($content['name']) : 'Content Item'; ?></div>
                                <div class="content-category">
                                    <span class="badge badge-secondary"><?php echo isset($content['category']) ? htmlspecialchars($content['category']) : 'General'; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        } else {
                            echo '<div class="content-item">No related content found</div>';
                        }
                        ?>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="viewAllContent()">
                        <i class="fas fa-folder-open"></i> View All Content
                    </button>
                </div>
                
                <!-- Communication Channels -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Communication Channels</div>
                        <div class="card-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                    </div>
                    <div class="channel-distribution">
                        <?php 
                        if (!empty($channels)) {
                            foreach (array_slice($channels, 0, 4) as $channel): 
                        ?>
                        <div class="channel-item">
                            <i class="fas <?php 
                                if (isset($channel['name'])) {
                                    if (strpos(strtolower($channel['name']), 'email') !== false) echo 'fa-envelope';
                                    elseif (strpos(strtolower($channel['name']), 'sms') !== false) echo 'fa-mobile-alt';
                                    elseif (strpos(strtolower($channel['name']), 'social') !== false) echo 'fa-hashtag';
                                    else echo 'fa-newspaper';
                                } else {
                                    echo 'fa-newspaper';
                                }
                            ?>"></i>
                            <div>
                                <div style="font-weight: 600;"><?php echo isset($channel['name']) ? htmlspecialchars($channel['name']) : 'Unnamed Channel'; ?></div>
                                <div class="channel-stats">
                                    <?php echo isset($channel['preference_score']) ? $channel['preference_score'] : 0; ?>% prefer • 
                                    <?php echo isset($channel['reach_percentage']) ? $channel['reach_percentage'] : 0; ?>% reach
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        } else {
                            echo '<div class="channel-item">No communication channels found</div>';
                        }
                        ?>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-sliders-h"></i> Optimize Channels
                    </button>
                </div>

                <!-- Related Events -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Related Events</div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="event-list">
                        <?php 
                        if (!empty($segments) && isset($segments[0]['related_events']) && !empty($segments[0]['related_events'])) {
                            foreach (array_slice($segments[0]['related_events'], 0, 4) as $event): 
                        ?> 
                        <div class="event-item">
                            <div class="event-date">
                                <div class="date-day"><?php echo isset($event['start_date']) ? date('d', strtotime($event['start_date'])) : '01'; ?></div>
                                <div class="date-month"><?php echo isset($event['start_date']) ? date('M', strtotime($event['start_date'])) : 'Jan'; ?></div>
                            </div>
                            <div class="event-details">
                                <div class="event-title"><?php echo isset($event['title']) ? htmlspecialchars($event['title']) : 'Event'; ?></div>
                                <div class="event-location"><?php echo isset($event['location']) ? htmlspecialchars($event['location']) : 'TBD'; ?></div>
                                <div class="event-type">
                                    <span class="badge badge-info"><?php echo isset($event['event_type']) ? ucfirst($event['event_type']) : 'General'; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        } else {
                            echo '<div class="event-item">No related events found</div>';
                        }
                        ?>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="viewAllEvents()">
                        <i class="fas fa-calendar-alt"></i> View All Events
                    </button>
                </div>
                
                <!-- A/B Testing Groups -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">A/B Testing Groups</div>
                        <div class="card-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>
                    <div class="testing-groups">
                        <div class="test-group">
                            <div class="group-name">Group A - Control</div>
                            <div class="group-size">5,000 recipients • 68% response</div>
                        </div>
                        <div class="test-group">
                            <div class="group-name">Group B - Variant 1</div>
                            <div class="group-size">5,000 recipients • 72% response</div>
                        </div>
                        <div class="test-group">
                            <div class="group-name">Group C - Variant 2</div>
                            <div class="group-size">5,000 recipients • 81% response</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Test Result</div>
                        <div style="color: var(--success); font-size: 14px;">
                            <i class="fas fa-arrow-up"></i> Variant 2 shows 13% improvement
                        </div>
                    </div>
                </div>

                <!-- Privacy Compliance -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Privacy Compliance</div>
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="compliance-status">
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>GDPR Compliant</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>HIPAA Compliant</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>Data Encrypted</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>Consent Managed</span>
                        </div>
                    </div>
                    <p style="margin-top: 15px; font-size: 14px; color: var(--text-gray);">
                        All data handling follows strict privacy regulations with regular audits
                    </p>
                </div>
            </div>

            <!-- Segment Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">All Segments</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="segment-table">
                    <thead>
                        <tr>
                            <th>Segment Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Engagement Rate</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (!empty($segments)) {
                            foreach ($segments as $segment): 
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo isset($segment['name']) ? htmlspecialchars($segment['name']) : 'Unnamed Segment'; ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo isset($segment['description']) ? htmlspecialchars($segment['description']) : ''; ?></div>
                            </td>
                            <td><span class="segment-type type-<?php echo isset($segment['type']) ? $segment['type'] : 'demographic'; ?>"><?php echo isset($segment['type']) ? ucfirst($segment['type']) : 'Demographic'; ?></span></td>
                            <td><?php echo isset($segment['size_estimate']) ? number_format($segment['size_estimate']) : 0; ?></td>
                            <td>
                               <div><?php echo isset($segment['engagement_rate']) ? $segment['engagement_rate'] : 0; ?>%</div>
                            </td>
                            <td><?php 
                                if (isset($segment['updated_at']) && !empty($segment['updated_at'])) {
                                    echo date('M j, Y', strtotime($segment['updated_at']));
                                } else {
                                    echo 'N/A';
                                }
                            ?></td>
                            <td><span style="color: <?php echo (isset($segment['status']) && $segment['status'] === 'active') ? 'var(--success)' : 'var(--text-gray)'; ?>"><?php echo isset($segment['status']) ? ucfirst($segment['status']) : 'Draft'; ?></span></td>
                            <td>
                                <div class="segment-actions">
                                    <?php if (isset($segment['id'])): ?>
                                    <i class="fas fa-edit" title="Edit" onclick="editSegment(<?php echo $segment['id']; ?>)"></i>
                                    <i class="fas fa-chart-line" title="Analytics" onclick="showAnalytics(<?php echo $segment['id']; ?>)"></i>
                                    <i class="fas fa-bullhorn" title="Target" onclick="targetSegment(<?php echo $segment['id']; ?>)"></i>
                                    <?php else: ?>
                                    <span style="color: var(--text-gray); font-size: 12px;">No actions</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        } else {
                            echo '<tr><td colspan="7" style="text-align: center; padding: 20px;">No segments found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Segment Overlap Analysis -->
            <div class="module-grid" style="margin-top: 30px;">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Overlap Analysis</div>
                        <div class="card-icon">
                            <i class="fas fa-venn-diagram"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--danger); border-radius: 4px; margin-right: 10px;"></div>
                            <span>High-Risk Population</span>
                        </div>
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--warning); border-radius: 4px; margin-right: 10px;"></div>
                            <span>Senior Citizens</span>
                        </div>
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--accent); border-radius: 4px; margin-right: 10px;"></div>
                            <span>Parents with Children</span>
                        </div>
                        <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 5px;">Overlap Insight</div>
                            <div style="font-size: 14px; color: var(--text-gray);">
                                42% of High-Risk individuals are also Senior Citizens
                            </div>
                        </div>
                    </div>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Segmentation</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> By Location
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-birthday-cake"></i> By Age Group
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-history"></i> By Engagement History
                        </button>
                        <button class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-heartbeat"></i> By Health Condition
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../Scripts/mod3.js"></script>

    <script>
        // Function to handle segment creation
        function openCreateModal() {
            alert('Create Segment functionality would open a modal here');
        }

        // Function to edit a segment
        function editSegment(segmentId) {
            alert('Editing segment ID: ' + segmentId);
        }

        // Function to show analytics for a segment
        function showAnalytics(segmentId) {
            alert('Showing analytics for segment ID: ' + segmentId);
        }

        // Function to target a segment
        function targetSegment(segmentId) {
            alert('Targeting segment ID: ' + segmentId + ' for campaign');
        }

        // Function to export segments
        function exportSegments() {
            alert('Exporting segments...');
        }

        // Function to import segments
        function importSegments(event) {
            const file = event.target.files[0];
            if (file) {
                alert('Importing segments from: ' + file.name);
            }
        }

        // Add search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                // In a real implementation, this would filter the segments dynamically
                console.log('Searching for:', searchTerm);
            });
        }

        // Add filter functionality
        document.querySelectorAll('.filter-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.filter-item').forEach(el => {
                    el.classList.remove('active');
                });
                this.classList.add('active');
                
                // In a real implementation, this would filter the segments based on the selected filter
                console.log('Filter selected:', this.textContent);
            });
        });
        
        // Add function to view all events
        function viewAllEvents() {
            window.location.href = 'EventSeminarManagement.php';
        }
        
        // Add function to view all content
        function viewAllContent() {
            window.location.href = 'ContentRepository.php';
        }
    </script>

</body>
</html>