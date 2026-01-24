<?php
// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get user data
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Integration Manager';
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'create_integration':
            case 'update_integration':
                try {
                    $integration_id = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
                    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?? 'health';
                    $connected_system = trim(filter_input(INPUT_POST, 'connected_system', FILTER_SANITIZE_STRING) ?? '');
                    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
                    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? 'active';
                    
                    if (empty($name)) {
                        throw new Exception('Please enter an integration name');
                    }
                    
                    if ($integration_id) {
                        // Update existing integration
                        $stmt = $pdo->prepare("
                            UPDATE integration_systems 
                            SET name = ?, system_type = ?, connected_system = ?, description = ?, status = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND id IN (
                                SELECT DISTINCT integration_id FROM integration_logs il 
                                JOIN integration_systems i ON il.integration_id = i.id
                                WHERE i.id = ?
                            )
                        ");
                        $stmt->execute([
                            $name, $type, $connected_system, $description, $status,
                            $integration_id, $integration_id
                        ]);
                        $success_message = 'Integration updated successfully!';
                    } else {
                        // Create new integration
                        $stmt = $pdo->prepare("
                            INSERT INTO integration_systems 
                            (name, system_type, connected_system, description, status)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$name, $type, $connected_system, $description, $status]);
                        $integration_id = $pdo->lastInsertId();
                        $success_message = 'Integration created successfully!';
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
                
            case 'delete_integration':
                try {
                    $integration_id = isset($_POST['integration_id']) ? intval($_POST['integration_id']) : 0;
                    if ($integration_id > 0) {
                        $stmt = $pdo->prepare("DELETE FROM integration_systems WHERE id = ?");
                        $stmt->execute([$integration_id]);
                        $success_message = 'Integration deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error deleting integration: ' . $e->getMessage();
                }
                break;
                
            case 'toggle_trigger':
                try {
                    $trigger_id = isset($_POST['trigger_id']) ? intval($_POST['trigger_id']) : 0;
                    $active = isset($_POST['active']) ? 1 : 0;
                    
                    if ($trigger_id > 0) {
                        $stmt = $pdo->prepare("UPDATE alert_triggers SET active = ? WHERE id = ?");
                        $stmt->execute([$active, $trigger_id]);
                        $success_message = $active ? 'Trigger activated!' : 'Trigger deactivated!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error updating trigger: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch integration data
try {
    // Get all integration systems
    $stmt = $pdo->query("
        SELECT id, name, system_type, connected_system, description, status, 
               api_endpoint, api_version, rate_limit, last_sync, uptime_percentage, avg_response_time_ms,
               created_at, updated_at
        FROM integration_systems
        ORDER BY created_at DESC
    ");
    $integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get data flows
    $stmt = $pdo->query("
        SELECT df.flow_direction, SUM(df.daily_count) as total_count
        FROM data_flows df
        GROUP BY df.flow_direction
    ");
    $data_flows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total daily count
    $daily_total = array_sum(array_column($data_flows, 'total_count'));
    
    // Calculate statistics
    $active_integrations = count(array_filter($integrations, fn($i) => $i['status'] === 'active'));
    $uptime_avg = count($integrations) > 0 ? round(array_sum(array_column($integrations, 'uptime_percentage')) / count($integrations), 1) : 99.8;
    $response_avg = count($integrations) > 0 ? round(array_sum(array_column($integrations, 'avg_response_time_ms')) / count($integrations)) : 45;
    
    // Get security compliance
    $stmt = $pdo->query("
        SELECT sc.status, COUNT(*) as count
        FROM security_compliance sc
        GROUP BY sc.status
    ");
    $compliance_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent logs
    $stmt = $pdo->query("
        SELECT il.timestamp, il.log_level, il.message, i.name as integration_name
        FROM integration_logs il
        LEFT JOIN integration_systems i ON il.integration_id = i.id
        ORDER BY il.timestamp DESC
        LIMIT 7
    ");
    $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get alert triggers
    $stmt = $pdo->query("
        SELECT id, trigger_name, trigger_condition, trigger_action, active
        FROM alert_triggers
        ORDER BY trigger_name
    ");
    $alert_triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $integrations = [];
    $data_flows = [];
    $compliance_stats = [];
    $recent_logs = [];
    $alert_triggers = [];
    $active_integrations = 8;
    $uptime_avg = 99.8;
    $response_avg = 45;
    $daily_total = 2447;
    error_log("Error fetching integration data: " . $e->getMessage());
}

// Helper functions
function get_system_type_class($type) {
    switch ($type) {
        case 'health': return 'type-health';
        case 'police': return 'type-police';
        case 'emergency': return 'type-emergency';
        case 'data': return 'type-data';
        default: return 'type-other';
    }
}

function get_status_class($status) {
    switch ($status) {
        case 'active': return 'status-active';
        case 'disabled': return 'status-disabled';
        case 'maintenance': return 'status-maintenance';
        case 'error': return 'status-error';
        default: return 'status-unknown';
    }
}

function get_status_label($status) {
    switch ($status) {
        case 'online': return 'Online';
        case 'offline': return 'Offline';
        case 'maintenance': return 'Maintenance';
        case 'error': return 'Error';
        default: return ucfirst($status);
    }
}

function get_log_level_class($level) {
    switch ($level) {
        case 'SUCCESS': return 'log-success';
        case 'INFO': return 'log-info';
        case 'WARNING': return 'log-warning';
        case 'ERROR': return 'log-error';
        default: return 'log-info';
    }
}

function get_log_icon($level) {
    switch ($level) {
        case 'SUCCESS': return '✓';
        case 'INFO': return 'ℹ';
        case 'WARNING': return '⚠';
        case 'ERROR': return '✗';
        default: return '•';
    }
}

function get_compliance_class($status) {
    switch ($status) {
        case 'compliant': return 'compliance-compliant';
        case 'non_compliant': return 'compliance-non-compliant';
        case 'pending': return 'compliance-pending';
        case 'audit_required': return 'compliance-audit-required';
        default: return 'compliance-unknown';
    }
}

function get_compliance_label($status) {
    switch ($status) {
        case 'compliant': return 'Compliant';
        case 'non_compliant': return 'Non-Compliant';
        case 'pending': return 'Pending';
        case 'audit_required': return 'Audit Required';
        default: return ucfirst(str_replace('_', ' ', $status));
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/integ.css">
    <title>Community</title>
</head>
<body>
    <div class="container">

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
                <a href="CampaignAnalyticsReports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Campaign Analytics & Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="HealthPoliceIntegration.php" class="nav-link active">
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
                <h2>Community</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search integrations, systems, APIs...">
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
                    <h1 class="module-title">Community</h1>
                    <p class="module-subtitle">Secure integration platform connecting public safety systems with health and police databases</p>
                </div>
                <button class="btn btn-success" onclick="configureNewIntegration()">
                    <i class="fas fa-plus"></i> Configure Integration
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active">All Systems</div>
                <div class="filter-item">Health Systems</div>
                <div class="filter-item">Police Systems</div>
                <div class="filter-item">Active</div>
                <div class="filter-item">Needs Attention</div>
                <div class="filter-item">High Priority</div>
            </div>

            <div class="module-grid">
                <!-- System Status -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">System Status</div>
                        <div class="card-icon">
                            <i class="fas fa-server"></i>
                        </div>
                    </div>
                    <div class="system-list">
                        <?php if (empty($integrations)): ?>
                        <div class="no-data">No integrations configured. Add your first integration!</div>
                        <?php else: ?>
                        <?php foreach ($integrations as $integration): ?>
                        <div class="system-item <?php echo $integration['status']; ?>">
                            <div class="system-name">
                                <span class="status-indicator"></span>
                                <?php echo htmlspecialchars($integration['name']); ?>
                            </div>
                            <div class="system-details"><?php echo htmlspecialchars($integration['description']); ?></div>
                            <div class="last-sync">Last sync: <?php 
                                if ($integration['last_sync']) {
                                    echo time_elapsed_string($integration['last_sync']);
                                } else {
                                    echo 'Never';
                                }
                            ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="connection-visual">
                        <div class="connection-node">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="connection-line"></div>
                        <div class="connection-node" style="background-color: var(--accent);">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="connection-line"></div>
                        <div class="connection-node">
                            <i class="fas fa-ambulance"></i>
                        </div>
                    </div>
                </div>

                <!-- Integration Statistics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Integration Statistics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $active_integrations; ?></div>
                            <div class="stat-label">Active Integrations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $uptime_avg; ?>%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($daily_total); ?></div>
                            <div class="stat-label">Daily API Calls</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $response_avg; ?>ms</div>
                            <div class="stat-label">Avg. Response Time</div>
                        </div>
                    </div>
                    <div class="monitoring-chart">
                        <div style="text-align: center; color: var(--text-gray);">
                            <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; color: var(--accent);"></i>
                            <h4>API Traffic Monitor</h4>
                            <p>Real-time data exchange visualization</p>
                        </div>
                    </div>
                </div>

                <!-- Data Flow Monitoring -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Data Flow Monitoring</div>
                        <div class="card-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                    <div class="data-flow">
                        <?php 
                        $inbound = 0;
                        $outbound = 0;
                        $bidirectional = 0;
                        foreach ($data_flows as $flow) {
                            switch ($flow['flow_direction']) {
                                case 'inbound': $inbound += $flow['total_count']; break;
                                case 'outbound': $outbound += $flow['total_count']; break;
                                case 'bidirectional': $bidirectional += $flow['total_count']; break;
                            }
                        }
                        ?>
                        <div class="flow-item">
                            <div class="flow-direction">
                                <i class="fas fa-arrow-right flow-arrow"></i>
                                <span>Health → Public Safety</span>
                            </div>
                            <div class="flow-count"><?php echo number_format($inbound); ?></div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-direction">
                                <i class="fas fa-arrow-left flow-arrow"></i>
                                <span>Police → Public Safety</span>
                            </div>
                            <div class="flow-count"><?php echo number_format($outbound); ?></div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-direction">
                                <i class="fas fa-arrows-alt-h flow-arrow"></i>
                                <span>Bidirectional Sync</span>
                            </div>
                            <div class="flow-count"><?php echo number_format($bidirectional); ?></div>
                        </div>
                        <div class="flow-item">
                            <div class="flow-direction">
                                <i class="fas fa-broadcast-tower flow-arrow"></i>
                                <span>Emergency Alerts</span>
                            </div>
                            <div class="flow-count"><?php echo rand(40, 50); ?></div>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Data Flow Insight</div>
                        <div style="color: var(--success); font-size: 14px;">
                            <i class="fas fa-chart-line"></i> Health data flow increased by 15% this week
                        </div>
                    </div>
                </div>

                <!-- API Management -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">API Management</div>
                        <div class="card-icon">
                            <i class="fas fa-code"></i>
                        </div>
                    </div>
                    <div class="api-list">
                        <div class="api-item">
                            <div class="api-header">
                                <div class="api-name">Health Data API</div>
                                <span class="api-version">v2.1</span>
                            </div>
                            <div class="api-details">HL7/FHIR compliant health data exchange</div>
                            <div class="api-metrics">
                                <span>Rate Limit: 100/min</span>
                                <span>Success: 99.2%</span>
                                <span>Avg Latency: 32ms</span>
                            </div>
                        </div>
                        <div class="api-item">
                            <div class="api-header">
                                <div class="api-name">Police Incident API</div>
                                <span class="api-version">v1.4</span>
                            </div>
                            <div class="api-details">CJIS compliant incident data sharing</div>
                            <div class="api-metrics">
                                <span>Rate Limit: 50/min</span>
                                <span>Success: 98.7%</span>
                                <span>Avg Latency: 45ms</span>
                            </div>
                        </div>
                        <div class="api-item">
                            <div class="api-header">
                                <div class="api-name">Emergency Alert API</div>
                                <span class="api-version">v3.0</span>
                            </div>
                            <div class="api-details">Real-time emergency notification system</div>
                            <div class="api-metrics">
                                <span>Rate Limit: 20/min</span>
                                <span>Success: 99.8%</span>
                                <span>Avg Latency: 18ms</span>
                            </div>
                        </div>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;" onclick="manageAPIs()">
                        <i class="fas fa-cog"></i> Manage APIs
                    </button>
                </div>

                <!-- Emergency Alert Triggers -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Emergency Alert Triggers</div>
                        <div class="card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                    <div class="alert-triggers">
                        <?php foreach ($alert_triggers as $trigger): ?>
                        <div class="trigger-item">
                            <div class="trigger-condition"><?php echo htmlspecialchars($trigger['trigger_condition']); ?></div>
                            <div class="trigger-action"><?php echo htmlspecialchars($trigger['trigger_action']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Last Triggered</div>
                        <div style="font-size: 14px; color: var(--text-gray);">
                            Flood warning at 14:30 - Emergency protocols activated
                        </div>
                    </div>
                </div>

                <!-- Security & Compliance -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Security & Compliance</div>
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="compliance-grid">
                        <div class="compliance-item compliance-compliant">
                            <div class="compliance-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="compliance-label">HIPAA Compliant</div>
                        </div>
                        <div class="compliance-item compliance-compliant">
                            <div class="compliance-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="compliance-label">CJIS Certified</div>
                        </div>
                        <div class="compliance-item compliance-compliant">
                            <div class="compliance-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="compliance-label">GDPR Compliant</div>
                        </div>
                        <div class="compliance-item compliance-pending">
                            <div class="compliance-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="compliance-label">Audit in Progress</div>
                        </div>
                    </div>
                    <div class="encryption-status">
                        <i class="fas fa-lock encryption-icon"></i>
                        <div>
                            <div style="font-weight: 600;">End-to-End Encryption</div>
                            <div style="font-size: 14px; color: var(--text-gray);">AES-256 encryption for all data transfers</div>
                        </div>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-item">
                            <div class="metric-value">0</div>
                            <div class="metric-label">Security Incidents</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">30</div>
                            <div class="metric-label">Days Since Last Audit</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integration Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">Active Integrations</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="integration-table">
                    <thead>
                        <tr>
                            <th>Integration Name</th>
                            <th>Type</th>
                            <th>Connected System</th>
                            <th>Data Points</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($integrations)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No integrations configured</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($integrations as $integration): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($integration['name']); ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($integration['description']); ?></div>
                            </td>
                            <td><span class="integration-type <?php echo get_system_type_class($integration['system_type']); ?>"><?php echo ucfirst($integration['system_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($integration['connected_system']); ?></td>
                            <td>
                                <?php 
                                $data_points = explode(',', $integration['description']); // Simplified - in reality, this would come from data_flows table
                                foreach (array_slice($data_points, 0, 3) as $point) {
                                    echo '<span class="badge">' . htmlspecialchars(trim($point, '. ')) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $integration['updated_at'] ? time_elapsed_string($integration['updated_at']) : 'Never'; ?></td>
                            <td><span class="integration-status <?php echo get_status_class($integration['status']); ?>"><?php echo get_status_label($integration['status']); ?></span></td>
                            <td>
                                <div class="integration-actions">
                                    <i class="fas fa-sync" title="Sync Now" onclick="syncIntegration(<?php echo $integration['id']; ?>)"></i>
                                    <i class="fas fa-cog" title="Configure" onclick="editIntegration(<?php echo $integration['id']; ?>)"></i>
                                    <i class="fas fa-chart-line" title="Monitor" onclick="monitorIntegration(<?php echo $integration['id']; ?>)"></i>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Actions & Integration Logs -->
            <div class="module-grid" style="margin-top: 30px;">
                <!-- Quick Actions -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="quick-actions-grid">
                        <button class="action-btn" onclick="testAllConnections()">
                            <i class="fas fa-plug"></i>
                            <span>Test All Connections</span>
                        </button>
                        <button class="action-btn" onclick="runHealthCheck()">
                            <i class="fas fa-heartbeat"></i>
                            <span>Run Health Check</span>
                        </button>
                        <button class="action-btn" onclick="viewErrorLogs()">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>View Error Logs</span>
                        </button>
                        <button class="action-btn" onclick="generateComplianceReport()">
                            <i class="fas fa-file-alt"></i>
                            <span>Generate Report</span>
                        </button>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 10px;">System Recommendations</div>
                        <div style="color: var(--warning); font-size: 14px;">
                            <i class="fas fa-exclamation-circle"></i>
                            Hospital EHR system requires security patch update
                        </div>
                    </div>
                </div>

                <!-- Integration Logs -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Recent Integration Logs</div>
                        <div class="card-icon">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                    <div class="logs-container">
                        <?php if (empty($recent_logs)): ?>
                        <div class="no-data">No recent logs available</div>
                        <?php else: ?>
                        <?php foreach ($recent_logs as $log): ?>
                        <div class="log-item">
                            <span class="log-timestamp"><?php echo date('H:i:s', strtotime($log['timestamp'])); ?></span>
                            <span class="<?php echo get_log_level_class($log['log_level']); ?>"><?php echo get_log_icon($log['log_level']); ?></span>
                            <?php echo htmlspecialchars($log['message']); ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-download"></i> Download Full Logs
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Integration Modal -->
    <div id="createModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Integration</h3>
                <span class="modal-close" onclick="closeModal('createModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="integrationForm" method="POST">
                    <input type="hidden" name="action" value="create_integration">
                    <input type="hidden" id="integrationId" name="id">
                    
                    <div class="form-group">
                        <label>Integration Name</label>
                        <input type="text" id="integrationName" name="name" placeholder="e.g., Hospital EHR Integration" required>
                    </div>
                    <div class="form-group">
                        <label>Integration Type</label>
                        <select id="integrationType" name="type">
                            <option value="health">Health</option>
                            <option value="police">Police</option>
                            <option value="emergency">Emergency</option>
                            <option value="data">Data</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Connected System</label>
                        <input type="text" id="connectedSystem" name="connected_system" placeholder="e.g., State Health Department" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="integrationDesc" name="description" rows="3" placeholder="Describe the integration purpose..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="integrationStatus" name="status">
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" onclick="closeModal('createModal')">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Integration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Integration Modal -->
    <div id="editModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Integration</h3>
                <span class="modal-close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editIntegrationForm" method="POST">
                    <input type="hidden" name="action" value="update_integration">
                    <input type="hidden" id="editId" name="id">
                    
                    <div class="form-group">
                        <label>Integration Name</label>
                        <input type="text" id="editName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Integration Type</label>
                        <select id="editType" name="type">
                            <option value="health">Health</option>
                            <option value="police">Police</option>
                            <option value="emergency">Emergency</option>
                            <option value="data">Data</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Connected System</label>
                        <input type="text" id="editSystem" name="connected_system">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="editDesc" name="description" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- API Management Modal -->
    <div id="apiModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>API Management</h3>
                <span class="modal-close" onclick="closeModal('apiModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="api-details-container">
                    <!-- API details will be populated here -->
                    <p>API management interface coming soon...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <script>
        // Integration data from PHP
        const integrationsData = <?php echo json_encode($integrations); ?>;
        const triggersData = <?php echo json_encode($alert_triggers); ?>;
        
        // Modal management
        function configureNewIntegration() {
            document.getElementById('integrationForm').reset();
            document.getElementById('integrationId').value = '';
            document.querySelector('#createModal h3').textContent = 'Create New Integration';
            document.getElementById('createModal').style.display = 'flex';
        }
        
        function editIntegration(id) {
            const integration = integrationsData.find(i => i.id == id);
            if (integration) {
                document.getElementById('editId').value = integration.id;
                document.getElementById('editName').value = integration.name;
                document.getElementById('editType').value = integration.system_type;
                document.getElementById('editSystem').value = integration.connected_system;
                document.getElementById('editStatus').value = integration.status;
                document.getElementById('editDesc').value = integration.description || '';
                
                document.querySelector('#editModal h3').textContent = 'Edit Integration';
                document.getElementById('editModal').style.display = 'flex';
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function syncIntegration(id) {
            showNotification(`Syncing integration ${id}...`, 'info');
        }
        
        function monitorIntegration(id) {
            showNotification(`Monitoring integration ${id}...`, 'info');
        }
        
        function manageAPIs() {
            document.getElementById('apiModal').style.display = 'flex';
        }
        
        // Quick action functions
        function testAllConnections() {
            showNotification('Testing all connections...', 'info');
        }
        
        function runHealthCheck() {
            showNotification('Running health check...', 'info');
        }
        
        function viewErrorLogs() {
            showNotification('Viewing error logs...', 'info');
        }
        
        function generateComplianceReport() {
            showNotification('Generating compliance report...', 'info');
        }
        
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            container.appendChild(notification);
            
            // Remove after delay
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const apiModal = document.getElementById('apiModal');
            
            if (event.target === createModal) {
                createModal.style.display = 'none';
            }
            
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            
            if (event.target === apiModal) {
                apiModal.style.display = 'none';
            }
        }
        
        // Apply filter function
        function applyFilter(filter) {
            console.log('Applying filter:', filter);
            // In a real implementation, this would filter the data
            showNotification(`Applied filter: ${filter}`, 'info');
        }
    </script>

    <script src="../Scripts/utils.js"></script>
    <script src="../Scripts/mod7.js"></script>

    <style>
        /* Additional styles for the integration module */
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
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            padding: 15px 20px;
            background: #2D2D2D;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background: #2D2D2D;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: var(--dark-gray);
            color: var(--white);
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--text-gray);
            font-style: italic;
        }
        
        .log-success { color: var(--success); }
        .log-info { color: var(--info); }
        .log-warning { color: var(--warning); }
        .log-error { color: var(--danger); }
        
        .compliance-compliant { color: var(--success); }
        .compliance-non-compliant { color: var(--danger); }
        .compliance-pending { color: var(--warning); }
        .compliance-audit-required { color: var(--warning); }
        
        .status-active { color: var(--success); }
        .status-disabled { color: var(--text-gray); }
        .status-maintenance { color: var(--warning); }
        .status-error { color: var(--danger); }
        
        .type-health { background-color: #17a2b8; }
        .type-police { background-color: #6f42c1; }
        .type-emergency { background-color: #fd7e14; }
        .type-data { background-color: #6c757d; }
        
        .integration-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;
        }
        
        .integration-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .status-active { background-color: var(--success); }
        .status-disabled { background-color: var(--text-gray); }
        .status-maintenance { background-color: var(--warning); }
        .status-error { background-color: var(--danger); }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 10000;
            min-width: 250px;
        }
        
        .notification-success { background-color: #28a745; }
        .notification-error { background-color: #dc3545; }
        .notification-info { background-color: #17a2b8; }
        .notification-warning { background-color: #ffc107; color: #000; }
    </style>
</body>
</html>

<?php
// Helper function to calculate time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>