<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get user data
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Analytics Manager';
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'create_report':
            case 'update_report':
                try {
                    $report_id = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $report_name = trim(filter_input(INPUT_POST, 'report_name', FILTER_SANITIZE_STRING) ?? '');
                    $report_type = filter_input(INPUT_POST, 'report_type', FILTER_SANITIZE_STRING) ?? 'custom';
                    $period_start = filter_input(INPUT_POST, 'period_start', FILTER_SANITIZE_STRING) ?? null;
                    $period_end = filter_input(INPUT_POST, 'period_end', FILTER_SANITIZE_STRING) ?? null;
                    
                    if (empty($report_name)) {
                        throw new Exception('Please enter a report name');
                    }
                    
                    if ($report_id) {
                        // Update existing report
                        $stmt = $pdo->prepare("
                            UPDATE generated_reports 
                            SET report_name = ?, report_type = ?, report_period_start = ?, report_period_end = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND generated_by = ?
                        ");
                        $stmt->execute([
                            $report_name, $report_type, $period_start, $period_end,
                            $report_id, $user_id
                        ]);
                        $success_message = 'Report updated successfully!';
                    } else {
                        // Create new report
                        $stmt = $pdo->prepare("
                            INSERT INTO generated_reports 
                            (report_name, report_type, report_period_start, report_period_end, generated_by)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $report_name, $report_type, $period_start, $period_end, $user_id
                        ]);
                        $report_id = $pdo->lastInsertId();
                        $success_message = 'Report created successfully!';
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
                
            case 'delete_report':
                try {
                    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
                    if ($report_id > 0) {
                        $stmt = $pdo->prepare("
                            DELETE FROM generated_reports 
                            WHERE id = ? AND generated_by = ?
                        ");
                        $stmt->execute([$report_id, $user_id]);
                        $success_message = 'Report deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error deleting report: ' . $e->getMessage();
                }
                break;
                
            case 'create_campaign':
            case 'update_campaign':
                try {
                    $campaign_id = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
                    $reach = intval($_POST['reach'] ?? 0);
                    $engagement = floatval($_POST['engagement'] ?? 0);
                    $roi = floatval($_POST['roi'] ?? 0);
                    $progress = intval($_POST['progress'] ?? 0);
                    $performance = filter_input(INPUT_POST, 'performance', FILTER_SANITIZE_STRING) ?? 'medium';
                    
                    if (empty($name)) {
                        throw new Exception('Please enter a campaign name');
                    }
                    
                    if ($campaign_id) {
                        // Update existing campaign
                        $stmt = $pdo->prepare("
                            UPDATE campaigns 
                            SET name = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([$name, $campaign_id, $user_id]);
                        
                        // Update metrics
                        $stmt = $pdo->prepare("
                            INSERT INTO campaign_metrics 
                            (campaign_id, date_recorded, reach, engagement_rate, roi, revenue)
                            VALUES (?, CURDATE(), ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            reach = VALUES(reach), engagement_rate = VALUES(engagement_rate), 
                            roi = VALUES(roi), revenue = VALUES(revenue)
                        ");
                        $stmt->execute([$campaign_id, $reach, $engagement, $roi, ($roi * 1000)]);
                        
                        $success_message = 'Campaign updated successfully!';
                    } else {
                        // Create new campaign
                        $stmt = $pdo->prepare("
                            INSERT INTO campaigns 
                            (name, description, type, status, start_date, end_date, budget, target_audience, created_by)
                            VALUES (?, '', 'safety', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 50000, 'General Public', ?)
                        ");
                        $stmt->execute([$name, $user_id]);
                        $campaign_id = $pdo->lastInsertId();
                        
                        // Add initial metrics
                        $stmt = $pdo->prepare("
                            INSERT INTO campaign_metrics 
                            (campaign_id, date_recorded, reach, engagement_rate, roi, revenue)
                            VALUES (?, CURDATE(), ?, ?, ?, ?)
                        ");
                        $stmt->execute([$campaign_id, $reach, $engagement, $roi, ($roi * 1000)]);
                        
                        $success_message = 'Campaign created successfully!';
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
                
            case 'delete_campaign':
                try {
                    $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
                    if ($campaign_id > 0) {
                        $stmt = $pdo->prepare("
                            DELETE FROM campaigns 
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([$campaign_id, $user_id]);
                        $success_message = 'Campaign deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error deleting campaign: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch analytics data
try {
    // Get all campaigns with their metrics
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.status, c.start_date, c.end_date,
               cm.reach, cm.engagement_rate, cm.roi,
               cs.overall_score, cs.engagement_score, cs.roi_score, cs.satisfaction_score
        FROM campaigns c
        LEFT JOIN campaign_metrics cm ON c.id = cm.campaign_id AND cm.date_recorded = (
            SELECT MAX(date_recorded) FROM campaign_metrics WHERE campaign_id = c.id
        )
        LEFT JOIN campaign_scores cs ON c.id = cs.campaign_id
        WHERE c.created_by = ? OR ? IN ('admin', 'manager')
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id, $user_role]);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate KPIs
    $total_reach = array_sum(array_column($campaigns, 'reach')) ?: 245000; // fallback to sample data
    $avg_engagement = count($campaigns) > 0 ? round((array_sum(array_column($campaigns, 'engagement_rate')) / count($campaigns)), 1) : 18.5;
    $avg_roi = count($campaigns) > 0 ? round((array_sum(array_column($campaigns, 'roi')) / count($campaigns)), 1) : 3.4;
    $avg_satisfaction = count($campaigns) > 0 && !empty(array_filter(array_column($campaigns, 'satisfaction_score'))) ? 
                        round((array_sum(array_column($campaigns, 'satisfaction_score')) / count(array_filter(array_column($campaigns, 'satisfaction_score')))), 1) : 4.2;
    
    // Format values for display
    $total_reach_formatted = $total_reach >= 1000 ? round($total_reach / 1000, 1) . 'K' : $total_reach;
    
    // Get channel analytics
    $stmt = $pdo->query("
        SELECT channel_name, SUM(impressions) as total_impressions, 
               SUM(clicks) as total_clicks, AVG(roi) as avg_roi, 
               AVG(engagement_rate) as avg_engagement_rate
        FROM channel_analytics 
        GROUP BY channel_name
        ORDER BY total_impressions DESC
    ");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get demographic data
    $stmt = $pdo->query("
        SELECT demographic_value, percentage 
        FROM campaign_demographics 
        WHERE demographic_category = 'age_group'
        ORDER BY percentage DESC
    ");
    $demographics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get generated reports
    $stmt = $pdo->prepare("
        SELECT id, report_name, report_type, report_period_start, report_period_end, 
               generated_at, status
        FROM generated_reports
        WHERE generated_by = ? OR ? IN ('admin', 'manager')
        ORDER BY generated_at DESC
    ");
    $stmt->execute([$user_id, $user_role]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest campaign scores
    $stmt = $pdo->query("
        SELECT overall_score 
        FROM campaign_scores 
        ORDER BY calculated_at DESC 
        LIMIT 1
    ");
    $latest_score = $stmt->fetchColumn() ?: 92;

} catch (PDOException $e) {
    $campaigns = [];
    $channels = [];
    $demographics = [];
    $reports = [];
    $latest_score = 92;
    $total_reach = 245000;
    $total_reach_formatted = '245K';
    $avg_engagement = 18.5;
    $avg_roi = 3.4;
    $avg_satisfaction = 4.2;
    error_log("Error fetching analytics: " . $e->getMessage());
}

// Helper functions
function format_report_type($type) {
    switch ($type) {
        case 'performance': return 'Performance';
        case 'financial': return 'Financial';
        case 'audience': return 'Audience';
        case 'comparative': return 'Comparative';
        default: return 'Custom';
    }
}

function format_date_range($start, $end) {
    if (!$start && !$end) return 'All Time';
    if (!$start) return 'Until ' . date('M j, Y', strtotime($end));
    if (!$end) return 'Since ' . date('M j, Y', strtotime($start));
    return date('M j, Y', strtotime($start)) . ' - ' . date('M j, Y', strtotime($end));
}

function get_performance_class($score) {
    if ($score >= 90) return 'score-excellent';
    if ($score >= 75) return 'score-good';
    if ($score >= 50) return 'score-average';
    return 'score-low';
}

function get_performance_label($score) {
    if ($score >= 90) return 'Excellent Performance';
    if ($score >= 75) return 'Good Performance';
    if ($score >= 50) return 'Average Performance';
    return 'Needs Improvement';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/analytics.css">
    <title>Campaign Analytics & Reports</title>
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
                    <a href="ContentRepository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="TargetGroupSegmentation.php" class="nav-link">
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
                    <a href="CampaignAnalyticsReports.php" class="nav-link active">
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
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
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
                <h2>Campaign Analytics & Reports</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search reports, metrics, campaigns...">
                    </div>
                    <div class="user-profile" onclick="showUserMenu()">
                        <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                        <div>
                            <div style="font-weight: 500;" id="username"><?php echo htmlspecialchars($user_name); ?></div>
                            <div style="font-size: 13px; color: var(--text-gray);" id="userrole"><?php echo htmlspecialchars($user_role); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Module Content -->
            <div class="module-header">
                <div>
                    <h1 class="module-title">Campaign Analytics & Reports</h1>
                    <p class="module-subtitle">Measure performance, generate insights, and create comprehensive reports</p>
                </div>
                <button class="btn btn-success" onclick="openReportWizard()">
                    <i class="fas fa-plus"></i> Generate Report
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active" onclick="applyFilter('all')">All Campaigns</div>
                <div class="filter-item" onclick="applyFilter('7days')">Last 7 Days</div>
                <div class="filter-item" onclick="applyFilter('30days')">Last 30 Days</div>
                <div class="filter-item" onclick="applyFilter('high')">High Performance</div>
                <div class="filter-item" onclick="applyFilter('attention')">Need Attention</div>
                <div class="filter-item" onclick="applyFilter('channel')">By Channel</div>
            </div>

            <div class="module-grid">
                <!-- Campaign Performance -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Campaign Performance</div>
                        <div class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                    <div class="campaign-list" id="campaignList">
                        <?php if (empty($campaigns)): ?>
                        <div class="no-data">No campaigns found. Create your first campaign!</div>
                        <?php else: ?>
                        <?php foreach ($campaigns as $campaign): ?>
                        <div class="campaign-item">
                            <div class="campaign-info">
                                <div class="campaign-name"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                <div class="campaign-stats">
                                    <div class="stat">
                                        <span class="stat-value"><?php echo number_format($campaign['reach'] ?? 0); ?></span>
                                        <span class="stat-label">Reach</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo number_format($campaign['engagement_rate'] ?? 0, 1); ?>%</span>
                                        <span class="stat-label">Engagement</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo number_format($campaign['roi'] ?? 0, 1); ?>x</span>
                                        <span class="stat-label">ROI</span>
                                    </div>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?php echo min(100, ($campaign['overall_score'] ?? 50)); ?>%"></div>
                                </div>
                            </div>
                            <div class="campaign-actions">
                                <button class="btn-sm" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-warning" style="width: 100%; margin-top: 15px;" onclick="openCampaignModal('create')">
                        <i class="fas fa-plus"></i> Add New Campaign
                    </button>
                </div>

                <!-- KPI Dashboard -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">KPI Dashboard</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="kpi-grid">
                        <div class="kpi-item">
                            <div class="kpi-value" id="totalReach"><?php echo $total_reach_formatted; ?></div>
                            <div class="kpi-label">Total Reach</div>
                            <div class="kpi-change positive-change">
                                <i class="fas fa-arrow-up"></i> <span id="reachChange">12%</span> from last month
                            </div>
                        </div>
                        <div class="kpi-item">
                            <div class="kpi-value" id="avgEngagement"><?php echo $avg_engagement; ?>%</div>
                            <div class="kpi-label">Avg. Engagement</div>
                            <div class="kpi-change positive-change">
                                <i class="fas fa-arrow-up"></i> <span id="engagementChange">3.2%</span> from last month
                            </div>
                        </div>
                        <div class="kpi-item">
                            <div class="kpi-value" id="avgROI"><?php echo $avg_roi; ?>x</div>
                            <div class="kpi-label">Avg. ROI</div>
                            <div class="kpi-change positive-change">
                                <i class="fas fa-arrow-up"></i> <span id="roiChange">0.8x</span> from last quarter
                            </div>
                        </div>
                        <div class="kpi-item">
                            <div class="kpi-value" id="satisfaction"><?php echo $avg_satisfaction; ?>★</div>
                            <div class="kpi-label">Satisfaction</div>
                            <div class="kpi-change positive-change">
                                <i class="fas fa-arrow-up"></i> <span id="satisfactionChange">0.3</span> from last month
                            </div>
                        </div>
                    </div>
                    <div class="chart-container small-chart">
                        <div class="viz-placeholder" onclick="openChartEditor('kpi')">
                            <i class="fas fa-chart-area"></i>
                            <h4>Performance Trends</h4>
                            <p>Monthly KPI visualization</p>
                            <button class="btn btn-sm" style="margin-top: 10px;">
                                <i class="fas fa-edit"></i> Customize
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Channel Analytics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Channel Analytics</div>
                        <div class="card-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                    </div>
                    <div class="channel-analytics" id="channelList">
                        <?php if (empty($channels)): ?>
                        <div class="no-data">No channel data available</div>
                        <?php else: ?>
                        <?php foreach ($channels as $channel): ?>
                        <div class="channel-item">
                            <div class="channel-name"><?php echo htmlspecialchars($channel['channel_name']); ?></div>
                            <div class="channel-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo number_format($channel['total_impressions'] ?? 0); ?></span>
                                    <span class="stat-label">Impressions</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo number_format($channel['avg_roi'] ?? 0, 1); ?>x</span>
                                    <span class="stat-label">ROI</span>
                                </div>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo min(100, (($channel['avg_engagement_rate'] ?? 0) * 10)); ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;" onclick="openChannelManager()">
                        <i class="fas fa-cog"></i> Manage Channels
                    </button>
                </div>

                <!-- ROI Analysis -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">ROI Analysis</div>
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="roi-analysis">
                        <div class="roi-item">
                            <div class="roi-label">Total Investment</div>
                            <div class="roi-value" id="totalInvestment">₱<?php echo number_format(array_sum(array_column($campaigns, 'budget')) / 1000, 1); ?>M</div>
                            <button class="btn-sm" onclick="editROI('investment')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="roi-item">
                            <div class="roi-label">Total Value Generated</div>
                            <div class="roi-value positive-roi" id="totalValue">₱<?php echo number_format((array_sum(array_column($campaigns, 'budget')) * $avg_roi) / 1000, 1); ?>M</div>
                            <button class="btn-sm" onclick="editROI('value')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="roi-item">
                            <div class="roi-label">Net ROI</div>
                            <div class="roi-value positive-roi" id="netROI"><?php echo round(($avg_roi - 1) * 100); ?>%</div>
                        </div>  
                        <div class="roi-item">
                            <div class="roi-label">Cost per Engagement</div>
                            <div class="roi-value" id="costPerEngagement">₱<?php echo number_format(50000 / (array_sum(array_column($campaigns, 'reach')) * ($avg_engagement / 100)), 2); ?></div>
                            <button class="btn-sm" onclick="editROI('cost')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container small-chart">
                        <div class="viz-placeholder" onclick="openChartEditor('roi')">
                            <i class="fas fa-chart-pie"></i>
                            <h4>ROI Distribution</h4>
                            <p>By campaign and channel</p>
                            <button class="btn btn-sm" style="margin-top: 10px;">
                                <i class="fas fa-sliders-h"></i> Adjust Metrics
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Demographic Insights -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Demographic Insights</div>
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="demographic-grid">
                        <?php if (empty($demographics)): ?>
                        <div class="demo-item">
                            <div class="demo-value" id="age25-44">45%</div>
                            <div class="demo-label">Age 25-44</div>
                            <button class="btn-sm" onclick="editDemo('age25-44')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="demo-item">
                            <div class="demo-value" id="age45-64">32%</div>
                            <div class="demo-label">Age 45-64</div>
                            <button class="btn-sm" onclick="editDemo('age45-64')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="demo-item">
                            <div class="demo-value" id="age18-24">18%</div>
                            <div class="demo-label">Age 18-24</div>
                            <button class="btn-sm" onclick="editDemo('age18-24')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="demo-item">
                            <div class="demo-value" id="age65">5%</div>
                            <div class="demo-label">Age 65+</div>
                            <button class="btn-sm" onclick="editDemo('age65')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <?php foreach ($demographics as $demo): ?>
                        <div class="demo-item">
                            <div class="demo-value"><?php echo $demo['percentage']; ?>%</div>
                            <div class="demo-label"><?php echo htmlspecialchars($demo['demographic_value']); ?></div>
                            <button class="btn-sm" onclick="editDemo('<?php echo urlencode($demo['demographic_value']); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;" onclick="openDemographicManager()">
                        <i class="fas fa-user-cog"></i> Manage Demographics
                    </button>
                </div>

                <!-- Quick Reports -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Reports</div>
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="reports-grid">
                        <div class="report-item" onclick="generateReport('performance')">
                            <i class="fas fa-trophy"></i>
                            <div>Performance Summary</div>
                        </div>
                        <div class="report-item" onclick="generateReport('financial')">
                            <i class="fas fa-chart-pie"></i>
                            <div>Financial Analysis</div>
                        </div>
                        <div class="report-item" onclick="generateReport('audience')">
                            <i class="fas fa-users"></i>
                            <div>Audience Insights</div>
                        </div>
                        <div class="report-item" onclick="generateReport('comparative')">
                            <i class="fas fa-balance-scale"></i>
                            <div>Comparative Analysis</div>
                        </div>
                    </div>
                    <div class="export-options">
                        <button class="export-btn" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="export-btn" onclick="exportReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="export-btn" onclick="exportReport('ppt')">
                            <i class="fas fa-file-powerpoint"></i> PPT
                        </button>
                        <button class="export-btn" onclick="shareReport()">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>
            </div>

            <!-- Generated Reports Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">Generated Reports</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="reports-table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Type</th>
                            <th>Period</th>
                            <th>Generated</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No reports generated yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['report_name']); ?></td>
                            <td><span class="report-type"><?php echo format_report_type($report['report_type']); ?></span></td>
                            <td><?php echo format_date_range($report['report_period_start'], $report['report_period_end']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($report['generated_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm" onclick="viewReport(<?php echo $report['id']; ?>)" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-sm" onclick="editReport(<?php echo $report['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-sm" onclick="deleteReport(<?php echo $report['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button class="btn" style="width: 100%; margin-top: 20px;" onclick="openReportManager()">
                    <i class="fas fa-cog"></i> Manage All Reports
                </button>
            </div>

            <!-- Advanced Analytics -->
            <div class="module-grid" style="margin-top: 30px;">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Predictive Analytics</div>
                        <div class="card-icon">
                            <i class="fas fa-crystal-ball"></i>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div class="viz-placeholder">
                            <i class="fas fa-chart-line"></i>
                            <h4>Campaign Performance Forecast</h4>
                            <p>Predictive modeling for future campaigns</p>
                            <button class="btn" style="margin-top: 15px;" onclick="runForecast()">
                                <i class="fas fa-play"></i> Run Forecast
                            </button>
                            <button class="btn" style="margin-top: 10px;" onclick="configureForecast()">
                                <i class="fas fa-sliders-h"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Performance Scorecard</div>
                        <div class="card-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="performance-score <?php echo get_performance_class($latest_score); ?>" id="performanceScore"><?php echo $latest_score; ?></div>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="font-weight: 600; font-size: 18px;" id="performanceRating"><?php echo get_performance_label($latest_score); ?></div>
                        <div style="color: var(--text-gray); font-size: 14px;" id="performancePercentile">Top 10% of all campaigns</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Engagement Score</div>
                            <div class="metric-trend trend-up">
                                <i class="fas fa-arrow-up"></i> <span id="engagementScoreChange">12%</span>
                            </div>
                        </div>
                        <div class="metric-value" id="engagementScore"><?php echo $campaigns[0]['engagement_score'] ?? 88; ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">ROI Score</div>
                            <div class="metric-trend trend-up">
                                <i class="fas fa-arrow-up"></i> <span id="roiScoreChange">24%</span>
                            </div>
                        </div>
                        <div class="metric-value" id="roiScore"><?php echo $campaigns[0]['roi_score'] ?? 95; ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-title">Satisfaction Score</div>
                            <div class="metric-trend trend-neutral">
                                <i class="fas fa-minus"></i> <span id="satisfactionScoreChange">2%</span>
                            </div>
                        </div>
                        <div class="metric-value" id="satisfactionScore"><?php echo $campaigns[0]['satisfaction_score'] ?? 85; ?></div>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;" onclick="recalculateScores()">
                        <i class="fas fa-calculator"></i> Recalculate Scores
                    </button>
                </div>
            </div>

            <!-- Custom Report Builder -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">Custom Report Builder</div>
                    <div class="card-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-gray);">Report Type</label>
                        <select id="reportType" style="width: 100%; padding: 10px; background-color: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; color: var(--white);">
                            <option value="performance">Performance Dashboard</option>
                            <option value="financial">Financial Summary</option>
                            <option value="audience">Audience Insights</option>
                            <option value="channel">Channel Analysis</option>
                            <option value="comparative">Comparative Report</option>
                            <option value="custom">Custom Report</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-gray);">Time Period</label>
                        <select id="timePeriod" style="width: 100%; padding: 10px; background-color: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; color: var(--white);">
                            <option value="7days">Last 7 Days</option>
                            <option value="30days">Last 30 Days</option>
                            <option value="quarter">Last Quarter</option>
                            <option value="year">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-gray);">Campaigns</label>
                        <select id="selectedCampaigns" style="width: 100%; padding: 10px; background-color: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; color: var(--white);">
                            <option value="all">All Campaigns</option>
                            <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-gray);">Metrics</label>
                        <select id="selectedMetrics" style="width: 100%; padding: 10px; background-color: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; color: var(--white);">
                            <option value="all">All Key Metrics</option>
                            <option value="engagement">Engagement Only</option>
                            <option value="financial">Financial Only</option>
                            <option value="audience">Audience Only</option>
                            <option value="custom">Custom Selection</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button class="btn" style="flex: 1;" onclick="previewCustomReport()">
                        <i class="fas fa-eye"></i> Preview Report
                    </button>
                    <button class="btn btn-success" style="flex: 1;" onclick="generateCustomReport()">
                        <i class="fas fa-file-export"></i> Generate & Export
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Campaign Modal -->
    <div id="campaignModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('campaignModal')">&times;</span>
            <h3 id="campaignModalTitle">Add New Campaign</h3>
            <form id="campaignForm" method="POST">
                <input type="hidden" name="action" value="create_campaign">
                <input type="hidden" id="campaignId" name="id">
                
                <div class="form-group">
                    <label>Campaign Name</label>
                    <input type="text" id="campaignName" name="name" required>
                </div>
                <div class="form-group">
                    <label>Reach</label>
                    <input type="number" id="campaignReach" name="reach" required>
                </div>
                <div class="form-group">
                    <label>Engagement (%)</label>
                    <input type="number" step="0.1" id="campaignEngagement" name="engagement" required>
                </div>
                <div class="form-group">
                    <label>ROI</label>
                    <input type="number" step="0.1" id="campaignROI" name="roi" required>
                </div>
                <div class="form-group">
                    <label>Progress (%)</label>
                    <input type="number" min="0" max="100" id="campaignProgress" name="progress" required>
                </div>
                <div class="form-group">
                    <label>Performance Level</label>
                    <select id="campaignPerformance" name="performance">
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" id="deleteCampaignBtn" style="display: none;" onclick="deleteCampaign()">Delete Campaign</button>
                    <button type="button" class="btn" onclick="closeModal('campaignModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Campaign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Modal -->
    <div id="reportModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('reportModal')">&times;</span>
            <h3>Manage Report</h3>
            <form id="reportForm" method="POST">
                <input type="hidden" name="action" value="create_report">
                <input type="hidden" id="reportId" name="id">
                
                <div class="form-group">
                    <label>Report Name</label>
                    <input type="text" id="reportName" name="report_name" required>
                </div>
                <div class="form-group">
                    <label>Report Type</label>
                    <select id="reportTypeSelect" name="report_type">
                        <option value="performance">Performance</option>
                        <option value="financial">Financial</option>
                        <option value="audience">Audience</option>
                        <option value="comparative">Comparative</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="reportStartDate" name="period_start">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="reportEndDate" name="period_end">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" id="deleteReportBtn" style="display: none;" onclick="deleteReportById()">Delete Report</button>
                    <button type="button" class="btn" onclick="closeModal('reportModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Element -->
    <div id="notification" class="notification" style="display: none;">
        <span id="notificationMessage"></span>
    </div>

    <script>
        // Campaign data from PHP
        const campaignsData = <?php echo json_encode($campaigns); ?>;
        const reportsData = <?php echo json_encode($reports); ?>;
        
        // Modal management
        function openCampaignModal(mode, id = null) {
            const modal = document.getElementById('campaignModal');
            const title = document.getElementById('campaignModalTitle');
            const form = document.getElementById('campaignForm');
            const submitBtn = form.querySelector('.btn-success');
            const deleteBtn = document.getElementById('deleteCampaignBtn');
            
            if (mode === 'create') {
                title.textContent = 'Add New Campaign';
                form.action.value = 'create_campaign';
                form.reset();
                document.getElementById('campaignId').value = '';
                deleteBtn.style.display = 'none';
            } else if (mode === 'edit' && id) {
                const campaign = campaignsData.find(c => c.id == id);
                if (campaign) {
                    title.textContent = 'Edit Campaign';
                    form.action.value = 'update_campaign';
                    document.getElementById('campaignId').value = campaign.id;
                    document.getElementById('campaignName').value = campaign.name || '';
                    document.getElementById('campaignReach').value = campaign.reach || 0;
                    document.getElementById('campaignEngagement').value = campaign.engagement_rate || 0;
                    document.getElementById('campaignROI').value = campaign.roi || 0;
                    document.getElementById('campaignProgress').value = campaign.overall_score || 0;
                    document.getElementById('campaignPerformance').value = 'medium'; // default
                    deleteBtn.style.display = 'inline-block';
                }
            }
            
            modal.style.display = 'flex';
        }
        
        function openReportModal(mode, id = null) {
            const modal = document.getElementById('reportModal');
            const form = document.getElementById('reportForm');
            const deleteBtn = document.getElementById('deleteReportBtn');
            
            if (mode === 'create') {
                document.querySelector('#reportModal h3').textContent = 'Create New Report';
                form.action.value = 'create_report';
                form.reset();
                document.getElementById('reportId').value = '';
                deleteBtn.style.display = 'none';
            } else if (mode === 'edit' && id) {
                const report = reportsData.find(r => r.id == id);
                if (report) {
                    document.querySelector('#reportModal h3').textContent = 'Edit Report';
                    form.action.value = 'update_report';
                    document.getElementById('reportId').value = report.id;
                    document.getElementById('reportName').value = report.report_name;
                    document.getElementById('reportTypeSelect').value = report.report_type;
                    document.getElementById('reportStartDate').value = report.report_period_start || '';
                    document.getElementById('reportEndDate').value = report.report_period_end || '';
                    deleteBtn.style.display = 'inline-block';
                }
            }
            
            modal.style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editCampaign(id) {
            openCampaignModal('edit', id);
        }
        
        function deleteCampaign() {
            // This will be handled by the form submission
            const campaignId = document.getElementById('campaignId').value;
            if (campaignId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_campaign">
                    <input type="hidden" name="campaign_id" value="${campaignId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editReport(id) {
            openReportModal('edit', id);
        }
        
        function deleteReport(id) {
            if (confirm('Are you sure you want to delete this report?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_report">
                    <input type="hidden" name="report_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteReportById() {
            const reportId = document.getElementById('reportId').value;
            if (reportId) {
                deleteReport(reportId);
            }
        }
        
        // Other functions
        function openReportWizard() {
            openReportModal('create');
        }
        
        function applyFilter(filter) {
            console.log('Applying filter:', filter);
            // In a real implementation, this would filter the data
            showNotification(`Applied filter: ${filter}`, 'info');
        }
        
        function openChannelManager() {
            showNotification('Opening channel manager...', 'info');
        }
        
        function editROI(type) {
            showNotification(`Editing ${type} value...`, 'info');
        }
        
        function editDemo(ageGroup) {
            showNotification(`Editing ${decodeURIComponent(ageGroup)} demographic...`, 'info');
        }
        
        function openDemographicManager() {
            showNotification('Opening demographic manager...', 'info');
        }
        
        function generateReport(type) {
            showNotification(`Generating ${type} report...`, 'info');
        }
        
        function exportReport(format) {
            showNotification(`Exporting report as ${format.toUpperCase()}...`, 'info');
        }
        
        function shareReport() {
            showNotification('Preparing report for sharing...', 'info');
        }
        
        function viewReport(id) {
            showNotification(`Viewing report ID: ${id}`, 'info');
        }
        
        function openReportManager() {
            showNotification('Opening report manager...', 'info');
        }
        
        function runForecast() {
            showNotification('Running forecast analysis...', 'info');
        }
        
        function configureForecast() {
            showNotification('Configuring forecast parameters...', 'info');
        }
        
        function recalculateScores() {
            showNotification('Recalculating campaign scores...', 'info');
        }
        
        function previewCustomReport() {
            showNotification('Previewing custom report...', 'info');
        }
        
        function generateCustomReport() {
            showNotification('Generating custom report...', 'info');
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const messageElement = document.getElementById('notificationMessage');
            
            messageElement.textContent = message;
            notification.style.display = 'block';
            
            // Set color based on type
            notification.style.backgroundColor = type === 'error' ? '#dc3545' : 
                                               type === 'warning' ? '#ffc107' : 
                                               '#28a745';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const campaignModal = document.getElementById('campaignModal');
            const reportModal = document.getElementById('reportModal');
            
            if (event.target === campaignModal) {
                campaignModal.style.display = 'none';
            }
            
            if (event.target === reportModal) {
                reportModal.style.display = 'none';
            }
        }
    </script>

    <script src="../Scripts/utils.js"></script>
    <script src="../Scripts/mod6.js"></script>
    
    <style>
        /* Additional styles for the analytics module */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: #2D2D2D;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            padding: 20px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: var(--dark-gray);
            color: var(--white);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--text-gray);
            font-style: italic;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .report-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            background-color: var(--medium-gray);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .status-generated {
            background-color: var(--success);
        }
        
        .status-draft {
            background-color: var(--warning);
        }
        
        .status-shared {
            background-color: var(--accent);
        }
        
        .score-excellent { color: #28a745; font-weight: bold; }
        .score-good { color: #20c997; font-weight: bold; }
        .score-average { color: #ffc107; font-weight: bold; }
        .score-low { color: #dc3545; font-weight: bold; }
        
        .positive-change {
            color: var(--success);
        }
        
        .negative-change {
            color: var(--danger);
        }
    </style>
</body>
</html>