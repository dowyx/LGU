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
    
    // Recent activity data
    $stmt = $pdo->prepare("
        (SELECT 
            'incident' as type,
            CONCAT('New incident reported: ', description) as text,
            created_at as time,
            'exclamation-triangle' as icon,
            'alert' as icon_class
        FROM incidents 
        ORDER BY created_at DESC 
        LIMIT 3)
        UNION ALL
        (SELECT 
            'campaign' as type,
            CONCAT('Campaign \"', name, '\" updated') as text,
            updated_at as time,
            'bullhorn' as icon,
            'success' as icon_class
        FROM campaigns 
        WHERE status = 'active'
        ORDER BY updated_at DESC 
        LIMIT 3)
        ORDER BY time DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $recent_activity = [];
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

// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
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
        /* Additional styles */
        :root {
            --primary: #4A90E2;
            --secondary: #764ba2;
            --success: #4CAF50;
            --warning: #FFA726;
            --danger: #FF4757;
            --info: #17a2b8;
            --dark: #343a40;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #e0e0e0;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #1a237e 0%, #283593 100%);
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
            position: fixed;
            height: 100vh;
        }
        
        .logo {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo h1 {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 20px 0;
            flex: 1;
        }
        
        .nav-item {
            margin: 5px 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #4A90E2;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
            text-align: center;
        }
        
        .nav-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 0;
            background: #f5f7fa;
        }
        
        /* Header Styles */
        .header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .header h2 {
            color: #1a237e;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: #f5f7fa;
            border-radius: 25px;
            padding: 10px 20px;
            width: 300px;
            border: 1px solid var(--border);
        }
        
        .search-box i {
            color: var(--gray);
            margin-right: 10px;
        }
        
        .search-box input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
            color: #333;
        }
        
        /* Notifications */
        .notifications-dropdown {
            position: relative;
        }
        
        .notifications-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray);
            cursor: pointer;
            position: relative;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .notifications-btn:hover {
            background: #f5f7fa;
            color: var(--primary);
        }
        
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .notifications-menu {
            position: absolute;
            top: 50px;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: none;
            border: 1px solid var(--border);
            z-index: 1000;
        }
        
        .notifications-dropdown:hover .notifications-menu {
            display: block;
        }
        
        .notifications-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notifications-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .mark-all-read {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: flex-start;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f9f9f9;
        }
        
        .notification-item.unread {
            background: rgba(74, 144, 226, 0.05);
        }
        
        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .notification-icon.alert {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger);
        }
        
        .notification-icon.success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-text {
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 12px;
            color: var(--gray);
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: #f5f7fa;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-profile:hover {
            background: #e8eef7;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        /* Dashboard Widgets */
        .dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .widget {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .widget-title {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .widget-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .icon-incidents {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger);
        }
        
        .icon-campaigns {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .icon-response {
            background: rgba(255, 167, 38, 0.1);
            color: var(--warning);
        }
        
        .icon-analytics {
            background: rgba(74, 144, 226, 0.1);
            color: var(--primary);
        }
        
        .widget-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .widget-change {
            font-size: 14px;
        }
        
        .widget-change .positive {
            color: var(--success);
        }
        
        .widget-change .negative {
            color: var(--danger);
        }
        
        /* Charts Section */
        .charts-section {
            padding: 0 30px 30px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }
        
        .chart-legend {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #f5f7fa;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .legend-item:hover {
            background: #e8eef7;
        }
        
        .legend-item.active {
            background: rgba(74, 144, 226, 0.1);
            color: var(--primary);
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        .badge {
            background: var(--primary);
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .chart-filters select {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: white;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }
        
        /* Incident Types Grid */
        .incident-types {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .incident-type-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .incident-type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .incident-icon {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }
        
        .incident-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .incident-count {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .incident-trend {
            font-size: 13px;
            font-weight: 500;
        }
        
        .incident-trend.up {
            color: var(--success);
        }
        
        .incident-trend.down {
            color: var(--danger);
        }
        
        .incident-trend.neutral {
            color: var(--gray);
        }
        
        .incident-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 5px;
        }
        
        .mini-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: white;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .mini-action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Heat Map */
        .heat-map-container {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .heat-map-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .heat-map-title span {
            font-weight: 600;
            color: #333;
        }
        
        .heat-map-period {
            font-size: 14px;
            color: var(--gray);
            font-weight: normal;
        }
        
        .heat-map-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .heat-map-cell {
            aspect-ratio: 1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .heat-map-cell:hover {
            transform: scale(1.1);
        }
        
        .heat-map-cell.low { background: #c6f6d5; }
        .heat-map-cell.medium { background: #68d391; }
        .heat-map-cell.high { background: #38a169; }
        
        .heat-map-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }
        
        .legend-color.low { background: #c6f6d5; }
        .legend-color.medium { background: #68d391; }
        .legend-color.high { background: #38a169; }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--border);
        }
        
        .stat-card i {
            font-size: 24px;
            color: var(--primary);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--gray);
        }
        
        /* Campaign Grid */
        .campaign-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .campaign-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .campaign-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .campaign-status {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        
        .campaign-status.active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .campaign-status.planned {
            background: rgba(255, 167, 38, 0.1);
            color: var(--warning);
        }
        
        .campaign-status.completed {
            background: rgba(74, 144, 226, 0.1);
            color: var(--primary);
        }
        
        .campaign-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--primary);
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }
        
        .campaign-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .campaign-progress {
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4A90E2, #764ba2);
            border-radius: 4px;
        }
        
        .progress-text {
            font-size: 12px;
            color: var(--gray);
        }
        
        .campaign-stats {
            display: flex;
            gap: 20px;
        }
        
        .campaign-stats .stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
        }
        
        .campaign-stats .stat i {
            color: var(--gray);
        }
        
        .campaign-actions {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 5px;
        }
        
        .campaign-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: white;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .campaign-action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Performance Metrics */
        .performance-metrics {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .metric-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .metric {
            text-align: center;
        }
        
        .metric-label {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .metric-change {
            font-size: 13px;
            font-weight: 500;
        }
        
        .metric-change.positive {
            color: var(--success);
        }
        
        .metric-change.negative {
            color: var(--danger);
        }
        
        .metric-change.neutral {
            color: var(--gray);
        }
        
        /* Timeline */
        .campaign-timeline {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--border);
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .timeline-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .view-all-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        
        .timeline-item:hover {
            background: #e8eef7;
        }
        
        .timeline-item.current {
            background: rgba(74, 144, 226, 0.05);
            border-color: var(--primary);
        }
        
        .timeline-date {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            width: 60px;
            flex-shrink: 0;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .timeline-desc {
            font-size: 12px;
            color: var(--gray);
        }
        
        .timeline-actions {
            flex-shrink: 0;
        }
        
        .timeline-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: white;
            color: var(--gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .timeline-action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Recent Activity */
        .activity-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin: 0 30px 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .activity-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .icon-alert {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger);
        }
        
        .icon-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }
        
        .icon-info {
            background: rgba(74, 144, 226, 0.1);
            color: var(--primary);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--gray);
        }
        
        /* Chatbot Toggle Button */
        .chatbot-toggle-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .chatbot-toggle-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
        }
        
        .chatbot-toggle-btn i {
            color: white;
            font-size: 24px;
        }
        
        .chatbot-toggle-btn .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Chatbot Panel */
        .chatbot-panel {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            height: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            z-index: 999;
            display: none;
            flex-direction: column;
            border: 1px solid var(--border);
        }
        
        .chatbot-panel.open {
            display: flex;
        }
        
        .chatbot-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
            color: white;
        }
        
        .chatbot-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .chatbot-header-title i {
            font-size: 22px;
        }
        
        .chatbot-close {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .chatbot-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 20px;
            max-width: 80%;
        }
        
        .message-ai {
            align-self: flex-start;
        }
        
        .message-user {
            align-self: flex-end;
            margin-left: auto;
        }
        
        .message-content {
            padding: 15px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .message-ai .message-content {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px 15px 15px 5px;
        }
        
        .message-user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 5px 15px;
        }
        
        .message-time {
            font-size: 11px;
            color: var(--gray);
            margin-top: 5px;
            text-align: right;
        }
        
        .chatbot-input-area {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: white;
            border-radius: 0 0 15px 15px;
        }
        
        .quick-questions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .quick-question-btn {
            padding: 8px 12px;
            background: #f5f7fa;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            color: #333;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .quick-question-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .chatbot-input-container {
            display: flex;
            gap: 10px;
        }
        
        .chatbot-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 25px;
            font-size: 14px;
            outline: none;
        }
        
        .chatbot-input:focus {
            border-color: var(--primary);
        }
        
        .chatbot-send-btn {
            width: 50px;
            height: 50px;
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
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn-small {
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .action-btn-small:hover {
            background: #3a7bd5;
            transform: translateY(-2px);
        }
        
        /* Show Export Report button only where needed */
        .chart-actions .action-btn-small:last-child {
            display: inline-flex !important;
            background: var(--success);
        }
        
        .chart-actions .action-btn-small:last-child:hover {
            background: #43a047;
        }
        
        /* Notification popup */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--success);
            animation: slideIn 0.3s ease;
        }
        
        .notification-success {
            border-left-color: var(--success);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification i {
            color: var(--success);
            font-size: 18px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .nav-text {
                display: none;
            }
            
            .logo h1 {
                font-size: 0;
            }
            
            .logo:after {
                content: "PS";
                font-size: 18px;
                font-weight: 600;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-box {
                width: 100%;
            }
            
            .dashboard-widgets {
                grid-template-columns: 1fr;
            }
            
            .charts-section {
                padding: 15px;
            }
            
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .incident-types {
                grid-template-columns: 1fr;
            }
            
            .campaign-grid {
                grid-template-columns: 1fr;
            }
            
            .metric-row {
                grid-template-columns: 1fr 1fr;
            }
            
            .chatbot-panel {
                width: calc(100vw - 40px);
                right: 20px;
                bottom: 20px;
                height: 500px;
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
                                <div class="notification-item unread">
                                    <div class="notification-icon alert">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">System update required for incident reports</div>
                                        <div class="notification-time">2 hours ago</div>
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
                            <div style="font-size: 13px; color: var(--gray);"><?php echo htmlspecialchars($user_role); ?></div>
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
                                'police' => 'fa-badge',
                                'traffic' => 'fa-traffic-light',
                                'environmental' => 'fa-leaf',
                                'cyber' => 'fa-shield-alt'
                            ];
                            
                            if (!empty($incident_types_result)) {
                                $display_incidents = $incident_types_result;
                            } else {
                                $display_incidents = [
                                    ['type' => 'emergency', 'count' => 25, 'trend' => 12],
                                    ['type' => 'health', 'count' => 18, 'trend' => -5],
                                    ['type' => 'safety', 'count' => 32, 'trend' => 8],
                                    ['type' => 'fire', 'count' => 12, 'trend' => 0],
                                    ['type' => 'police', 'count' => 13, 'trend' => 15],
                                    ['type' => 'traffic', 'count' => 8, 'trend' => -3],
                                    ['type' => 'environmental', 'count' => 5, 'trend' => 2],
                                    ['type' => 'cyber', 'count' => 3, 'trend' => 1]
                                ];
                            }
                            
                            foreach ($display_incidents as $incident):
                                $incident_type = $incident['type'] ?? 'unknown';
                                $incident_count = $incident['count'] ?? 0;
                                $incident_trend = $incident['trend'] ?? 0;
                                $icon_class = $incident_icons[$incident_type] ?? 'fa-exclamation-triangle';
                            ?>
                            <div class="incident-type-card" data-type="<?php echo htmlspecialchars($incident_type); ?>" data-count="<?php echo $incident_count; ?>">
                                <div class="incident-icon">
                                    <i class="fas <?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="incident-info">
                                    <h4><?php echo ucfirst(htmlspecialchars($incident_type)); ?> Incidents</h4>
                                    <div class="incident-count"><?php echo $incident_count; ?></div>
                                    <div class="incident-trend <?php echo getTrendClass($incident_trend); ?>">
                                        <?php echo getTrendIcon($incident_trend); ?> <?php echo abs($incident_trend); ?> this week
                                    </div>
                                </div>
                                <div class="incident-actions">
                                    <button class="mini-action-btn view-details" onclick="viewIncidentDetails('<?php echo htmlspecialchars($incident_type); ?>')" title="View Details">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="mini-action-btn assign-team" onclick="assignTeam('<?php echo htmlspecialchars($incident_type); ?>')" title="Assign Team">
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
                            <button class="action-btn-small" onclick="openExportModal()">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                        </div>
                    </div>

                    <!-- Campaign Cards Grid -->
                    <div class="campaign-grid">
                        <?php
                        if (!empty($campaigns_result)) {
                            $display_campaigns = array_slice($campaigns_result, 0, 4);
                            if (count($display_campaigns) < 4) {
                                $default_campaigns = [
                                    ['id' => 1, 'name' => 'Summer Safety', 'status' => 'active', 'completion_percentage' => 75, 'actual_reach' => 7500, 'engagement_rate' => 92],
                                    ['id' => 2, 'name' => 'School Zone Safety', 'status' => 'active', 'completion_percentage' => 60, 'actual_reach' => 5200, 'engagement_rate' => 88],
                                    ['id' => 3, 'name' => 'Home Safety Week', 'status' => 'planned', 'completion_percentage' => 10, 'actual_reach' => 0, 'engagement_rate' => 0],
                                    ['id' => 4, 'name' => 'Road Safety Month', 'status' => 'completed', 'completion_percentage' => 100, 'actual_reach' => 12500, 'engagement_rate' => 95]
                                ];
                                
                                foreach ($default_campaigns as $default_campaign) {
                                    $found = false;
                                    foreach ($display_campaigns as $campaign) {
                                        if (isset($campaign['name']) && $campaign['name'] == $default_campaign['name']) {
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
                                ['id' => 1, 'name' => 'Summer Safety', 'status' => 'active', 'completion_percentage' => 75, 'actual_reach' => 7500, 'engagement_rate' => 92],
                                ['id' => 2, 'name' => 'School Zone Safety', 'status' => 'active', 'completion_percentage' => 60, 'actual_reach' => 5200, 'engagement_rate' => 88],
                                ['id' => 3, 'name' => 'Home Safety Week', 'status' => 'planned', 'completion_percentage' => 10, 'actual_reach' => 0, 'engagement_rate' => 0],
                                ['id' => 4, 'name' => 'Road Safety Month', 'status' => 'completed', 'completion_percentage' => 100, 'actual_reach' => 12500, 'engagement_rate' => 95]
                            ];
                        }
                        
                        $campaign_icons = [
                            'Summer Safety' => 'fa-sun',
                            'School Zone Safety' => 'fa-school',
                            'Home Safety Week' => 'fa-home',
                            'Road Safety Month' => 'fa-car',
                            'Cybersecurity Month' => 'fa-shield-alt',
                            'default' => 'fa-bullhorn'
                        ];
                        
                        foreach ($display_campaigns as $campaign):
                            $campaign_id = $campaign['id'] ?? uniqid();
                            $campaign_name = $campaign['name'] ?? 'Unnamed Campaign';
                            $campaign_status = $campaign['status'] ?? 'planned';
                            $completion_percentage = $campaign['completion_percentage'] ?? 0;
                            $actual_reach = $campaign['actual_reach'] ?? 0;
                            $engagement_rate = $campaign['engagement_rate'] ?? 0;
                            $campaign_icon = $campaign_icons[$campaign_name] ?? $campaign_icons['default'];
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
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon icon-<?php echo htmlspecialchars($activity['icon_class'] ?? 'info'); ?>">
                                <i class="fas fa-<?php echo htmlspecialchars($activity['icon'] ?? 'info-circle'); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text"><?php echo htmlspecialchars($activity['text']); ?></div>
                                <div class="activity-time"><?php echo timeAgo($activity['time']); ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <div class="activity-text">No recent activity to display</div>
                                <div class="activity-time">System is up to date</div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </main>
    </div>

    <!-- Chatbot Toggle Button (Floating) -->
    <button class="chatbot-toggle-btn" id="chatbotToggleBtn">
        <i class="fas fa-robot"></i>
        <span class="badge" id="chatbotBadge">0</span>
    </button>

    <!-- Chatbot Panel (Hidden by default) -->
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
                    <br><br>
                    • Incident analysis and reporting<br>
                    • Campaign planning and optimization<br>
                    • Report generation and analytics<br>
                    • Safety recommendations<br>
                    • Emergency procedures guidance<br>
                    <br>
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
    // Simple chatbot functionality
    document.addEventListener('DOMContentLoaded', function() {
        const chatbotToggleBtn = document.getElementById('chatbotToggleBtn');
        const chatbotPanel = document.getElementById('chatbotPanel');
        const closeChatbotBtn = document.getElementById('closeChatbotBtn');
        const chatInput = document.getElementById('chatInput');
        const sendChatBtn = document.getElementById('sendChatBtn');
        const chatMessages = document.getElementById('chatMessages');
        
        // Generate heat map
        generateHeatMap();
        
        // Toggle chatbot panel
        chatbotToggleBtn.addEventListener('click', function() {
            chatbotPanel.classList.toggle('open');
            // Clear badge when opened
            if (chatbotPanel.classList.contains('open')) {
                document.getElementById('chatbotBadge').textContent = '0';
            }
        });
        
        // Close chatbot panel
        closeChatbotBtn.addEventListener('click', function() {
            chatbotPanel.classList.remove('open');
        });
        
        // Send message function
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message === '') return;
            
            // Add user message
            addMessage(message, 'user');
            
            // Clear input
            chatInput.value = '';
            
            // Simulate AI response after delay
            setTimeout(() => {
                const response = getAIResponse(message);
                addMessage(response, 'ai');
                scrollToBottom();
            }, 1000);
        }
        
        // Add message to chat
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message-${sender}`;
            
            const now = new Date();
            const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
            
            messageDiv.innerHTML = `
                <div class="message-content">${text}</div>
                <div class="message-time">${timeString}</div>
            `;
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Scroll to bottom of chat
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Get AI response based on input
        function getAIResponse(input) {
            const lowerInput = input.toLowerCase();
            
            if (lowerInput.includes('incident') || lowerInput.includes('emergency')) {
                return `There are currently <strong>${<?php echo $active_incidents; ?>} active incidents</strong>. The most common type is safety-related incidents (32 active cases). Would you like me to:<br>
                1. Show detailed incident reports?<br>
                2. Generate incident statistics?<br>
                3. Suggest response strategies?`;
            } else if (lowerInput.includes('campaign') || lowerInput.includes('marketing')) {
                return `You have <strong>${<?php echo $active_campaigns; ?>} active campaigns</strong> running. The average completion rate is 78% and total reach is 38,200 people.<br><br>
                <strong>Top Performing Campaign:</strong> Summer Safety (92% engagement)<br>
                <strong>Needs Attention:</strong> School Zone Safety (60% completion)<br><br>
                Need help with campaign planning or optimization?`;
            } else if (lowerInput.includes('report') || lowerInput.includes('generate')) {
                return 'I can help you generate a comprehensive safety report. Would you like:<br>
                1) Weekly incident summary<br>
                2) Campaign performance report<br>
                3) Public satisfaction analysis<br>
                4) Risk assessment report<br>
                5) Emergency response metrics';
            } else if (lowerInput.includes('help') || lowerInput.includes('assist')) {
                return 'I can help with:<br>
                • Incident analysis and reporting<br>
                • Campaign planning and tracking<br>
                • Report generation and analytics<br>
                • Safety recommendations<br>
                • Emergency procedures guidance<br>
                • Data visualization<br>
                • Risk assessment and mitigation<br>
                <br>What would you like to know?';
            } else if (lowerInput.includes('hello') || lowerInput.includes('hi') || lowerInput.includes('hey')) {
                return 'Hello! 👋 How can I assist you with public safety management today? I\'m here to help with incidents, campaigns, reports, and safety guidance.';
            } else if (lowerInput.includes('safety') && lowerInput.includes('tip')) {
                return 'Here are some safety tips I can provide:<br>
                1. Emergency evacuation procedures<br>
                2. Fire safety guidelines<br>
                3. First aid basics<br>
                4. Cybersecurity best practices<br>
                5. Natural disaster preparedness<br>
                <br>Which area would you like to explore?';
            } else if (lowerInput.includes('stat') || lowerInput.includes('data') || lowerInput.includes('number')) {
                return `Current Dashboard Statistics:<br>
                • Active Incidents: ${<?php echo $active_incidents; ?>}<br>
                • Active Campaigns: ${<?php echo $active_campaigns; ?>}<br>
                • Avg Response Time: ${<?php echo $avg_response_time; ?>} minutes<br>
                • Public Satisfaction: ${<?php echo $public_satisfaction; ?>}%<br>
                • Campaign Reach: 38,200 people<br>
                • Resolution Rate: 94%<br>
                <br>Would you like more detailed statistics?`;
            } else {
                return `I understand you're asking about "${input}". As your Public Safety Assistant, I specialize in:<br>
                1. Analyzing incident data and trends<br>
                2. Optimizing campaign performance<br>
                3. Generating safety reports<br>
                4. Providing emergency guidance<br>
                <br>Could you be more specific about what you need?`;
            }
        }
        
        // Generate heat map
        function generateHeatMap() {
            const grid = document.getElementById('heatMapGrid');
            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            
            for (let i = 0; i < 7; i++) {
                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement('div');
                    const count = Math.floor(Math.random() * 20) + 1;
                    
                    let intensity = 'low';
                    if (count > 15) intensity = 'high';
                    else if (count > 5) intensity = 'medium';
                    
                    cell.className = `heat-map-cell ${intensity}`;
                    cell.textContent = count;
                    cell.title = `${days[j]}: ${count} incidents`;
                    cell.style.cursor = 'pointer';
                    
                    cell.addEventListener('click', function() {
                        alert(`Day: ${days[j]}\nIncidents: ${count}\nTime period: ${this.textContent.includes('high') ? 'High activity' : 'Normal activity'}`);
                    });
                    
                    grid.appendChild(cell);
                }
            }
        }
        
        // Quick question function
        function askQuickQuestion(question) {
            addMessage(question, 'user');
            setTimeout(() => {
                const response = getAIResponse(question);
                addMessage(response, 'ai');
                scrollToBottom();
            }, 800);
        }
        
        // Event listeners
        sendChatBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Make functions available globally
        window.askQuickQuestion = askQuickQuestion;
        
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
            const notification = document.createElement('div');
            notification.className = 'notification notification-success';
            notification.innerHTML = '<div class="notification-content"><i class="fas fa-check-circle"></i><span>All notifications marked as read</span></div>';
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        };
    });
    
    // Placeholder functions for other buttons
    function viewIncidentDetails(type) {
        alert(`Viewing details for ${type} incidents\n\nWould you like to:\n1. See location map\n2. View response teams\n3. Check timeline\n4. Generate report`);
    }
    
    function assignTeam(type) {
        alert(`Assigning team to ${type} incidents\n\nAvailable teams:\n• Emergency Response Unit\n• Fire Department\n• Medical Team\n• Police Unit\n\nSelect team to assign:`);
    }
    
    function viewCampaign(id) {
        alert(`Viewing campaign details for ID: ${id}\n\nLoading analytics dashboard...`);
    }
    
    function editCampaign(id) {
        alert(`Editing campaign with ID: ${id}\n\nOpening campaign editor...`);
    }
    
    function addNewCampaign() {
        alert('Adding new campaign\n\nOpening campaign creation wizard...');
    }
    
    function openExportModal() {
        alert('Opening export modal\n\nSelect report type:\n• PDF Report\n• Excel Data\n• CSV Export\n• Summary Dashboard');
    }
    
    function viewAllCampaigns() {
        alert('Viewing all campaigns\n\nRedirecting to campaigns dashboard...');
    }
    
    function remindMe(campaign) {
        alert(`Setting reminder for ${campaign}\n\nReminder set for tomorrow at 9:00 AM`);
    }
    
    function viewLiveStats(campaign) {
        alert(`Viewing live stats for ${campaign}\n\nLoading real-time analytics...`);
    }
    
    // Time filter functionality
    document.getElementById('timeFilter').addEventListener('change', function() {
        const period = this.value;
        let periodText = 'This Week';
        
        switch(period) {
            case 'today':
                periodText = 'Today';
                break;
            case 'month':
                periodText = 'This Month';
                break;
            case 'quarter':
                periodText = 'This Quarter';
                break;
        }
        
        // Update heat map period
        document.querySelector('.heat-map-period').textContent = periodText;
        
        // In a real app, this would fetch new data
        alert(`Loading ${periodText.toLowerCase()} data...`);
    });
    
    // Search functionality
    document.querySelector('.search-box input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                alert(`Searching for: "${query}"\n\nWould search incidents, campaigns, and reports in a real application.`);
            }
        }
    });
    </script>

</body>
</html>