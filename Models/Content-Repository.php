<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database configuration - Update these with your actual credentials
$host = 'localhost';
$dbname = 'lgu4_safety_db'; // Change to your database name
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (empty)
$charset = 'utf8mb4';

// Initialize variables for data
$stats = [
    'by_category' => [
        'Campaign Materials' => 42,
        'Safety Promotions' => 56,
        'Multimedia Library' => 78,
        'Content Templates' => 34
    ],
    'by_status' => [
        'pending' => 12,
        'approved' => 156,
        'rejected' => 5
    ],
    'recent' => [],
    'expiring_soon' => []
];

$categories = [
    ['name' => 'Campaign Materials', 'icon' => 'fa-bullhorn'],
    ['name' => 'Safety Promotions', 'icon' => 'fa-shield-alt'],
    ['name' => 'Multimedia Library', 'icon' => 'fa-photo-video'],
    ['name' => 'Content Templates', 'icon' => 'fa-file-alt']
];

$contentItems = [];
$pdo = null;

// Try to connect to database - if fails, use mock data
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch real data if connected
    // Example queries (you'll need to create these tables first):
    
    // Get content items
    $stmt = $pdo->prepare("SELECT * FROM content_items LIMIT 10");
    $stmt->execute();
    $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent items
    $stmt = $pdo->prepare("SELECT * FROM content_items ORDER BY created_at DESC LIMIT 4");
    $stmt->execute();
    $stats['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expiring items
    $stmt = $pdo->prepare("SELECT * FROM content_items WHERE expiry_date > NOW() AND expiry_date < DATE_ADD(NOW(), INTERVAL 30 DAY) LIMIT 3");
    $stmt->execute();
    $stats['expiring_soon'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If database connection fails, use mock data
    error_log("Database connection failed: " . $e->getMessage());
    
    // Use mock data
    $stats['recent'] = [
        ['id' => 1, 'name' => 'Fire Safety Campaign Poster.jpg', 'created_at' => '2024-01-15', 'size' => '2.4 MB', 'category' => 'Campaign Materials'],
        ['id' => 2, 'name' => 'Emergency Response Guide.pdf', 'created_at' => '2024-01-14', 'size' => '1.2 MB', 'category' => 'Safety Promotions'],
        ['id' => 3, 'name' => 'Safety Training Video.mp4', 'created_at' => '2024-01-13', 'size' => '850 MB', 'category' => 'Multimedia Library'],
        ['id' => 4, 'name' => 'Social Media Template.psd', 'created_at' => '2024-01-12', 'size' => '3.1 MB', 'category' => 'Content Templates']
    ];
    
    $stats['expiring_soon'] = [
        ['id' => 5, 'name' => 'COVID-19 Safety Poster', 'expiry_date' => '2024-02-15', 'category' => 'Safety Promotions'],
        ['id' => 6, 'name' => 'Annual Safety Report 2023', 'expiry_date' => '2024-02-28', 'category' => 'Campaign Materials'],
        ['id' => 7, 'name' => 'Fire Drill Schedule', 'expiry_date' => '2024-03-01', 'category' => 'Safety Promotions']
    ];
    
    // Mock content items
    $contentItems = [
        [
            'id' => 1,
            'name' => 'Fire Safety Campaign Poster.jpg',
            'category' => 'Campaign Materials',
            'size' => '2.4 MB',
            'modified' => 'Jan 15, 2024',
            'status' => 'approved',
            'version' => '2.1',
            'description' => 'Fire safety awareness campaign poster'
        ],
        [
            'id' => 2,
            'name' => 'Emergency Response Guide.pdf',
            'category' => 'Safety Promotions',
            'size' => '1.2 MB',
            'modified' => 'Jan 14, 2024',
            'status' => 'pending',
            'version' => '1.0',
            'description' => 'Emergency response procedures guide'
        ],
        [
            'id' => 3,
            'name' => 'Safety Training Video.mp4',
            'category' => 'Multimedia Library',
            'size' => '850 MB',
            'modified' => 'Jan 13, 2024',
            'status' => 'approved',
            'version' => '3.2',
            'description' => 'Safety training video for employees'
        ],
        [
            'id' => 4,
            'name' => 'Social Media Template.psd',
            'category' => 'Content Templates',
            'size' => '3.1 MB',
            'modified' => 'Jan 12, 2024',
            'status' => 'approved',
            'version' => '1.5',
            'description' => 'Social media post template'
        ],
        [
            'id' => 5,
            'name' => 'First Aid Manual.pdf',
            'category' => 'Safety Promotions',
            'size' => '5.2 MB',
            'modified' => 'Jan 11, 2024',
            'status' => 'approved',
            'version' => '4.0',
            'description' => 'Complete first aid procedures manual'
        ],
        [
            'id' => 6,
            'name' => 'Road Safety Infographic.png',
            'category' => 'Multimedia Library',
            'size' => '1.8 MB',
            'modified' => 'Jan 10, 2024',
            'status' => 'pending',
            'version' => '1.2',
            'description' => 'Road safety statistics infographic'
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/repo.css">
    <title>Content Repository - Public Safety</title>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
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
                    <a href="../Models/Module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../ModelsContent-Repository.php" class="nav-link active">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/Target-Group-Segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Groups</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/EventSeminarManagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Events & Seminars</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/SurveyFeedbackCollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Surveys</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/CampaignAnalyticsReports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/HealthPoliceIntegration.php" class="nav-link">
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
                        <input type="text" placeholder="Search safety content..." id="searchInput">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php 
                            $userInitials = isset($_SESSION['user_name']) 
                                ? strtoupper(substr($_SESSION['user_name'], 0, 2))
                                : 'AD';
                            echo htmlspecialchars($userInitials);
                            ?>
                        </div>
                        <div>
                            <div style="font-weight: 500;">
                                <?php 
                                echo isset($_SESSION['user_name']) 
                                    ? htmlspecialchars($_SESSION['user_name'])
                                    : 'Administrator';
                                ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-gray);">Content Manager</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Content -->
            <div class="module-header">
                <div>
                    <h1 class="module-title">Safety Content Repository</h1>
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
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
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
                <!-- Campaign Materials -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Campaign Materials</div>
                        <div class="card-icon" style="background-color: rgba(52, 152, 219, 0.1);">
                            <i class="fas fa-bullhorn" style="color: #3498db;"></i>
                        </div>
                    </div>
                    <div class="asset-categories">
                        <div class="category-item" onclick="filterByCategory('Campaign Materials')">
                            <i class="fas fa-file-pdf" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;"></i>
                            <span>Public Awareness Campaigns</span>
                            <span class="category-count"><?php echo $stats['by_category']['Campaign Materials']; ?> files</span>
                        </div>
                        <div class="category-item" onclick="filterByCategory('Campaign Materials')">
                            <i class="fas fa-file-powerpoint" style="background-color: rgba(243, 156, 18, 0.1); color: #f39c12;"></i>
                            <span>Presentation Materials</span>
                            <span class="category-count">18 files</span>
                        </div>
                        <div class="category-item" onclick="filterByCategory('Campaign Materials')">
                            <i class="fas fa-newspaper" style="background-color: rgba(155, 89, 182, 0.1); color: #9b59b6;"></i>
                            <span>Press Releases</span>
                            <span class="category-count">9 files</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="viewCategory('Campaign Materials')">
                        <i class="fas fa-eye"></i> View Campaign Materials
                    </button>
                </div>

                <!-- Safety Promotions -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Safety Promotions</div>
                        <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1);">
                            <i class="fas fa-shield-alt" style="color: #2ecc71;"></i>
                        </div>
                    </div>
                    <div class="asset-categories">
                        <div class="category-item" onclick="filterByCategory('Safety Promotions')">
                            <i class="fas fa-exclamation-triangle" style="background-color: rgba(230, 126, 34, 0.1); color: #e67e22;"></i>
                            <span>Emergency Procedures</span>
                            <span class="category-count">15 files</span>
                        </div>
                        <div class="category-item" onclick="filterByCategory('Safety Promotions')">
                            <i class="fas fa-fire" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;"></i>
                            <span>Fire Safety Guides</span>
                            <span class="category-count">12 files</span>
                        </div>
                        <div class="category-item" onclick="filterByCategory('Safety Promotions')">
                            <i class="fas fa-heartbeat" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;"></i>
                            <span>Health & First Aid</span>
                            <span class="category-count">18 files</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="viewCategory('Safety Promotions')">
                        <i class="fas fa-first-aid"></i> View Safety Materials
                    </button>
                </div>

                <!-- Multimedia Library -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Multimedia Library</div>
                        <div class="card-icon" style="background-color: rgba(155, 89, 182, 0.1);">
                            <i class="fas fa-photo-video" style="color: #9b59b6;"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item" onclick="filterByType('image')">
                            <div class="stat-value" style="color: #3498db;">42</div>
                            <div class="stat-label">Images</div>
                        </div>
                        <div class="stat-item" onclick="filterByType('video')">
                            <div class="stat-value" style="color: #9b59b6;">18</div>
                            <div class="stat-label">Videos</div>
                        </div>
                        <div class="stat-item" onclick="filterByType('audio')">
                            <div class="stat-value" style="color: #2ecc71;">8</div>
                            <div class="stat-label">Audio Files</div>
                        </div>
                        <div class="stat-item" onclick="filterByType('infographic')">
                            <div class="stat-value" style="color: #f39c12;">10</div>
                            <div class="stat-label">Infographics</div>
                        </div>
                    </div>
                    <div class="quick-actions-grid" style="margin-top: 15px;">
                        <button class="action-btn" onclick="uploadMedia('image')">
                            <i class="fas fa-image"></i>
                            <span>Upload Image</span>
                        </button>
                        <button class="action-btn" onclick="uploadMedia('video')">
                            <i class="fas fa-video"></i>
                            <span>Upload Video</span>
                        </button>
                    </div>
                </div>

                <!-- Content Templates -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Content Templates</div>
                        <div class="card-icon" style="background-color: rgba(243, 156, 18, 0.1);">
                            <i class="fas fa-file-alt" style="color: #f39c12;"></i>
                        </div>
                    </div>
                    <div class="asset-categories">
                        <div class="category-item" onclick="useTemplate('social_media')">
                            <i class="fab fa-facebook" style="background-color: rgba(59, 89, 152, 0.1); color: #3b5998;"></i>
                            <span>Social Media Templates</span>
                            <span class="category-count">12 templates</span>
                        </div>
                        <div class="category-item" onclick="useTemplate('email')">
                            <i class="fas fa-envelope" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;"></i>
                            <span>Email Templates</span>
                            <span class="category-count">8 templates</span>
                        </div>
                        <div class="category-item" onclick="useTemplate('poster')">
                            <i class="fas fa-print" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;"></i>
                            <span>Poster & Flyer Templates</span>
                            <span class="category-count">14 templates</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="browseTemplates()">
                        <i class="fas fa-search"></i> Browse Templates
                    </button>
                </div>

                <!-- Content Approval Workflow -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Content Approval</div>
                        <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1);">
                            <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
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

                <!-- Quick Actions -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-icon" style="background-color: rgba(155, 89, 182, 0.1);">
                            <i class="fas fa-bolt" style="color: #9b59b6;"></i>
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
                        <button class="action-btn" onclick="createCampaign()">
                            <i class="fas fa-bullhorn"></i>
                            <span>Create Campaign</span>
                        </button>
                        <button class="action-btn" onclick="bulkExport()">
                            <i class="fas fa-download"></i>
                            <span>Bulk Export</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Display Area -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">All Safety Content</div>
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div id="content-display-area">
                    <!-- Content will be loaded via JavaScript -->
                </div>
            </div>
        </main>
    </div>

    <script src="../Scripts/mod2.js"></script>
    
    <script>
        // Sample data for demonstration
        const sampleContent = <?php echo json_encode($contentItems); ?>;
        const categories = <?php echo json_encode($categories); ?>;
        
        // Initialize content display
        document.addEventListener('DOMContentLoaded', function() {
            if (sampleContent && sampleContent.length > 0) {
                updateContentDisplay(sampleContent);
            } else {
                showNoContentMessage();
            }
            
            // Add event listeners for filters
            document.getElementById('filterSearch').addEventListener('input', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            
            // Add search functionality
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        });

        function showNoContentMessage() {
            const displayArea = document.getElementById('content-display-area');
            displayArea.innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p>No content uploaded yet</p>
                    <p style="font-size: 14px;">Upload safety content using the buttons above</p>
                </div>
            `;
        }

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

            let contentHTML = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">';
            
            items.forEach(item => {
                const fileType = getFileType(item.name);
                const fileIcon = getFileIcon(fileType);
                const statusClass = `status-${item.status}`;
                const statusText = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                const statusColor = getStatusColor(item.status);
                
                contentHTML += `
                    <div class="content-card" style="
                        background: var(--dark-gray);
                        border-radius: 8px;
                        padding: 15px;
                        transition: all 0.3s ease;
                        border: 1px solid transparent;
                        cursor: pointer;
                    " onclick="previewContent(${item.id})">
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
                                ${fileIcon}
                            </div>
                            <div style="flex-grow: 1; overflow: hidden;">
                                <div style="font-weight: 600; font-size: 15px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                     title="${escapeHtml(item.name)}">
                                    ${escapeHtml(item.name)}
                                </div>
                                <div style="font-size: 12px; color: var(--text-gray);">
                                    ${item.size} • ${item.category}
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <div>
                                <span style="
                                    padding: 3px 10px;
                                    border-radius: 15px;
                                    font-size: 11px;
                                    font-weight: 600;
                                    text-transform: uppercase;
                                    background: ${statusColor};
                                    color: white;
                                ">
                                    ${statusText}
                                </span>
                            </div>
                            <div style="font-size: 12px; color: var(--text-gray);">
                                v${item.version || '1.0'}
                            </div>
                        </div>
                        
                        ${item.description ? `
                        <div style="margin-top: 10px; font-size: 13px; color: var(--text-gray); line-height: 1.4; max-height: 40px; overflow: hidden;">
                            ${escapeHtml(item.description)}
                        </div>
                        ` : ''}
                        
                        <div style="display: flex; gap: 8px; margin-top: 15px;">
                            <button onclick="event.stopPropagation(); previewContent(${item.id})" 
                                    style="flex: 1; background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button onclick="event.stopPropagation(); downloadContent(${item.id})" 
                                    style="flex: 1; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                                <i class="fas fa-download"></i> Download
                            </button>
                        </div>
                        
                        ${item.status === 'pending' ? `
                        <div style="display: flex; gap: 5px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <button onclick="event.stopPropagation(); approveContent(${item.id})" 
                                    style="flex: 1; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="event.stopPropagation(); rejectContent(${item.id})" 
                                    style="flex: 1; background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                        ` : ''}
                    </div>
                `;
            });
            
            contentHTML += '</div>';
            displayArea.innerHTML = contentHTML;
            
            // Add hover effects
            const cards = displayArea.querySelectorAll('.content-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.3)';
                    this.style.borderColor = 'var(--accent)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                    this.style.borderColor = 'transparent';
                });
            });
        }

        function getFileType(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(ext)) return 'image';
            if (['mp4', 'avi', 'mov', 'wmv', 'mkv'].includes(ext)) return 'video';
            if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) return 'audio';
            if (['pdf'].includes(ext)) return 'pdf';
            if (['doc', 'docx'].includes(ext)) return 'word';
            if (['ppt', 'pptx'].includes(ext)) return 'powerpoint';
            if (['xls', 'xlsx'].includes(ext)) return 'excel';
            if (['psd', 'ai', 'eps'].includes(ext)) return 'design';
            return 'file';
        }

        function getFileIcon(fileType) {
            switch(fileType) {
                case 'image': return '<i class="fas fa-image"></i>';
                case 'video': return '<i class="fas fa-video"></i>';
                case 'audio': return '<i class="fas fa-volume-up"></i>';
                case 'pdf': return '<i class="fas fa-file-pdf"></i>';
                case 'word': return '<i class="fas fa-file-word"></i>';
                case 'powerpoint': return '<i class="fas fa-file-powerpoint"></i>';
                case 'excel': return '<i class="fas fa-file-excel"></i>';
                case 'design': return '<i class="fas fa-paint-brush"></i>';
                default: return '<i class="fas fa-file-alt"></i>';
            }
        }

        function getStatusColor(status) {
            switch(status) {
                case 'approved': return '#27ae60';
                case 'pending': return '#f39c12';
                case 'rejected': return '#e74c3c';
                default: return '#95a5a6';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function filterByCategory(category) {
            document.getElementById('categoryFilter').value = category;
            applyFilters();
        }

        function filterByType(type) {
            let searchTerm = '';
            switch(type) {
                case 'image': searchTerm = 'jpg png gif jpeg bmp'; break;
                case 'video': searchTerm = 'mp4 mov avi mkv'; break;
                case 'audio': searchTerm = 'mp3 wav ogg m4a'; break;
                case 'infographic': searchTerm = 'infographic'; break;
            }
            document.getElementById('filterSearch').value = searchTerm;
            applyFilters();
        }

        function viewCategory(category) {
            filterByCategory(category);
            document.getElementById('filterSearch').value = '';
            applyFilters();
        }

        function uploadMedia(type) {
            alert(`Opening ${type} upload dialog...\n\nRemember to include safety information and proper descriptions.`);
        }

        function useTemplate(templateType) {
            alert(`Using ${templateType.replace('_', ' ')} template.\n\nCustomize with your safety content and messages.`);
        }

        function browseTemplates() {
            filterByCategory('Content Templates');
            document.getElementById('filterSearch').value = 'template';
            applyFilters();
        }

        function createCampaign() {
            alert('Opening campaign creation wizard...\n\nCreate new safety awareness campaigns with templates.');
        }

        function applyFilters() {
            const searchTerm = document.getElementById('filterSearch').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            let filtered = sampleContent.filter(item => {
                const matchesSearch = !searchTerm || 
                    item.name.toLowerCase().includes(searchTerm) ||
                    (item.description && item.description.toLowerCase().includes(searchTerm));
                
                const matchesCategory = !category || item.category === category;
                const matchesStatus = !status || item.status === status;
                
                return matchesSearch && matchesCategory && matchesStatus;
            });
            
            updateContentDisplay(filtered);
        }

        // Content action functions
        function previewContent(id) {
            const item = sampleContent.find(i => i.id === id);
            if (item) {
                alert(`Previewing: ${item.name}\n\nCategory: ${item.category}\nStatus: ${item.status}\nSize: ${item.size}\n\nDescription: ${item.description || 'No description available'}`);
            }
        }

        function downloadContent(id) {
            const item = sampleContent.find(i => i.id === id);
            if (item) {
                alert(`Downloading: ${item.name}\n\nThis is a demo. In a real application, the file would start downloading.`);
            }
        }

        function approveContent(id) {
            if (confirm('Are you sure you want to approve this content?')) {
                alert('Content approved successfully!');
                // In real app, this would update the database
            }
        }

        function rejectContent(id) {
            if (confirm('Are you sure you want to reject this content?')) {
                alert('Content rejected and moved to revision queue.');
                // In real app, this would update the database
            }
        }

        // Functions from mod2.js
        function uploadNewContent() {
            alert('Opening content upload dialog...\n\nUpload safety materials, campaign content, or multimedia files.');
        }

        function advancedSearch() {
            alert('Opening advanced search panel...\n\nSearch by date range, file type, author, or custom criteria.');
        }

        function openReviewQueue() {
            const pending = sampleContent.filter(item => item.status === 'pending');
            if (pending.length > 0) {
                alert(`Opening review queue...\n\nYou have ${pending.length} items pending approval.`);
            } else {
                alert('No items pending review. All content is approved!');
            }
        }
    </script>
</body>
</html>