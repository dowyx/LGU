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
               COALESCE(
                   (SELECT COUNT(*) FROM incidents WHERE type = i.type AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)), 
                   0
               ) - 
               COALESCE(
                   (SELECT COUNT(*) FROM incidents WHERE type = i.type AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)), 
                   0
               ) as trend
        FROM incidents i 
        WHERE status = 'active' 
        GROUP BY type
        ORDER BY count DESC
    ");
    $stmt->execute();
    $incident_types_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Campaigns data
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            status, 
            start_date, 
            end_date, 
            COALESCE(target_reach, 0) as target_reach,
            COALESCE(actual_reach, 0) as actual_reach,
            COALESCE(completion_percentage, 0) as completion_percentage,
            COALESCE(engagement_rate, 0) as engagement_rate
        FROM campaigns 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $campaigns_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log error but don't show to user
    error_log("Database error: " . $e->getMessage());
    
    // Set default values if database fails
    $active_incidents = 42;
    $active_campaigns = 18;
    $avg_response_time = 8.2;
    $public_satisfaction = 92;
    $incident_types_result = [];
    $campaigns_result = [];
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
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/userprofile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Public Safety Campaign Management</title>

    <style>
        /* Chatbot Styles */
        .chatbot-toggle-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 22px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .chatbot-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .chatbot-toggle-btn .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            font-size: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chatbot-panel {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 400px;
            height: 600px;
            background: var(--secondary-black);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            display: none;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .chatbot-panel.open {
            display: flex;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .chatbot-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }

        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: var(--primary-black);
        }

        .message {
            margin-bottom: 15px;
            max-width: 80%;
        }

        .message-ai {
            margin-right: auto;
        }

        .message-user {
            margin-left: auto;
        }

        .message-content {
            padding: 12px 15px;
            border-radius: 15px;
            background: var(--dark-gray);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-ai .message-content {
            background: var(--dark-gray);
        }

        .message-user .message-content {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-gray);
            margin-top: 5px;
            text-align: right;
        }

        .chatbot-input-area {
            border-top: 1px solid var(--dark-gray);
            padding: 15px;
            background: var(--secondary-black);
        }

        .quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .quick-question-btn {
            background: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-gray);
        }

        .quick-question-btn:hover {
            background: var(--medium-gray);
            color: white;
        }

        .chatbot-input-container {
            display: flex;
            gap: 10px;
        }

        .chatbot-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 25px;
            font-size: 14px;
            background: var(--primary-black);
            color: white;
        }

        .chatbot-send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--dark-gray);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            background: var(--text-gray);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .chatbot-panel {
                width: calc(100% - 40px);
                height: calc(100vh - 100px);
                bottom: 20px;
                right: 20px;
            }
            
            .chatbot-toggle-btn {
                bottom: 20px;
                right: 20px;
            }
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
                    <a href="/home.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning & Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/content-repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/target-group-segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/eventseminarmanagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Event & Seminar Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/surveyfeedbackcollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Survey & Feedback Collection</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/campaignanalyticsreports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Campaign Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/models/healthpoliceintegration.php" class="nav-link">
                        <i class="fas fa-link"></i>
                        <span class="nav-text">Community Integration</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/logout.php" class="nav-link">
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

                    <!-- User Profile -->
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

            <!-- Charts Section -->
            <div class="charts-section">
                <!-- Interactive Incidents Matrix -->
                <div class="chart-container interactive-chart">
                    <div class="chart-header">
                        <div class="chart-title">Incidents by Type</div>
                        <div class="chart-legend">
                            <div class="legend-item active" data-type="all">
                                <div class="legend-color" style="background-color: #4A90E2;"></div>
                                <span>All Types</span>
                                <span class="badge"><?php echo $active_incidents; ?></span>
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
                            
                            if (!empty($incident_types_result)) {
                                $display_incidents = $incident_types_result;
                            } else {
                                $display_incidents = [
                                    ['type' => 'emergency', 'count' => 25, 'trend' => 12],
                                    ['type' => 'health', 'count' => 18, 'trend' => -5],
                                    ['type' => 'safety', 'count' => 32, 'trend' => 8],
                                    ['type' => 'fire', 'count' => 12, 'trend' => 0],
                                    ['type' => 'police', 'count' => 13, 'trend' => 15]
                                ];
                            }
                            
                            foreach ($display_incidents as $incident):
                                $incident_type = $incident['type'] ?? 'unknown';
                                $incident_count = $incident['count'] ?? 0;
                                $incident_trend = $incident['trend'] ?? 0;
                                $icon_class = $incident_icons[$incident_type] ?? 'fa-exclamation-triangle';
                            ?>
                            <div class="incident-type-card <?php echo htmlspecialchars($incident_type); ?>" data-type="<?php echo htmlspecialchars($incident_type); ?>" data-count="<?php echo $incident_count; ?>">
                                <div class="incident-icon">
                                    <i class="fas <?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="incident-info">
                                    <h4><?php echo ucfirst(htmlspecialchars($incident_type)); ?></h4>
                                    <div class="incident-count"><?php echo $incident_count; ?></div>
                                    <div class="incident-trend <?php echo getTrendClass($incident_trend); ?>"><?php echo getTrendIcon($incident_trend); ?> <?php echo abs($incident_trend); ?></div>
                                </div>
                                <div class="incident-actions">
                                    <button class="mini-action-btn view-details" onclick="viewIncidentDetails('<?php echo htmlspecialchars($incident_type); ?>')">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="mini-action-btn assign-team" onclick="assignTeam('<?php echo htmlspecialchars($incident_type); ?>')">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Heat Map Visualization -->
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
                        </div>
                    </div>

                    <!-- Campaign Cards Grid -->
                    <div class="campaign-grid">
                        <?php
                        if (!empty($campaigns_result)) {
                            $display_campaigns = $campaigns_result;
                            if (count($display_campaigns) < 4) {
                                $default_campaigns = [
                                    ['id' => 1, 'name' => 'Summer Safety', 'status' => 'active', 'completion_percentage' => 75, 'actual_reach' => 7500, 'engagement_rate' => 92, 'icon' => 'fa-sun'],
                                    ['id' => 2, 'name' => 'School Zone Safety', 'status' => 'active', 'completion_percentage' => 60, 'actual_reach' => 5200, 'engagement_rate' => 88, 'icon' => 'fa-school'],
                                    ['id' => 3, 'name' => 'Home Safety Week', 'status' => 'planned', 'completion_percentage' => 10, 'actual_reach' => 0, 'engagement_rate' => 0, 'icon' => 'fa-home'],
                                    ['id' => 4, 'name' => 'Road Safety Month', 'status' => 'completed', 'completion_percentage' => 100, 'actual_reach' => 12500, 'engagement_rate' => 95, 'icon' => 'fa-car']
                                ];
                                
                                foreach ($default_campaigns as $default_campaign) {
                                    $found = false;
                                    foreach ($display_campaigns as $campaign) {
                                        if ($campaign['name'] == $default_campaign['name']) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found && count($display_campaigns) < 4) {
                                        $display_campaigns[] = $default_campaign;
                                    }
                                }
                            }
                        } else {
                            $display_campaigns = [
                                ['id' => 1, 'name' => 'Summer Safety', 'status' => 'active', 'completion_percentage' => 75, 'actual_reach' => 7500, 'engagement_rate' => 92, 'icon' => 'fa-sun'],
                                ['id' => 2, 'name' => 'School Zone Safety', 'status' => 'active', 'completion_percentage' => 60, 'actual_reach' => 5200, 'engagement_rate' => 88, 'icon' => 'fa-school'],
                                ['id' => 3, 'name' => 'Home Safety Week', 'status' => 'planned', 'completion_percentage' => 10, 'actual_reach' => 0, 'engagement_rate' => 0, 'icon' => 'fa-home'],
                                ['id' => 4, 'name' => 'Road Safety Month', 'status' => 'completed', 'completion_percentage' => 100, 'actual_reach' => 12500, 'engagement_rate' => 95, 'icon' => 'fa-car']
                            ];
                        }
                        
                        $campaign_icons = [
                            'Summer Safety' => 'fa-sun',
                            'School Zone Safety' => 'fa-school',
                            'Home Safety Week' => 'fa-home',
                            'Road Safety Month' => 'fa-car',
                            'default' => 'fa-bullhorn'
                        ];
                        
                        foreach ($display_campaigns as $campaign):
                            $campaign_id = $campaign['id'] ?? uniqid();
                            $campaign_name = $campaign['name'] ?? 'Unnamed Campaign';
                            $campaign_status = $campaign['status'] ?? 'planned';
                            $completion_percentage = $campaign['completion_percentage'] ?? 0;
                            $actual_reach = $campaign['actual_reach'] ?? 0;
                            $engagement_rate = $campaign['engagement_rate'] ?? 0;
                            $campaign_icon = $campaign_icons[$campaign_name] ?? $campaign['icon'] ?? $campaign_icons['default'];
                        ?>
                        <div class="campaign-card <?php echo htmlspecialchars($campaign_status); ?>" data-id="<?php echo $campaign_id; ?>">
                            <div class="campaign-status <?php echo htmlspecialchars($campaign_status); ?>"><?php echo strtoupper(htmlspecialchars($campaign_status)); ?></div>
                            <div class="campaign-icon">
                                <i class="fas <?php echo $campaign_icon; ?>"></i>
                            </div>
                            <div class="campaign-info">
                                <h4><?php echo htmlspecialchars($campaign_name); ?></h4>
                                <div class="campaign-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, max(0, $completion_percentage)); ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo min(100, max(0, $completion_percentage)); ?>% Complete</span>
                                </div>
                                <div class="campaign-stats">
                                    <div class="stat">
                                        <i class="fas fa-eye"></i>
                                        <span><?php echo number_format($actual_reach); ?></span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?php echo $campaign_status === 'planned' ? 'N/A' : ($engagement_rate > 0 ? $engagement_rate . '%' : 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="campaign-actions">
                                <button class="campaign-action-btn" onclick="viewCampaign(<?php echo $campaign_id; ?>)" title="View Details">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button class="campaign-action-btn" onclick="editCampaign(<?php echo $campaign_id; ?>)" title="Edit">
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

    <!-- Chatbot Toggle Button -->
    <button class="chatbot-toggle-btn" id="chatbotToggleBtn">
        <i class="fas fa-robot"></i>
        <span class="badge" id="chatbotBadge">0</span>
    </button>

    <!-- Chatbot Panel -->
    <div class="chatbot-panel" id="chatbotPanel">
        <div class="chatbot-header">
            <div class="chatbot-header-title">
                <i class="fas fa-robot"></i>
                <span>AI Safety Assistant</span>
            </div>
            <button class="chatbot-close" id="closeChatbotBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="chatbot-messages" id="chatMessages">
            <div class="message message-ai">
                <div class="message-content">
                    👋 Hello! I'm your Public Safety AI Assistant. I can help you with:
                    • Incident analysis
                    • Campaign planning
                    • Report generation
                    • Safety recommendations
                    • Emergency procedures
                    How can I assist you today?
                </div>
                <div class="message-time">Just now</div>
            </div>
        </div>

        <div class="chatbot-input-area">
            <div class="quick-questions">
                <button class="quick-question-btn" onclick="askQuickQuestion('Show active incidents')">Active Incidents</button>
                <button class="quick-question-btn" onclick="askQuickQuestion('Generate safety report')">Generate Report</button>
                <button class="quick-question-btn" onclick="askQuickQuestion('Emergency procedures')">Emergency Guide</button>
                <button class="quick-question-btn" onclick="askQuickQuestion('Campaign suggestions')">Campaign Ideas</button>
            </div>

            <div class="chatbot-input-container">
                <input type="text"
                       class="chatbot-input"
                       id="chatInput"
                       placeholder="Ask about incidents, campaigns, or safety procedures...">
                <button class="chatbot-send-btn" id="sendChatBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Chatbot functionality
        let isChatbotOpen = false;
        let unreadMessages = 0;

        function toggleChatbot() {
            const panel = document.getElementById('chatbotPanel');
            isChatbotOpen = !isChatbotOpen;
            
            if (isChatbotOpen) {
                panel.classList.add('open');
                document.getElementById('chatInput').focus();
                // Reset badge when opened
                unreadMessages = 0;
                updateChatbotBadge();
            } else {
                panel.classList.remove('open');
            }
        }

        function closeChatbot() {
            const panel = document.getElementById('chatbotPanel');
            panel.classList.remove('open');
            isChatbotOpen = false;
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addMessage(message, 'user');
            input.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Simulate AI response
            setTimeout(() => {
                hideTypingIndicator();
                const response = getAIResponse(message);
                addMessage(response, 'ai');
                
                // Update badge if needed
                if (!isChatbotOpen) {
                    unreadMessages++;
                    updateChatbotBadge();
                }
            }, 1000);
        }

        function addMessage(text, sender) {
            const messages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message-${sender}`;
            
            const now = new Date();
            const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
            
            messageDiv.innerHTML = `
                <div class="message-content">${text}</div>
                <div class="message-time">${timeString}</div>
            `;
            
            messages.appendChild(messageDiv);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const messages = document.getElementById('chatMessages');
            let indicator = document.getElementById('typingIndicator');
            
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'typingIndicator';
                indicator.className = 'typing-indicator';
                indicator.innerHTML = `
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span>AI is typing...</span>
                `;
            }
            
            messages.appendChild(indicator);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }

        function scrollToBottom() {
            const messages = document.getElementById('chatMessages');
            messages.scrollTop = messages.scrollHeight;
        }

        function getAIResponse(input) {
            const lowerInput = input.toLowerCase();
            
            if (lowerInput.includes('incident') || lowerInput.includes('emergency')) {
                if (lowerInput.includes('active') || lowerInput.includes('current')) {
                    return `There are currently <strong>${<?php echo $active_incidents; ?>} active incidents</strong>. The most common type is safety-related incidents. Would you like me to show you detailed incident reports?`;
                } else if (lowerInput.includes('procedure') || lowerInput.includes('handle')) {
                    return `📋 <strong>Emergency Procedures</strong> 📋<br><br>
                           <strong>MEDICAL EMERGENCY:</strong><br>
                           1. Call 911 immediately<br>
                           2. Provide first aid<br>
                           3. Keep patient calm<br>
                           4. Clear area for responders<br><br>
                           <strong>FIRE EMERGENCY:</strong><br>
                           1. Activate fire alarm<br>
                           2. Evacuate immediately<br>
                           3. Use extinguisher if safe<br>
                           4. Report to assembly point`;
                }
                return `I can help with incident management. Ask me about:<br>
                        • Active incidents<br>
                        • Emergency procedures<br>
                        • Response coordination<br>
                        • Incident reporting`;
            } else if (lowerInput.includes('campaign') || lowerInput.includes('marketing')) {
                return `You have <strong>${<?php echo $active_campaigns; ?>} active campaigns</strong> running.<br><br>
                       <strong>Campaign Suggestions:</strong><br>
                       • Community Safety Workshops<br>
                       • Digital Awareness Campaign<br>
                       • School Safety Program<br>
                       • Emergency Response Training`;
            } else if (lowerInput.includes('report') || lowerInput.includes('generate')) {
                return `📊 <strong>Report Generator</strong> 📊<br><br>
                       I can help create:<br><br>
                       <strong>Daily Report:</strong><br>
                       • Incident summary<br>
                       • Response metrics<br>
                       • Campaign updates<br><br>
                       <strong>Weekly Report:</strong><br>
                       • Trend analysis<br>
                       • Resource allocation<br>
                       • Performance review`;
            } else if (lowerInput.includes('help') || lowerInput.includes('assist')) {
                return `I can help with:<br>
                        • Incident analysis<br>
                        • Campaign planning<br>
                        • Report generation<br>
                        • Safety recommendations<br>
                        • Emergency procedures<br>
                        • Data visualization<br>
                        • Risk assessment<br><br>
                        What would you like to know?`;
            } else if (lowerInput.includes('hello') || lowerInput.includes('hi')) {
                return 'Hello! 👋 How can I assist you with public safety management today?';
            } else {
                return `I understand you're asking about "${input}".<br><br>
                       As your Public Safety Assistant, I can help analyze data, generate reports, or provide safety recommendations.<br><br>
                       Could you be more specific about what you need?`;
            }
        }

        function askQuickQuestion(question) {
            const input = document.getElementById('chatInput');
            input.value = question;
            sendMessage();
        }

        function updateChatbotBadge() {
            const badge = document.getElementById('chatbotBadge');
            if (badge) {
                badge.textContent = unreadMessages > 0 ? unreadMessages : '0';
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Setup chatbot toggle button
            const toggleBtn = document.getElementById('chatbotToggleBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleChatbot);
            }
            
            // Setup chatbot close button
            const closeBtn = document.getElementById('closeChatbotBtn');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeChatbot);
            }
            
            // Setup send button
            const sendBtn = document.getElementById('sendChatBtn');
            if (sendBtn) {
                sendBtn.addEventListener('click', sendMessage);
            }
            
            // Setup enter key in input
            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendMessage();
                    }
                });
            }
            
            // Close chatbot when clicking outside
            document.addEventListener('click', function(e) {
                const panel = document.getElementById('chatbotPanel');
                const toggleBtn = document.getElementById('chatbotToggleBtn');
                
                if (panel && panel.classList.contains('open') && 
                    !panel.contains(e.target) && 
                    !toggleBtn.contains(e.target)) {
                    closeChatbot();
                }
            });

            // Initialize heat map
            initializeHeatMap();
        });

        // Initialize heat map with sample data
        function initializeHeatMap() {
            const heatMapGrid = document.getElementById('heatMapGrid');
            if (!heatMapGrid) return;

            // Sample data for 7 days x 8 time slots = 56 cells
            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const timeSlots = ['6-9', '9-12', '12-15', '15-18', '18-21', '21-24', '0-3', '3-6'];
            
            // Clear existing content
            heatMapGrid.innerHTML = '';
            
            // Create header row with days
            days.forEach(day => {
                const headerCell = document.createElement('div');
                headerCell.className = 'heat-map-cell header';
                headerCell.textContent = day;
                headerCell.style.background = 'transparent';
                headerCell.style.color = 'var(--text-gray)';
                headerCell.style.fontSize = '11px';
                headerCell.style.fontWeight = '600';
                headerCell.style.cursor = 'default';
                heatMapGrid.appendChild(headerCell);
            });
            
            // Create time slot cells with random incident counts
            for (let i = 0; i < timeSlots.length; i++) {
                for (let j = 0; j < days.length; j++) {
                    const cell = document.createElement('div');
                    const incidentCount = Math.floor(Math.random() * 20); // 0-19 incidents
                    
                    // Determine intensity level
                    if (incidentCount <= 5) {
                        cell.className = 'heat-map-cell low';
                    } else if (incidentCount <= 15) {
                        cell.className = 'heat-map-cell medium';
                    } else {
                        cell.className = 'heat-map-cell high';
                    }
                    
                    cell.title = `${days[j]} ${timeSlots[i]}: ${incidentCount} incidents`;
                    cell.textContent = incidentCount > 0 ? incidentCount : '';
                    heatMapGrid.appendChild(cell);
                }
            }
        }

        // Mark all notifications as read
        window.markAllAsRead = function() {
            const notifications = document.querySelectorAll('.notification-item.unread');
            notifications.forEach(notification => {
                notification.classList.remove('unread');
            });
            
            // Update badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = '0';
            }
            
            // Show success message
            showNotification('All notifications marked as read', 'success');
        };

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Placeholder functions for other buttons
        window.viewIncidentDetails = function(type) {
            alert('Viewing details for ' + type + ' incidents');
        };

        window.assignTeam = function(type) {
            alert('Assigning team to ' + type + ' incidents');
        };

        window.viewCampaign = function(id) {
            alert('Viewing campaign details for ID: ' + id);
        };

        window.editCampaign = function(id) {
            alert('Editing campaign with ID: ' + id);
        };

        window.addNewCampaign = function() {
            alert('Adding new campaign');
        };

        window.openExportModal = function() {
            alert('Opening export modal');
        };

        window.viewAllCampaigns = function() {
            alert('Viewing all campaigns');
        };

        window.remindMe = function(campaign) {
            alert('Setting reminder for ' + campaign);
        };

        window.viewLiveStats = function(campaign) {
            alert('Viewing live stats for ' + campaign);
        };

        // Make quick question function available globally
        window.askQuickQuestion = askQuickQuestion;
    </script>
</body>
</html>