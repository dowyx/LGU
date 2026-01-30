<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection (adjust path as needed)
try {
    require_once '../config/database.php';
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize content data arrays with the requested features
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
    'recent' => [
        ['name' => 'Fire Safety Campaign Poster.jpg', 'created_at' => '2024-01-15', 'size' => '2.4 MB', 'category' => 'Campaign Materials'],
        ['name' => 'Emergency Response Guide.pdf', 'created_at' => '2024-01-14', 'size' => '1.2 MB', 'category' => 'Safety Promotions'],
        ['name' => 'Safety Training Video.mp4', 'created_at' => '2024-01-13', 'size' => '850 MB', 'category' => 'Multimedia Library'],
        ['name' => 'Social Media Template.psd', 'created_at' => '2024-01-12', 'size' => '3.1 MB', 'category' => 'Content Templates']
    ],
    'expiring_soon' => [
        ['name' => 'COVID-19 Safety Poster', 'expiry_date' => '2024-02-15', 'category' => 'Safety Promotions'],
        ['name' => 'Annual Safety Report 2023', 'expiry_date' => '2024-02-28', 'category' => 'Campaign Materials'],
        ['name' => 'Fire Drill Schedule', 'expiry_date' => '2024-03-01', 'category' => 'Safety Promotions']
    ]
];

// The requested categories
$categories = [
    ['name' => 'Campaign Materials', 'icon' => 'fa-bullhorn'],
    ['name' => 'Safety Promotions', 'icon' => 'fa-shield-alt'],
    ['name' => 'Multimedia Library', 'icon' => 'fa-photo-video'],
    ['name' => 'Content Templates', 'icon' => 'fa-file-alt']
];

