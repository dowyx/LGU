<?php
// Start session for user management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Safety Manager';

// Fetch dashboard data from database
try {
    // Active incidents count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM incidents WHERE status = 'active'");
    $stmt->execute();
    $active_incidents = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active campaigns count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'");
    $stmt->execute();
    $active_campaigns = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Average response time (in minutes)
    $stmt = $pdo->prepare("SELECT AVG(response_time) as avg_time FROM incident_responses WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $avg_response_time = $result && $result['avg_time'] !== null ? round($result['avg_time'] / 60, 1) : 8.2;
    
    // Public satisfaction percentage
    $stmt = $pdo->prepare("SELECT AVG(satisfaction_score) as avg_score FROM feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $public_satisfaction = $result && $result['avg_score'] !== null ? round($result['avg_score'], 0) : 92;
    
    // Incident types data
    $stmt = $pdo->prepare("
        SELECT type, COUNT(*) as count,
               (SELECT COUNT(*) FROM incidents WHERE type = i.type AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) -
               (SELECT COUNT(*) FROM incidents WHERE type = i.type AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)) as trend
        FROM incidents i 
        WHERE status = 'active' 
        GROUP BY type
    ");
    $stmt->execute();
    $incident_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Campaigns data
    $stmt = $pdo->prepare("
        SELECT id, name, status, start_date, end_date, 
               target_reach, actual_reach, completion_percentage
        FROM campaigns 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Set default values if database fails
    $active_incidents = 42;
    $active_campaigns = 18;
    $avg_response_time = 8.2;
    $public_satisfaction = 92;
    $incident_types = [];
    $campaigns = [];
}

// Helper function to get trend icon
function getTrendIcon($trend) {
    if ($trend > 0) return '↑';
    if ($trend < 0) return '↓';
    return '↔';
}

// Helper function to get trend class
function getTrendClass($trend) {
    if ($trend > 0) return 'up';
    if ($trend < 0) return 'down';
    return 'neutral';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Styles/home.css">
    <link rel="stylesheet" href="Styles/chatbot.css">
    <link rel="stylesheet" href="Styles/userprofile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <title>Public Safety Campaign Management</title>

    <style>
    /* Hide all Export Report buttons except in modal */
    button:has(i.fa-download),
    .action-btn-small:last-child {
        display: none !important;
    }
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
                    <a href="home.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/Module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning & Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/Content-Repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/Target-Group-Segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/EventSeminarManagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Event & Seminar Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/SurveyFeedbackCollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Survey & Feedback Collection</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/CampaignAnalyticsReports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Campaign Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Models/HealthPoliceIntegration.php" class="nav-link">
                        <i class="fas fa-link"></i>
                        <span class="nav-text">Community Integration</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h2>Main Dashboard</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search incidents, campaigns, reports...">
                    </div>

                    <!-- Notifications Button -->
                    <div class="notifications-dropdown">
                        <button class="notifications-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <div class="notifications-menu" id="notificationsMenu">
                            <div class="notifications-header">
                                <h3>Notifications</h3>
                                <button class="mark-all-read" onclick="markAllAsRead()">Mark all read</button>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <div class="notification-icon alert">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">New incident reported in Downtown District</div>
                                        <div class="notification-time">10 minutes ago</div>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <div class="notification-icon success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">Campaign approval completed</div>
                                        <div class="notification-time">1 hour ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Chatbot Icon -->
                    <div class="ai-chatbot-icon">
                        <a href="#" class="chatbot-link" title="AI Assistant" onclick="chatbot.open(); return false;">
                            <i class="fas fa-robot"></i>
                            <span class="chatbot-text">AI Assistant</span>
                        </a>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user_name); ?></div>
                            <div style="font-size: 13px; color: var(--text-gray);"><?php echo htmlspecialchars($user_role); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Widgets -->
            <div class="dashboard-widgets">
                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-title">Active Incidents</div>
                        <div class="widget-icon icon-incidents">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="widget-value"><?php echo $active_incidents; ?></div>
                    <div class="widget-change">
                        <span class="positive"><i class="fas fa-arrow-down"></i> 12% from last week</span>
                    </div>
                </div>

                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-title">Active Campaigns</div>
                        <div class="widget-icon icon-campaigns">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                    </div>
                    <div class="widget-value"><?php echo $active_campaigns; ?></div>
                    <div class="widget-change">
                        <span class="positive"><i class="fas fa-arrow-up"></i> 3 new this week</span>
                    </div>
                </div>

                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-title">Avg Response Time</div>
                        <div class="widget-icon icon-response">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="widget-value"><?php echo $avg_response_time; ?>m</div>
                    <div class="widget-change">
                        <span class="positive"><i class="fas fa-arrow-down"></i> 1.5m improvement</span>
                    </div>
                </div>

                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-title">Public Satisfaction</div>
                        <div class="widget-icon icon-analytics">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="widget-value"><?php echo $public_satisfaction; ?>%</div>
                    <div class="widget-change">
                        <span class="positive"><i class="fas fa-arrow-up"></i> 4% from last month</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section - INTERACTIVE VERSION -->
            <div class="charts-section">
                <!-- Interactive Incidents Matrix -->
                <div class="chart-container interactive-chart">
                    <div class="chart-header">
                        <div class="chart-title">Incidents by Type</div>
                        <div class="chart-legend">
                            <div class="legend-item active" data-type="all">
                                <div class="legend-color" style="background-color: #4A90E2;"></div>
                                <span>All Types</span>
                                <span class="badge"><?php echo array_sum(array_column($incident_types, 'count')) + 100; ?></span>
                            </div>
                        </div>
                        <div class="chart-filters">
                            <select id="timeFilter">
                                <option value="today">Today</option>
                                <option value="week" selected>This Week</option>
                                <option value="month">This Month</option>
                                <option value="quarter">This Quarter</option>
                            </select>
                        </div>
                    </div>

                    <div class="interactive-matrix">
                        <div class="incident-types">
                            <?php
                            $incident_icons = [
                                'emergency' => 'fa-ambulance',
                                'health' => 'fa-heartbeat',
                                'safety' => 'fa-shield-alt',
                                'fire' => 'fa-fire',
                                'police' => 'fa-badge'
                            ];
                            
                            $default_incidents = [
                                ['type' => 'emergency', 'count' => 25, 'trend' => 12],
                                ['type' => 'health', 'count' => 18, 'trend' => -5],
                                ['type' => 'safety', 'count' => 32, 'trend' => 8],
                                ['type' => 'fire', 'count' => 12, 'trend' => 0],
                                ['type' => 'police', 'count' => 13, 'trend' => 15]
                            ];
                            
                            $display_incidents = !empty($incident_types) ? $incident_types : $default_incidents;
                            
                            foreach ($display_incidents as $incident):
                            ?>
                            <div class="incident-type-card <?php echo htmlspecialchars($incident['type']); ?>" data-type="<?php echo htmlspecialchars($incident['type']); ?>" data-count="<?php echo $incident['count']; ?>">
                                <div class="incident-icon">
                                    <i class="fas <?php echo $incident_icons[$incident['type']] ?? 'fa-exclamation-triangle'; ?>"></i>
                                </div>
                                <div class="incident-info">
                                    <h4><?php echo ucfirst(htmlspecialchars($incident['type'])); ?></h4>
                                    <div class="incident-count"><?php echo $incident['count']; ?></div>
                                    <div class="incident-trend <?php echo getTrendClass($incident['trend'] ?? 0); ?>"><?php echo getTrendIcon($incident['trend'] ?? 0); ?> <?php echo abs($incident['trend'] ?? 0); ?>%</div>
                                </div>
                                <div class="incident-actions">
                                    <button class="mini-action-btn view-details" onclick="viewIncidentDetails('<?php echo htmlspecialchars($incident['type']); ?>')">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="mini-action-btn assign-team" onclick="assignTeam('<?php echo htmlspecialchars($incident['type']); ?>')">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Heat Map Visualization - FIXED -->
                        <div class="heat-map-container">
                            <div class="heat-map-title">
                                <span>Incident Heat Map</span>
                                <span class="heat-map-period">This Week</span>
                            </div>
                            <div class="heat-map-grid" id="heatMapGrid">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <div class="heat-map-legend">
                                <div class="legend-item"><span class="legend-color low"></span> Low (1-5)</div>
                                <div class="legend-item"><span class="legend-color medium"></span> Medium (6-15)</div>
                                <div class="legend-item"><span class="legend-color high"></span> High (16+)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="stat-card">
                            <i class="fas fa-clock"></i>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $avg_response_time; ?>m</div>
                                <div class="stat-label">Avg Response</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-check-circle"></i>
                            <div class="stat-info">
                                <div class="stat-value">94%</div>
                                <div class="stat-label">Resolved</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <div class="stat-info">
                                <div class="stat-value">42</div>
                                <div class="stat-label">Active Teams</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="stat-info">
                                <div class="stat-value">18</div>
                                <div class="stat-label">Zones Covered</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Campaign Dashboard -->
                <div class="chart-container interactive-chart">
                    <div class="chart-header">
                        <div class="chart-title">Campaign Performance</div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4CAF50;"></div>
                                <span>Active Campaigns</span>
                                <span class="badge"><?php echo $active_campaigns; ?></span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #FFA726;"></div>
                                <span>Planned</span>
                                <span class="badge">5</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4A90E2;"></div>
                                <span>Completed</span>
                                <span class="badge">5</span>
                            </div>
                        </div>
                        <div class="chart-actions">
                            <button class="action-btn-small" onclick="addNewCampaign()">
                                <i class="fas fa-plus"></i> New Campaign
                            </button>
                            <button class="action-btn-small" onclick="openExportModal()" style="margin-left: 10px;">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                        </div>
                    </div>

                    <!-- Campaign Cards Grid -->
                    <div class="campaign-grid">
                        <?php
                        $default_campaigns = [
                            ['id' => 1, 'name' => 'Summer Safety', 'status' => 'active', 'progress' => 75, 'reach' => 7500, 'engagement' => 92, 'icon' => 'fa-sun'],
                            ['id' => 2, 'name' => 'School Zone Safety', 'status' => 'active', 'progress' => 60, 'reach' => 5200, 'engagement' => 88, 'icon' => 'fa-school'],
                            ['id' => 3, 'name' => 'Home Safety Week', 'status' => 'planned', 'progress' => 10, 'reach' => 10000, 'engagement' => 0, 'icon' => 'fa-home'],
                            ['id' => 4, 'name' => 'Road Safety Month', 'status' => 'completed', 'progress' => 100, 'reach' => 12500, 'engagement' => 95, 'icon' => 'fa-car']
                        ];
                        
                        $display_campaigns = !empty($campaigns) ? $campaigns : $default_campaigns;
                        
                        foreach ($display_campaigns as $campaign):
                            $campaign_data = is_array($campaign) ? $campaign : [
                                'id' => $campaign['id'] ?? 1,
                                'name' => $campaign['name'] ?? 'Campaign',
                                'status' => $campaign['status'] ?? 'active',
                                'completion_percentage' => $campaign['completion_percentage'] ?? 75,
                                'actual_reach' => $campaign['actual_reach'] ?? 5000,
                                'target_reach' => $campaign['target_reach'] ?? 10000
                            ];
                        ?>
                        <div class="campaign-card <?php echo htmlspecialchars($campaign_data['status']); ?>" data-id="<?php echo $campaign_data['id']; ?>">
                            <div class="campaign-status <?php echo htmlspecialchars($campaign_data['status']); ?>"><?php echo strtoupper(htmlspecialchars($campaign_data['status'])); ?></div>
                            <div class="campaign-icon">
                                <i class="fas <?php echo $default_campaigns[array_search($campaign_data['id'], array_column($default_campaigns, 'id'))]['icon'] ?? 'fa-bullhorn'; ?>"></i>
                            </div>
                            <div class="campaign-info">
                                <h4><?php echo htmlspecialchars($campaign_data['name']); ?></h4>
                                <div class="campaign-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $campaign_data['completion_percentage']; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo $campaign_data['completion_percentage']; ?>% Complete</span>
                                </div>
                                <div class="campaign-stats">
                                    <div class="stat">
                                        <i class="fas fa-eye"></i>
                                        <span><?php echo number_format($campaign_data['actual_reach']); ?></span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?php echo $campaign_data['status'] === 'planned' ? 'N/A' : '88%'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="campaign-actions">
                                <button class="campaign-action-btn" onclick="viewCampaign(<?php echo $campaign_data['id']; ?>)" title="View Details">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button class="campaign-action-btn" onclick="editCampaign(<?php echo $campaign_data['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="performance-metrics">
                        <div class="metric-row">
                            <div class="metric">
                                <div class="metric-label">Total Reach</div>
                                <div class="metric-value">38,200</div>
                                <div class="metric-change positive">↑ 12%</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Engagement Rate</div>
                                <div class="metric-value">4.8%</div>
                                <div class="metric-change positive">↑ 0.5%</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Avg Completion</div>
                                <div class="metric-value">78%</div>
                                <div class="metric-change neutral">↔ 0%</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Cost per Reach</div>
                                <div class="metric-value"> ₱ 0.42</div>
                                <div class="metric-change negative">↑  ₱ 0.02</div>
                            </div>
                        </div>

                        <!-- Timeline Visualization -->
                        <div class="campaign-timeline">
                            <div class="timeline-header">
                                <h4>Upcoming Campaigns</h4>
                                <button class="view-all-btn" onclick="viewAllCampaigns()">View All</button>
                            </div>
                            <div class="timeline">
                                <div class="timeline-item upcoming">
                                    <div class="timeline-date">Oct 15</div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Home Safety Week</div>
                                        <div class="timeline-desc">Community workshops</div>
                                    </div>
                                    <div class="timeline-actions">
                                        <button class="timeline-action-btn" onclick="remindMe('home-safety')">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="timeline-item upcoming">
                                    <div class="timeline-date">Oct 22</div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Cybersecurity Month</div>
                                        <div class="timeline-desc">Online safety training</div>
                                    </div>
                                    <div class="timeline-actions">
                                        <button class="timeline-action-btn" onclick="remindMe('cyber')">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="timeline-item current">
                                    <div class="timeline-date">Now</div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Summer Safety</div>
                                        <div class="timeline-desc">Beach & pool safety</div>
                                    </div>
                                    <div class="timeline-actions">
                                        <button class="timeline-action-btn" onclick="viewLiveStats('summer')">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-container">
                <div class="activity-title">Recent Activity</div>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon icon-alert">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">New incident reported: Fire emergency at Downtown District</div>
                            <div class="activity-time">5 minutes ago</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon icon-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">Campaign "Summer Safety" reached 75% completion</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon icon-info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">System maintenance scheduled for tonight 11 PM</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </li>
                </ul>
            </div>
        </main>
    </div>

    <!-- Enhanced Chatbot Modal -->
    <div class="chatbot-modal" id="chatbotModal">
        <div class="chatbot-header">
            <div class="chatbot-header-title">
                <i class="fas fa-robot"></i>
                <span>Public Safety AI Assistant</span>
            </div>
            <button class="chatbot-close" id="closeChatbotBtn" onclick="chatbot.close(); return false;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="chatbot-messages" id="chatMessages">
            <div class="message message-ai">
                👋 Hello! I'm your Public Safety AI Assistant. I can help you with:
                • Incident analysis
                • Campaign planning
                • Report generation
                • Safety recommendations
                • Emergency procedures
                How can I assist you today?
                <div class="message-time">Just now</div>
            </div>
        </div>

        <div class="chatbot-input-area">
            <div class="quick-questions">
                <button class="quick-question-btn" onclick="chatbot.askQuickQuestion('Show active incidents')">Active Incidents</button>
                <button class="quick-question-btn" onclick="chatbot.askQuickQuestion('Generate safety report')">Generate Report</button>
                <button class="quick-question-btn" onclick="chatbot.askQuickQuestion('Emergency procedures')">Emergency Guide</button>
                <button class="quick-question-btn" onclick="chatbot.askQuickQuestion('Campaign suggestions')">Campaign Ideas</button>
            </div>

            <div class="chatbot-input-container">
                <input type="text"
                       class="chatbot-input"
                       id="chatInput"
                       placeholder="Ask about incidents, campaigns, or safety procedures..."
                       onkeypress="chatbot.handleKeyPress(event)">
                <button class="chatbot-send-btn" onclick="chatbot.sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    // Critical functions needed immediately
    // Chatbot functions are now handled by chatbot.js class
    
    // Other critical functions
    function markAllAsRead() {
        const notifications = document.querySelectorAll('.notification-item.unread');
        notifications.forEach(notification => {
            notification.classList.remove('unread');
        });
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'notification notification-success';
        notification.innerHTML = '<div class="notification-content"><i class="fas fa-check-circle"></i><span>All notifications marked as read</span></div>';
        document.body.appendChild(notification);
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
    
    // Load remaining scripts with error handling
    function loadScript(src, callback) {
        const script = document.createElement('script');
        script.src = src;
        script.onload = callback;
        script.onerror = function() {
            console.error('Failed to load script: ' + src);
            if (callback) callback();
        };
        document.head.appendChild(script);
    }
    
    // Load utils.js first, then chatbot.js, then home.js
    loadScript('Scripts/utils.js', function() {
        loadScript('Scripts/chatbot.js', function() {
            loadScript('Scripts/home.js', function() {
                console.log('All scripts loaded successfully');
            });
        });
    });
    </script>

</body>
</html>