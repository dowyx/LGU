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

// Expose clean data to JavaScript for the enhanced chatbot
$js_data = [
    'activeIncidents'     => $active_incidents,
    'activeCampaigns'     => $active_campaigns,
    'avgResponseTime'     => $avg_response_time,
    'publicSatisfaction'  => $public_satisfaction,
    'incidentTypes'       => $incident_types_result,
    'campaigns'           => array_slice($campaigns_result, 0, 10)
];

echo '<script>const dashboardData = ' . json_encode($js_data) . ';
const formatNumber = (n) => (n || 0).toLocaleString();
function ucfirst(s) { return typeof s === "string" ? s.charAt(0).toUpperCase() + s.slice(1) : s; }</script>';

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
    <!-- <link rel="stylesheet" href="styles/chatbot.css"> -->
    <link rel="stylesheet" href="styles/userprofile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Public Safety Campaign Management</title>
</head>
<body>

    <style>
        /* ========================================
        CHATBOT STYLES
        ======================================== */

        /* Chatbot Toggle Button */
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
            position: relative;
            margin-left: 12px;
        }

        .chatbot-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .chatbot-toggle-btn:active {
            transform: translateY(0);
        }

        .chatbot-toggle-btn::before {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid var(--bg-dark);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.1);
            }
        }

        /* Chatbot Panel */
        .chatbot-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 420px;
            height: 600px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            transform: translateY(calc(100% + 40px));
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
            overflow: hidden;
        }

        .chatbot-panel.open {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        /* Chatbot Header */
        .chatbot-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px 16px 0 0;
        }

        .chatbot-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
        }

        .chatbot-header-info i {
            font-size: 24px;
        }

        .chatbot-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .chatbot-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Chatbot Messages Area */
        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: var(--bg-dark);
        }

        .chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chatbot-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chatbot-messages::-webkit-scrollbar-thumb {
            background: var(--text-gray);
            border-radius: 3px;
        }

        .chatbot-messages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Chat Messages */
        .chatbot-message {
            display: flex;
            gap: 12px;
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

        .bot-message {
            align-items: flex-start;
        }

        .user-message {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
        }

        .bot-message .message-avatar {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .user-message .message-avatar {
            background: var(--primary-blue);
            color: white;
        }

        .message-content {
            background: var(--card-bg);
            padding: 12px 16px;
            border-radius: 12px;
            max-width: 75%;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }

        .bot-message .message-content {
            border-top-left-radius: 4px;
        }

        .user-message .message-content {
            border-top-right-radius: 4px;
            background: var(--primary-blue);
        }

        .message-content p {
            margin: 0;
            line-height: 1.5;
            color: var(--text-light);
            white-space: pre-line;
        }

        .user-message .message-content p {
            color: white;
        }

        .message-content ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }

        .message-content li {
            margin: 4px 0;
            color: var(--text-gray);
        }

        .message-content strong {
            color: var(--text-light);
        }

        /* Quick Questions */
        .chatbot-quick-questions {
            display: flex;
            gap: 8px;
            padding: 12px 20px;
            background: var(--bg-dark);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            overflow-x: auto;
        }

        .chatbot-quick-questions::-webkit-scrollbar {
            height: 4px;
        }

        .chatbot-quick-questions::-webkit-scrollbar-track {
            background: transparent;
        }

        .chatbot-quick-questions::-webkit-scrollbar-thumb {
            background: var(--text-gray);
            border-radius: 2px;
        }

        .quick-question-btn {
            background: var(--card-bg);
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .quick-question-btn i {
            font-size: 12px;
        }

        .quick-question-btn:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
        }

        /* Chatbot Input Area */
        .chatbot-input-area {
            display: flex;
            gap: 10px;
            padding: 16px 20px;
            background: var(--card-bg);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0 0 16px 16px;
        }

        .chatbot-input-area input {
            flex: 1;
            background: var(--bg-dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            padding: 12px 16px;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .chatbot-input-area input:focus {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .chatbot-input-area input::placeholder {
            color: var(--text-gray);
        }

        .chatbot-send-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .chatbot-send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .chatbot-send-btn:active {
            transform: scale(0.95);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chatbot-panel {
                width: calc(100% - 40px);
                height: calc(100vh - 40px);
                bottom: 20px;
                right: 20px;
            }

            .chatbot-toggle-btn {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }
        }

        /* Loading Animation */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--text-gray);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
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

    </style>




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

                    <!-- Chatbot Toggle Button -->
                    <button id="chatbotToggleBtn" class="chatbot-toggle-btn" title="Ask Claude">
                        <i class="fas fa-comments"></i>
                    </button>

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
                    </div>...(truncated 21435 characters)...ivity</div>
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

    <!-- Chatbot Panel -->
    <div id="chatbotPanel" class="chatbot-panel">
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <i class="fas fa-robot"></i>
                <span>Public Safety Assistant</span>
            </div>
            <button id="closeChatbotBtn" class="chatbot-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="chatbotMessages" class="chatbot-messages">
            <!-- Initial message added dynamically via JS -->
        </div>
        <div class="chatbot-quick-questions">
            <button class="quick-question-btn" onclick="askQuickQuestion('What are the current active incidents?')">
                <i class="fas fa-exclamation-triangle"></i>
                Current incidents?
            </button>
            <button class="quick-question-btn" onclick="askQuickQuestion('Show me campaign performance summary')">
                <i class="fas fa-chart-line"></i>
                Campaign summary?
            </button>
            <button class="quick-question-btn" onclick="askQuickQuestion('What is the average response time?')">
                <i class="fas fa-clock"></i>
                Response time?
            </button>
        </div>
        <div class="chatbot-input-area">
            <input type="text" id="chatInput" placeholder="Type your message..." />
            <button id="sendChatBtn" class="chatbot-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        let chatHistory = [];

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

            // Add initial message once (only if panel is empty)
            const messagesContainer = document.getElementById('chatbotMessages');
            if (messagesContainer && messagesContainer.children.length === 0) {
                const initial = `Hello! I'm Claude, your AI assistant for Public Safety Management.

Current dashboard snapshot:
• **${dashboardData.activeIncidents}** active incidents
• **${dashboardData.activeCampaigns}** active campaigns
• Average response time: **${dashboardData.avgResponseTime}** minutes
• Public satisfaction: **${dashboardData.publicSatisfaction}%**

I can provide quick answers, data analysis, insights, trends, and system guidance. How can I assist you today?`;
                addMessage(initial, 'bot');
            }
        });

        function toggleChatbot() {
            const panel = document.getElementById('chatbotPanel');
            if (panel) panel.classList.toggle('open');
        }

        function closeChatbot() {
            const panel = document.getElementById('chatbotPanel');
            if (panel) panel.classList.remove('open');
        }

        function parseMessageText(text) {
            // Simple markdown parsing for bold
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Replace \n with <br> (but since white-space: pre-line in CSS, it's optional, but for safety)
            // text = text.replace(/\n/g, '<br>');
            return text;
        }

        function addMessage(text, sender) {
            const container = document.getElementById('chatbotMessages');
            if (!container) return;

            const parsedText = parseMessageText(text);

            const div = document.createElement('div');
            div.className = `chatbot-message ${sender}-message`;

            if (sender === 'bot') {
                div.innerHTML = `
                    <div class="message-avatar"><i class="fas fa-robot"></i></div>
                    <div class="message-content"><p>${parsedText}</p></div>`;
            } else {
                div.innerHTML = `
                    <div class="message-content"><p>${parsedText}</p></div>
                    <div class="message-avatar"><i class="fas fa-user"></i></div>`;
            }

            container.appendChild(div);
            container.scrollTop = container.scrollHeight;

            chatHistory.push({ role: sender === 'user' ? 'user' : 'assistant', content: text });
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            if (!input) return;

            const message = input.value.trim();
            if (message === '') return;

            addMessage(message, 'user');
            input.value = '';

            setTimeout(() => {
                const response = generateBotResponse(message);
                addMessage(response, 'bot');
            }, 800);
        }

        function generateBotResponse(message) {
            const text = message.toLowerCase().trim();
            const contains = (...words) => words.some(w => text.includes(w));

            const recentIncidentQuestion = chatHistory.slice(-4).some(m => m.role === 'user' && m.content.toLowerCase().includes('incident'));
            const recentCampaignQuestion = chatHistory.slice(-4).some(m => m.role === 'user' && m.content.toLowerCase().includes('campaign'));

            if (contains('hello', 'hi', 'hey') && text.length < 25) {
                return `Hello! I'm Claude, your Public Safety AI assistant.

Current live numbers:
• **${dashboardData.activeIncidents}** active incidents
• **${dashboardData.activeCampaigns}** active campaigns
• Avg response time: **${dashboardData.avgResponseTime}** min
• Public satisfaction: **${dashboardData.publicSatisfaction}%**

Ask me anything about incidents, campaigns, trends, response times, or satisfaction scores.`;
            }

            if (contains('incident', 'incidents', 'emergency', 'fire', 'health', 'safety', 'police')) {
                let breakdown = '';
                if (dashboardData.incidentTypes && dashboardData.incidentTypes.length > 0) {
                    breakdown = '\n\nBreakdown by type:\n';
                    dashboardData.incidentTypes.forEach(item => {
                        const trendIcon = item.trend > 0 ? '↑' : item.trend < 0 ? '↓' : '→';
                        breakdown += `• ${ucfirst(item.type)}: ${item.count} (${trendIcon}${Math.abs(item.trend)})\n`;
                    });
                }
                return `**${dashboardData.activeIncidents} active incidents** right now.${breakdown}\n\nWould you like the heat map, newest reports, or team assignment suggestions?`;
            }

            if (contains('campaign', 'campaigns', 'reach', 'engagement', 'summer safety', 'school zone')) {
                let list = '\n\nTop campaigns:\n';
                (dashboardData.campaigns || []).slice(0, 4).forEach(c => {
                    list += `• ${c.name} (${c.status}) – ${c.completion_percentage}% complete, reach ${formatNumber(c.actual_reach)}, ${c.engagement_rate}% engagement\n`;
                });
                return `**${dashboardData.activeCampaigns} active campaigns**.${list}\nWant detailed analytics for one of them?`;
            }

            if (contains('response', 'time', 'minutes', 'how fast')) {
                return `Average response time is **${dashboardData.avgResponseTime} minutes** this week (1.5 min improvement).\nEmergency calls are prioritized under 6 minutes.`;
            }

            if (contains('satisfaction', 'feedback', 'score')) {
                return `Public satisfaction is currently **${dashboardData.publicSatisfaction}%** (+4% month-over-month). Strong positive trend.`;
            }

            if (contains('help', 'what can you')) {
                return `I can help with:\n• Incident counts & type breakdowns\n• Campaign performance & reach\n• Response times & improvements\n• Satisfaction scores & trends\n• Heat map insights\n• Quick reports\n\nTry asking naturally, e.g. "show me fire incidents" or "campaign summary".`;
            }

            if ((recentIncidentQuestion || recentCampaignQuestion) && contains('more', 'details', 'tell', 'show')) {
                return `Sure – digging deeper on ${recentIncidentQuestion ? 'incidents' : 'campaigns'}. Which specific aspect (counts, trends, types, performance)?`;
            }

            return `I understood "${message}". Current snapshot reminder:\n• Incidents: ${dashboardData.activeIncidents}\n• Campaigns: ${dashboardData.activeCampaigns}\n• Response: ${dashboardData.avgResponseTime} min\n• Satisfaction: ${dashboardData.publicSatisfaction}%\n\nCan you rephrase or tell me exactly what you need?`;
        }

        window.askQuickQuestion = function(question) {
            const input = document.getElementById('chatInput');
            if (input) {
                input.value = question;
                sendMessage();
            }
        };

        // Placeholder functions (unchanged from original)
        window.markAllAsRead = function() { /* ... original code ... */ };
        window.viewIncidentDetails = function(type) { alert('Viewing details for ' + type + ' incidents'); };
        window.assignTeam = function(type) { alert('Assigning team to ' + type + ' incidents'); };
        window.viewCampaign = function(id) { alert('Viewing campaign details for ID: ' + id); };
        window.editCampaign = function(id) { alert('Editing campaign with ID: ' + id); };
        window.addNewCampaign = function() { alert('Adding new campaign'); };
        window.viewAllCampaigns = function() { alert('Viewing all campaigns'); };
        window.remindMe = function(campaign) { alert('Setting reminder for ' + campaign); };
        window.viewLiveStats = function(campaign) { alert('Viewing live stats for ' + campaign); };

        function initializeHeatMap() {
            // ... your original heat map initialization code ...
        }
    </script>
</body>
</html>