// Sample content items
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
    ]
];
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
                    <a href="../Modules/Module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Content-Repository.php" class="nav-link active">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Modules/Target-Group-Segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Groups</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Modules/EventSeminarManagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Events & Seminars</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Modules/SurveyFeedbackCollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Surveys</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Modules/CampaignAnalyticsReports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Modules/HealthPoliceIntegration.php" class="nav-link">
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
                    <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>No content uploaded yet</p>
                        <p style="font-size: 14px;">Upload safety content using the buttons above</p>
                    </div>
                </div>
            </div>

            <!-- Additional Features Section -->
            <div class="module-grid" style="margin-top: 30px;">
                <!-- Version Control -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Version Control</div>
                        <div class="card-icon" style="background-color: rgba(52, 152, 219, 0.1);">
                            <i class="fas fa-code-branch" style="color: #3498db;"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Active Versions</span>
                            <span>156</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Archived Versions</span>
                            <span>423</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Last Version Update</span>
                            <span>Today</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;" onclick="viewVersionHistory()">
                        <i class="fas fa-history"></i> View Version History
                    </button>
                </div>

                <!-- Usage Analytics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Usage Analytics</div>
                        <div class="card-icon" style="background-color: rgba(46, 204, 113, 0.1);">
                            <i class="fas fa-chart-line" style="color: #2ecc71;"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">1,247</div>
                            <div class="stat-label">Total Downloads</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">85%</div>
                            <div class="stat-label">Reuse Rate</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">42</div>
                            <div class="stat-label">Downloads Today</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">18</div>
                            <div class="stat-label">Active Campaigns</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../Scripts/mod2.js"></script>
    
    <script>
        // Initialize content display with sample data
        document.addEventListener('DOMContentLoaded', function() {
            const initialContent = <?php echo json_encode($contentItems); ?>;
            if (initialContent && initialContent.length > 0) {
                updateContentDisplay(initialContent);
            }
            
            // Add event listeners for filters
            document.getElementById('filterSearch').addEventListener('input', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
        });

        // Function to update content display
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
                const fileType = getFileType(item.name);
                const fileIcon = getFileIcon(fileType);
                const statusClass = `status-${item.status}`;
                const statusText = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                
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
                                ${fileIcon}
                            </div>
                            <div style="flex-grow: 1; overflow: hidden;">
                                <div style="font-weight: 600; font-size: 15px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                     title="${escapeHtml(item.name)}">
                                    ${escapeHtml(item.name)}
                                </div>
                                <div style="font-size: 12px; color: var(--text-gray);">
                                    v${item.version || '1.0'} • ${item.size} • ${item.category}
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 13px;">
                            <div>
                                <div style="color: var(--text-gray);">Category</div>
                                <div style="color: white; font-weight: 500;">${escapeHtml(item.category)}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-gray);">Status</div>
                                <div>
                                    <span class="status-badge ${statusClass}" style="
                                        padding: 3px 10px;
                                        border-radius: 15px;
                                        font-size: 11px;
                                        font-weight: 600;
                                        text-transform: uppercase;
                                        background: ${statusClass === 'status-approved' ? '#27ae60' : statusClass === 'status-pending' ? '#f39c12' : '#e74c3c'};
                                        color: white;
                                    ">
                                        ${statusText}
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
                                <i class="fas fa-download"></i> Download
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

        // Helper function to get file type
        function getFileType(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) return 'image';
            if (['mp4', 'avi', 'mov', 'wmv'].includes(ext)) return 'video';
            if (['pdf'].includes(ext)) return 'pdf';
            if (['doc', 'docx'].includes(ext)) return 'word';
            if (['ppt', 'pptx'].includes(ext)) return 'powerpoint';
            if (['psd', 'ai'].includes(ext)) return 'design';
            return 'file';
        }

        // Helper function to get file icon
        function getFileIcon(fileType) {
            switch(fileType) {
                case 'image': return '<i class="fas fa-image"></i>';
                case 'video': return '<i class="fas fa-video"></i>';
                case 'pdf': return '<i class="fas fa-file-pdf"></i>';
                case 'word': return '<i class="fas fa-file-word"></i>';
                case 'powerpoint': return '<i class="fas fa-file-powerpoint"></i>';
                case 'design': return '<i class="fas fa-paint-brush"></i>';
                default: return '<i class="fas fa-file-alt"></i>';
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Function to filter by category
        function filterByCategory(category) {
            document.getElementById('categoryFilter').value = category;
            applyFilters();
        }

        // Function to filter by file type
        function filterByType(type) {
            let searchTerm = '';
            switch(type) {
                case 'image': searchTerm = 'jpg png gif jpeg'; break;
                case 'video': searchTerm = 'mp4 mov avi'; break;
                case 'audio': searchTerm = 'mp3 wav'; break;
                case 'infographic': searchTerm = 'infographic'; break;
            }
            document.getElementById('filterSearch').value = searchTerm;
            applyFilters();
        }

        // Function to view all items in a category
        function viewCategory(category) {
            filterByCategory(category);
            document.getElementById('filterSearch').value = '';
            applyFilters();
            
            // Scroll to content display area
            document.getElementById('content-display-area').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Function to upload media
        function uploadMedia(type) {
            alert(`Opening ${type} upload dialog...\n\nSupported formats: ${type === 'image' ? 'JPG, PNG, GIF, BMP' : 'MP4, AVI, MOV, WMV'}`);
        }

        // Function to use template
        function useTemplate(templateType) {
            let templateName = '';
            switch(templateType) {
                case 'social_media': templateName = 'Social Media Template'; break;
                case 'email': templateName = 'Email Template'; break;
                case 'poster': templateName = 'Poster Template'; break;
            }
            
            alert(`Using ${templateName}\n\nThis template will be copied to your workspace. You can now customize it with your safety content.`);
        }

        // Function to browse templates
        function browseTemplates() {
            filterByCategory('Content Templates');
            document.getElementById('filterSearch').value = 'template';
            applyFilters();
        }

        // Function to create campaign
        function createCampaign() {
            alert('Opening campaign creation wizard...\n\nYou can create new safety awareness campaigns with this tool.');
        }

        // Function to view version history
        function viewVersionHistory() {
            alert('Opening version history...\n\nView and restore previous versions of safety content.');
        }

        // Apply filters function
        function applyFilters() {
            const searchTerm = document.getElementById('filterSearch').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            const initialContent = <?php echo json_encode($contentItems); ?>;
            let filtered = initialContent.filter(item => {
                const matchesSearch = !searchTerm || 
                    item.name.toLowerCase().includes(searchTerm) ||
                    (item.description && item.description.toLowerCase().includes(searchTerm));
                
                const matchesCategory = !category || item.category === category;
                const matchesStatus = !status || item.status === status;
                
                return matchesSearch && matchesCategory && matchesStatus;
            });
            
            updateContentDisplay(filtered);
        }
    </script>
</body>
</html>