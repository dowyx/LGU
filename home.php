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
    <link rel="stylesheet" href="styles/chatbot.css">
    <link rel="stylesheet" href="styles/userprofile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Public Safety Campaign Management</title>
    <style>
        /* Chatbot Styles */
        .chatbot-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
            border: none;
        }

        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .chatbot-toggle i {
            color: white;
            font-size: 24px;
        }

        .chatbot-panel {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            z-index: 999;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .chatbot-panel.open {
            display: flex;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chatbot-header .status-indicator {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .chatbot-close:hover {
            transform: rotate(90deg);
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.bot {
            flex-direction: row;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .message.bot .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message.user .message-avatar {
            background: #e5e7eb;
            color: #374151;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            line-height: 1.5;
            font-size: 14px;
        }

        .message.bot .message-content {
            background: white;
            color: #1f2937;
            border-bottom-left-radius: 5px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 5px;
        }

        .typing-indicator {
            display: none;
            padding: 12px 16px;
            background: white;
            border-radius: 15px;
            border-bottom-left-radius: 5px;
            width: fit-content;
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #9ca3af;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }

        .chatbot-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
        }

        .chatbot-input input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .chatbot-input input:focus {
            border-color: #667eea;
        }

        .chatbot-send {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .chatbot-send:hover {
            transform: scale(1.1);
        }

        .chatbot-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        .quick-actions {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            padding: 8px 14px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            color: #374151;
        }

        .quick-action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .chatbot-panel {
                width: calc(100% - 40px);
                right: 20px;
                bottom: 90px;
                height: 500px;
            }

            .chatbot-toggle {
                right: 20px;
                bottom: 20px;
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
                    <a href="home.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning & Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/content-repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/target-group-segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/eventseminarmanagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Event & Seminar Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/surveyfeedbackcollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Survey & Feedback Collection</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/campaignanalyticsreports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Campaign Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="models/healthpoliceintegration.php" class="nav-link">
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
                        <div class="notifications-menu">
                            <div class="notifications-header">
                                <h3>Notifications</h3>
                                <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <div class="notification-icon danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">High Priority Incident</div>
                                        <div class="notification-text">Fire reported at Downtown District requiring immediate response</div>
                                        <div class="notification-time">5 minutes ago</div>
                                    </div>
                                </div>
                                <div class="notification-item unread">
                                    <div class="notification-icon success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">Campaign Milestone</div>
                                        <div class="notification-text">"Summer Safety" campaign reached 75% completion</div>
                                        <div class="notification-time">1 hour ago</div>
                                    </div>
                                </div>
                                <div class="notification-item unread">
                                    <div class="notification-icon info">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">System Maintenance</div>
                                        <div class="notification-text">Scheduled maintenance tonight at 11 PM</div>
                                        <div class="notification-time">2 hours ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile -->
                    <div class="user-profile">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Active Incidents</div>
                        <div class="stat-value"><?php echo $active_incidents; ?></div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 12% from last week
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Active Campaigns</div>
                        <div class="stat-value"><?php echo $active_campaigns; ?></div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 8% from last week
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Avg Response Time</div>
                        <div class="stat-value"><?php echo $avg_response_time; ?> min</div>
                        <div class="stat-change down">
                            <i class="fas fa-arrow-down"></i> 15% improvement
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-smile"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Public Satisfaction</div>
                        <div class="stat-value"><?php echo $public_satisfaction; ?>%</div>
                        <div class="stat-change up">
                            <i class="fas fa-arrow-up"></i> 3% from last month
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Incident Types Card -->
                <div class="card incident-types-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list-ul"></i>
                            Active Incident Types
                        </div>
                        <button class="btn-secondary" onclick="openExportModal()">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="incident-types-list">
                            <?php 
                            $default_incidents = [
                                ['type' => 'Fire Emergency', 'count' => 15, 'trend' => 3],
                                ['type' => 'Traffic Accident', 'count' => 12, 'trend' => -2],
                                ['type' => 'Medical Emergency', 'count' => 8, 'trend' => 1],
                                ['type' => 'Public Disturbance', 'count' => 7, 'trend' => 0]
                            ];
                            
                            $incidents_to_display = !empty($incident_types_result) ? $incident_types_result : $default_incidents;
                            
                            foreach ($incidents_to_display as $incident): 
                            ?>
                            <div class="incident-type-item">
                                <div class="incident-type-info">
                                    <div class="incident-type-name"><?php echo htmlspecialchars($incident['type']); ?></div>
                                    <div class="incident-type-count"><?php echo $incident['count']; ?> active incidents</div>
                                </div>
                                <div class="incident-type-trend trend-<?php echo getTrendClass($incident['trend']); ?>">
                                    <?php echo getTrendIcon($incident['trend']); ?> <?php echo abs($incident['trend']); ?>
                                </div>
                                <div class="incident-type-actions">
                                    <button class="btn-icon" onclick="viewIncidentDetails('<?php echo htmlspecialchars($incident['type']); ?>')" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" onclick="assignTeam('<?php echo htmlspecialchars($incident['type']); ?>')" title="Assign Team">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Campaigns Overview Card -->
                <div class="card campaigns-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-bullhorn"></i>
                            Campaign Overview
                        </div>
                        <button class="btn-primary" onclick="addNewCampaign()">
                            <i class="fas fa-plus"></i>
                            New Campaign
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="campaigns-list">
                            <?php 
                            $default_campaigns = [
                                [
                                    'id' => 1,
                                    'name' => 'Summer Safety Campaign',
                                    'status' => 'active',
                                    'start_date' => '2024-06-01',
                                    'end_date' => '2024-08-31',
                                    'target_reach' => 100000,
                                    'actual_reach' => 75000,
                                    'completion_percentage' => 75,
                                    'engagement_rate' => 8.5
                                ],
                                [
                                    'id' => 2,
                                    'name' => 'Fire Prevention Week',
                                    'status' => 'active',
                                    'start_date' => '2024-10-01',
                                    'end_date' => '2024-10-07',
                                    'target_reach' => 50000,
                                    'actual_reach' => 30000,
                                    'completion_percentage' => 60,
                                    'engagement_rate' => 12.3
                                ]
                            ];
                            
                            $campaigns_to_display = !empty($campaigns_result) ? array_slice($campaigns_result, 0, 3) : $default_campaigns;
                            
                            foreach ($campaigns_to_display as $campaign): 
                            ?>
                            <div class="campaign-item">
                                <div class="campaign-header">
                                    <div class="campaign-name"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                    <span class="campaign-status status-<?php echo $campaign['status']; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </div>
                                <div class="campaign-dates">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($campaign['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?>
                                </div>
                                <div class="campaign-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $campaign['completion_percentage']; ?>%"></div>
                                    </div>
                                    <div class="progress-label"><?php echo $campaign['completion_percentage']; ?>% Complete</div>
                                </div>
                                <div class="campaign-stats">
                                    <div class="campaign-stat">
                                        <div class="stat-label">Reach</div>
                                        <div class="stat-value"><?php echo number_format($campaign['actual_reach']); ?> / <?php echo number_format($campaign['target_reach']); ?></div>
                                    </div>
                                    <div class="campaign-stat">
                                        <div class="stat-label">Engagement</div>
                                        <div class="stat-value"><?php echo $campaign['engagement_rate']; ?>%</div>
                                    </div>
                                </div>
                                <div class="campaign-actions">
                                    <button class="btn-secondary btn-sm" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>
                                    <button class="btn-primary btn-sm" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <button class="btn-text" onclick="viewAllCampaigns()">
                                View All Campaigns
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Heat Map Card -->
                <div class="card heat-map-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-fire"></i>
                            Incident Heat Map
                        </div>
                        <select class="filter-select">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>Last 90 Days</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="heat-map-container">
                            <div class="heat-map-grid" id="heatMapGrid">
                                <!-- Heat map will be generated by JavaScript -->
                            </div>
                            <div class="heat-map-legend">
                                <span>Low</span>
                                <div class="legend-gradient"></div>
                                <span>High</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Card -->
                <div class="card activity-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Activity
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="activity-list">
                            <li class="activity-item">
                                <div class="activity-icon icon-danger">
                                    <i class="fas fa-exclamation-circle"></i>
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
                </div>
            </div>
        </main>
    </div>

    <!-- AI Chatbot -->
    <button class="chatbot-toggle" id="chatbotToggle" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i>
    </button>

    <div class="chatbot-panel" id="chatbotPanel">
        <div class="chatbot-header">
            <h3>
                <span class="status-indicator"></span>
                Safety Assistant AI
            </h3>
            <button class="chatbot-close" onclick="toggleChatbot()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="quick-actions">
            <button class="quick-action-btn" onclick="askQuickQuestion('What are the current active incidents?')">
                Active Incidents
            </button>
            <button class="quick-action-btn" onclick="askQuickQuestion('Show me campaign statistics')">
                Campaign Stats
            </button>
            <button class="quick-action-btn" onclick="askQuickQuestion('What is the average response time?')">
                Response Time
            </button>
        </div>

        <div class="chatbot-messages" id="chatMessages">
            <div class="message bot">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    Hello! I'm your Safety Assistant AI. I can help you with information about incidents, campaigns, statistics, and more. How can I assist you today?
                </div>
            </div>
        </div>

        <div class="typing-indicator" id="typingIndicator">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="chatbot-input">
            <input type="text" id="chatInput" placeholder="Type your message..." />
            <button class="chatbot-send" id="sendBtn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        // Chatbot functionality
        function toggleChatbot() {
            const panel = document.getElementById('chatbotPanel');
            panel.classList.toggle('open');
            
            if (panel.classList.contains('open')) {
                document.getElementById('chatInput').focus();
            }
        }

        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        function addMessage(content, isUser = false) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
            
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-${isUser ? 'user' : 'robot'}"></i>
                </div>
                <div class="message-content">
                    ${content}
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            indicator.classList.add('active');
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            indicator.classList.remove('active');
        }

        async function getBotResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            
            // Context-aware responses based on dashboard data
            if (lowerMessage.includes('incident') || lowerMessage.includes('emergency')) {
                return `Currently, there are <?php echo $active_incidents; ?> active incidents in the system. The most common types are Fire Emergency (15), Traffic Accident (12), and Medical Emergency (8). Would you like more details about any specific incident type?`;
            }
            
            if (lowerMessage.includes('campaign')) {
                return `We have <?php echo $active_campaigns; ?> active campaigns running. The "Summer Safety Campaign" is at 75% completion with 75,000 people reached out of 100,000 target. The "Fire Prevention Week" campaign has reached 60% completion. Would you like to know more about a specific campaign?`;
            }
            
            if (lowerMessage.includes('response time')) {
                return `The average response time for the past week is <?php echo $avg_response_time; ?> minutes, which is a 15% improvement from the previous week. This shows our team is becoming more efficient in handling incidents.`;
            }
            
            if (lowerMessage.includes('satisfaction') || lowerMessage.includes('public')) {
                return `Public satisfaction is currently at <?php echo $public_satisfaction; ?>%, which is up 3% from last month. This positive trend indicates our safety campaigns and incident response efforts are being well-received by the community.`;
            }
            
            if (lowerMessage.includes('help') || lowerMessage.includes('what can you do')) {
                return `I can help you with:
                <br>• Viewing current incident statistics
                <br>• Campaign progress and performance
                <br>• Response time analysis
                <br>• Public satisfaction metrics
                <br>• Quick access to any dashboard information
                <br><br>Just ask me about any of these topics!`;
            }
            
            if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
                return `Hello! I'm here to help you navigate the Public Safety Campaign Management system. You can ask me about incidents, campaigns, statistics, or any other dashboard information.`;
            }
            
            // Default response
            return `I understand you're asking about "${userMessage}". I can provide information about active incidents, campaigns, response times, and public satisfaction. Could you please be more specific about what you'd like to know?`;
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addMessage(message, true);
            input.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Simulate AI processing delay
            setTimeout(async () => {
                hideTypingIndicator();
                const response = await getBotResponse(message);
                addMessage(response, false);
            }, 1000 + Math.random() * 1000); // Random delay between 1-2 seconds
        }

        function askQuickQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendMessage();
        }

        // Enter key to send message
        document.getElementById('chatInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>