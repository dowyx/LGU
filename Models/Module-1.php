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
$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_role = $_SESSION['user_role'] ?? 'staff';
$is_admin_or_manager = in_array($user_role, ['admin', 'manager']);

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_campaign':
                case 'update_campaign':
                    try {
                        $campaign_id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
                        $name = trim($_POST['name'] ?? '');
                        $description = trim($_POST['description'] ?? '');
                        $start_date = trim($_POST['startDate'] ?? '');
                        $end_date = trim($_POST['endDate'] ?? '');
                        $type = $_POST['type'] ?? 'safety';
                        $status = $_POST['status'] ?? 'draft';
                        $budget = floatval($_POST['budget'] ?? 0);
                        $target_audience = trim($_POST['targetAudience'] ?? '');
                        $milestones_input = trim($_POST['milestones'] ?? '');

                        // Validation
                        if (empty($name) || empty($start_date) || empty($end_date)) {
                            throw new Exception('Name, start date, and end date are required.');
                        }

                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                            throw new Exception('Invalid date format. Use YYYY-MM-DD.');
                        }

                        if (strtotime($end_date) < strtotime($start_date)) {
                            throw new Exception('End date must be after start date.');
                        }

                        if ($budget < 0) {
                            throw new Exception('Budget cannot be negative.');
                        }

                        $pdo->beginTransaction();

                        if ($campaign_id) {
                            // Update - check ownership
                            $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND (created_by = ? OR ? IN ('admin','manager'))");
                            $stmt->execute([$campaign_id, $user_id, $user_role]);
                            if (!$stmt->fetch()) {
                                throw new Exception('You do not have permission to edit this campaign.');
                            }

                            $stmt = $pdo->prepare("
                                UPDATE campaigns 
                                SET name = ?, description = ?, type = ?, status = ?, 
                                    start_date = ?, end_date = ?, budget = ?, target_audience = ?,
                                    updated_at = CURRENT_TIMESTAMP
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $name, $description, $type, $status, 
                                $start_date, $end_date, $budget, $target_audience,
                                $campaign_id
                            ]);
                            $success_message = 'Campaign updated successfully!';
                        } else {
                            // Create new
                            $stmt = $pdo->prepare("
                                INSERT INTO campaigns 
                                (name, description, type, status, start_date, end_date, 
                                 budget, target_audience, created_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $name, $description, $type, $status, 
                                $start_date, $end_date, $budget, $target_audience, $user_id
                            ]);
                            $campaign_id = $pdo->lastInsertId();
                            $success_message = 'Campaign created successfully!';
                        }

                        // Handle milestones (one per line format recommended)
                        if (!empty($milestones_input)) {
                            // Clear existing milestones
                            $stmt = $pdo->prepare("DELETE FROM campaign_milestones WHERE campaign_id = ?");
                            $stmt->execute([$campaign_id]);

                            $lines = explode("\n", $milestones_input);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (empty($line)) continue;

                                if (preg_match('/^(.+?):\s*(\d{4}-\d{2}-\d{2})$/', $line, $matches)) {
                                    $milestone_name = trim($matches[1]);
                                    $milestone_date = trim($matches[2]);

                                    $stmt = $pdo->prepare("
                                        INSERT INTO campaign_milestones (campaign_id, name, target_date)
                                        VALUES (?, ?, ?)
                                    ");
                                    $stmt->execute([$campaign_id, $milestone_name, $milestone_date]);
                                }
                            }
                        }

                        $pdo->commit();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error_message = $e->getMessage();
                    }
                    break;

                case 'delete_campaign':
                    try {
                        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
                        if ($campaign_id <= 0) {
                            throw new Exception('Invalid campaign ID');
                        }

                        $pdo->beginTransaction();

                        // Permission check
                        $stmt = $pdo->prepare("
                            SELECT id FROM campaigns 
                            WHERE id = ? AND (created_by = ? OR ? IN ('admin','manager'))
                        ");
                        $stmt->execute([$campaign_id, $user_id, $user_role]);
                        if (!$stmt->fetch()) {
                            throw new Exception('You do not have permission to delete this campaign.');
                        }

                        // Delete from all related child tables
                        $child_tables = [
                            'campaign_milestones'           => 'campaign_id',
                            'campaign_resources'            => 'campaign_id',
                            'campaign_team_members'         => 'campaign_id',
                            'campaign_documents'            => 'campaign_id',
                            'campaign_activities'           => 'campaign_id',
                            'campaign_category_assignments' => 'campaign_id',
                            // Add any other child tables here
                        ];

                        foreach ($child_tables as $table => $column) {
                            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
                            $stmt->execute([$campaign_id]);
                        }

                        // Delete the campaign
                        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
                        $stmt->execute([$campaign_id]);

                        $pdo->commit();
                        $success_message = 'Campaign and all related data deleted successfully!';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error_message = 'Error deleting campaign: ' . $e->getMessage();
                    }
                    break;
            }
        }
    }

    // Refresh CSRF token after successful POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch campaigns
try {
    $query = "
        SELECT id, name, description, type, status, start_date, end_date, 
               budget, target_audience, created_at, updated_at
        FROM campaigns
    ";
    $params = [];

    if (!$is_admin_or_manager) {
        $query .= " WHERE created_by = ?";
        $params[] = $user_id;
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch milestones
    $campaign_milestones = [];
    if (!empty($campaigns)) {
        $campaign_ids = array_column($campaigns, 'id');
        $placeholders = str_repeat('?,', count($campaign_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT campaign_id, name, target_date, status
            FROM campaign_milestones 
            WHERE campaign_id IN ($placeholders)
            ORDER BY target_date
        ");
        $stmt->execute($campaign_ids);
        $milestones_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($milestones_data as $m) {
            $campaign_milestones[$m['campaign_id']][] = $m;
        }
    }

    // Statistics
    $total_campaigns = count($campaigns);
    $active_campaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
    $completed_campaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'completed'));
    $total_budget = array_sum(array_column($campaigns, 'budget'));
    $on_schedule_percent = $total_campaigns > 0 ? round(($completed_campaigns / $total_campaigns) * 100) : 0;

} catch (PDOException $e) {
    $campaigns = [];
    $campaign_milestones = [];
    $total_campaigns = 0;
    $active_campaigns = 0;
    $completed_campaigns = 0;
    $total_budget = 0;
    $on_schedule_percent = 0;
    $error_message = 'Database error: ' . $e->getMessage();
    error_log("Campaigns fetch error: " . $e->getMessage());
}

// Helper functions
function format_currency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function get_status_class($status) {
    $map = [
        'active'    => 'status-active',
        'completed' => 'status-completed',
        'upcoming'  => 'status-upcoming',
        'draft'     => 'status-draft',
    ];
    return $map[$status] ?? 'status-default';
}

function get_campaign_icon($type) {
    $map = [
        'safety'      => 'fa-shield-alt',
        'health'      => 'fa-heartbeat',
        'emergency'   => 'fa-exclamation-triangle',
        'vaccination' => 'fa-syringe',
        'awareness'   => 'fa-bullhorn',
        'education'   => 'fa-graduation-cap',
        'enforcement' => 'fa-gavel',
    ];
    return $map[$type] ?? 'fa-bullhorn';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/campaign.css">
    <title>Campaign Planning & Calendar</title>
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
                    <a href="module-1.php" class="nav-link active">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning & Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="content-repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="target-group-segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="eventseminarmanagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Event & Seminar Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="surveyfeedbackcollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Survey & Feedback Collection</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="campaignanalyticsreports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Campaign Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="healthpoliceintegration.php" class="nav-link">
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
                <h2>Campaign Planning & Calendar</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search campaigns, schedules...">
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
                    <h1 class="module-title">Campaign Planning Center</h1>
                    <p class="module-subtitle">Plan, schedule, and manage all public safety campaigns</p>
                </div>
                <button class="btn" id="createNewCampaignBtn" onclick="createNewCampaign()">
                    <i class="fas fa-plus"></i> New Campaign
                </button>
            </div>

            <div class="module-grid">
                <!-- Campaign Calendar -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Campaign Calendar</div>
                        <div class="card-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                    <div class="calendar-view">
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button class="calendar-nav prev-month">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <h3><?php echo date('F Y'); ?></h3>
                                <button class="calendar-nav next-month">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>

                            <div class="calendar-weekdays">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>

                            <div class="calendar-days">
                                <?php
                                // Generate calendar days
                                $current_month = date('n');
                                $current_year = date('Y');
                                $first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
                                $days_in_month = date('t', $first_day);
                                $day_of_week = date('w', $first_day);
                                $today = date('j');
                                
                                // Empty days before month starts
                                for ($i = 0; $i < $day_of_week; $i++) {
                                    echo '<div class="calendar-day empty"></div>';
                                }
                                
                                // Days of the month
                                for ($day = 1; $day <= $days_in_month; $day++) {
                                    $day_classes = ['calendar-day'];
                                    if ($day == $today) {
                                        $day_classes[] = 'today';
                                    }
                                    
                                    // Check if there are campaigns on this day
                                    $day_has_campaign = false;
                                    foreach ($campaigns as $campaign) {
                                        $campaign_start = new DateTime($campaign['start_date']);
                                        $campaign_end = new DateTime($campaign['end_date']);
                                        $current_day = new DateTime("$current_year-$current_month-$day");
                                        
                                        if ($current_day >= $campaign_start && $current_day <= $campaign_end) {
                                            $day_has_campaign = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($day_has_campaign) {
                                        $day_classes[] = 'has-campaign';
                                    }
                                    
                                    echo '<div class="' . implode(' ', $day_classes) . '">' . $day . '</div>';
                                }
                                ?>
                            </div>

                            <div class="calendar-legend">
                                <div class="legend-item">
                                    <span class="legend-dot today"></span>
                                    <span>Today (<?php echo date('M j'); ?>)</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot has-campaign"></span>
                                    <span>Campaign Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Campaigns -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Active Campaigns</div>
                        <div class="card-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                    </div>
                    <ul class="campaign-list">
                        <?php 
                        $active_campaigns_display = array_filter($campaigns, fn($c) => $c['status'] === 'active');
                        if (empty($active_campaigns_display)): 
                        ?>
                        <li class="no-data">No active campaigns found</li>
                        <?php else: ?>
                            <?php foreach ($active_campaigns_display as $campaign): ?>
                            <li class="campaign-item">
                                <div class="campaign-info">
                                    <h4><?php echo htmlspecialchars($campaign['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($campaign['description'] ?? ''); ?></p>
                                    <div class="campaign-meta">
                                        <span class="campaign-type">
                                            <i class="fas <?php echo get_campaign_icon($campaign['type']); ?>"></i>
                                            <?php echo ucfirst(htmlspecialchars($campaign['type'])); ?>
                                        </span>
                                        <span class="campaign-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M j', strtotime($campaign['start_date'])); ?> - 
                                            <?php echo date('M j', strtotime($campaign['end_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="campaign-actions">
                                    <button class="btn btn-sm" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Campaign Statistics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Campaign Statistics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_campaigns; ?></div>
                            <div class="stat-label">Total Campaigns</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $on_schedule_percent; ?>%</div>
                            <div class="stat-label">On Schedule</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo format_currency($total_budget); ?></div>
                            <div class="stat-label">Budget Utilized</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $active_campaigns; ?></div>
                            <div class="stat-label">Active Campaigns</div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Milestones -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Upcoming Milestones</div>
                        <div class="card-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                    </div>
                    <div class="timeline" id="milestonesTimeline">
                        <?php
                        $upcoming_milestones = [];
                        foreach ($campaign_milestones as $campaign_id => $milestones) {
                            foreach ($milestones as $milestone) {
                                if ($milestone['target_date'] >= date('Y-m-d')) {
                                    $campaign_name = '';
                                    foreach ($campaigns as $campaign) {
                                        if ($campaign['id'] == $campaign_id) {
                                            $campaign_name = $campaign['name'];
                                            break;
                                        }
                                    }
                                    $upcoming_milestones[] = [
                                        'name' => $milestone['name'],
                                        'date' => $milestone['target_date'],
                                        'campaign' => $campaign_name
                                    ];
                                }
                            }
                        }
                        
                        // Sort by date
                        usort($upcoming_milestones, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
                        
                        if (empty($upcoming_milestones)):
                        ?>
                        <p class="no-data">No upcoming milestones</p>
                        <?php else: ?>
                            <?php foreach (array_slice($upcoming_milestones, 0, 5) as $milestone): ?>
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo date('M j', strtotime($milestone['date'])); ?></div>
                                <div class="timeline-content">
                                    <div class="timeline-title"><?php echo htmlspecialchars($milestone['name']); ?></div>
                                    <div class="timeline-desc"><?php echo htmlspecialchars($milestone['campaign']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resource Allocation -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Resource Allocation</div>
                        <div class="card-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                    <div id="resourceAllocation">
                        <div class="resource-grid">
                            <div class="resource-item">
                                <div class="resource-label">Total Budget</div>
                                <div class="resource-value"><?php echo format_currency($total_budget); ?></div>
                            </div>
                            <div class="resource-item">
                                <div class="resource-label">Active Campaigns</div>
                                <div class="resource-value"><?php echo $active_campaigns; ?></div>
                            </div>
                            <div class="resource-item">
                                <div class="resource-label">Completion Rate</div>
                                <div class="resource-value"><?php echo $on_schedule_percent; ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Templates -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Campaign Templates</div>
                        <div class="card-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <button class="btn template-btn" data-template="vaccination" onclick="useTemplate('vaccination')">
                                <i class="fas fa-syringe"></i> Vaccination
                            </button>
                            <button class="btn template-btn" data-template="emergency" onclick="useTemplate('emergency')">
                                <i class="fas fa-exclamation-triangle"></i> Emergency
                            </button>
                            <button class="btn template-btn" data-template="health" onclick="useTemplate('health')">
                                <i class="fas fa-heartbeat"></i> Health
                            </button>
                            <button class="btn template-btn" data-template="safety" onclick="useTemplate('safety')">
                                <i class="fas fa-shield-alt"></i> Safety
                            </button>
                        </div>
                        <p style="margin-top: 15px; color: var(--text-gray); font-size: 14px;">
                            Use pre-built templates for common campaign types
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Campaign Modal -->
    <div class="modal-overlay" id="campaignModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">New Campaign</h3>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="campaignForm" method="POST">
                    <input type="hidden" name="action" value="create_campaign">
                    <input type="hidden" id="campaignId" name="id">

                    <div class="form-group">
                        <label for="campaignName">Campaign Name <span class="required">*</span></label>
                        <input type="text" id="campaignName" name="name" required
                               placeholder="Enter campaign name" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="campaignDescription">Description</label>
                        <textarea id="campaignDescription" name="description" rows="3"
                                  placeholder="Enter campaign description" class="form-input"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date <span class="required">*</span></label>
                            <input type="date" id="startDate" name="startDate" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date <span class="required">*</span></label>
                            <input type="date" id="endDate" name="endDate" required class="form-input">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="campaignType">Type</label>
                            <select id="campaignType" name="type" class="form-input">
                                <option value="safety">Safety</option>
                                <option value="health">Health</option>
                                <option value="emergency">Emergency</option>
                                <option value="vaccination">Vaccination</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="campaignStatus">Status</label>
                            <select id="campaignStatus" name="status" class="form-input">
                                <option value="draft">Draft</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="campaignBudget">Total Budget (₱)</label>
                            <input type="number" id="campaignBudget" name="budget"
                                   placeholder="50000" min="0" step="1000" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="targetAudience">Target Audience</label>
                            <input type="text" id="targetAudience" name="targetAudience"
                                   placeholder="General Public" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="milestones">Milestones (Format: Name:YYYY-MM-DD, Name:YYYY-MM-DD)</label>
                        <textarea id="milestones" name="milestones" rows="2"
                                  placeholder="Phase 1 Launch:2026-01-10, Phase 2 Launch:2026-01-20"
                                  class="form-input"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-danger" id="deleteCampaignBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Campaign
                        </button>
                        <button type="button" class="btn" id="cancelModalBtn">
                            Cancel
                        </button>
                        <button type="submit" class="btn" style="background-color: var(--success);">
                            <i class="fas fa-save"></i> Save Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="modal-close" id="closeConfirmModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <p style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--warning); margin-bottom: 15px; display: block;"></i>
                    Are you sure you want to delete this campaign?
                </p>
                <p style="text-align: center; color: var(--text-gray); font-size: 14px; margin-bottom: 25px;">
                    This action cannot be undone.
                </p>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelDeleteBtn">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Campaign data from PHP
        const campaignsData = <?php echo json_encode($campaigns); ?>;
        const milestonesData = <?php echo json_encode($campaign_milestones); ?>;
        
        // Modal management
        const campaignModal = document.getElementById('campaignModal');
        const confirmModal = document.getElementById('confirmModal');
        const campaignForm = document.getElementById('campaignForm');
        
        function createNewCampaign() {
            document.getElementById('modalTitle').textContent = 'New Campaign';
            campaignForm.reset();
            document.getElementById('campaignId').value = '';
            document.querySelector('input[name="action"]').value = 'create_campaign';
            document.getElementById('deleteCampaignBtn').style.display = 'none';
            
            // Set default dates (today and 30 days from now)
            const today = new Date().toISOString().split('T')[0];
            const nextMonth = new Date();
            nextMonth.setDate(nextMonth.getDate() + 30);
            const nextMonthStr = nextMonth.toISOString().split('T')[0];
            
            document.getElementById('startDate').value = today;
            document.getElementById('endDate').value = nextMonthStr;
            
            campaignModal.style.display = 'flex';
        }
        
        function editCampaign(id) {
            const campaign = campaignsData.find(c => c.id == id);
            if (!campaign) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Campaign';
            document.getElementById('campaignId').value = campaign.id;
            document.getElementById('campaignName').value = campaign.name;
            document.getElementById('campaignDescription').value = campaign.description || '';
            document.getElementById('startDate').value = campaign.start_date;
            document.getElementById('endDate').value = campaign.end_date;
            document.getElementById('campaignType').value = campaign.type;
            document.getElementById('campaignStatus').value = campaign.status;
            document.getElementById('campaignBudget').value = campaign.budget || 0;
            document.getElementById('targetAudience').value = campaign.target_audience || '';
            
            // Load milestones
            if (milestonesData[id]) {
                const milestoneText = milestonesData[id]
                    .map(m => `${m.name}:${m.target_date}`)
                    .join(', ');
                document.getElementById('milestones').value = milestoneText;
            } else {
                document.getElementById('milestones').value = '';
            }
            
            document.querySelector('input[name="action"]').value = 'update_campaign';
            document.getElementById('deleteCampaignBtn').style.display = 'inline-block';
            campaignModal.style.display = 'flex';
        }
        
        function useTemplate(type) {
            const templates = {
                vaccination: {
                    name: 'Vaccination Campaign',
                    description: 'Community vaccination drive for public health',
                    type: 'vaccination',
                    targetAudience: 'General Public, Healthcare Workers',
                    milestones: 'Registration Start:2026-01-01, First Dose:2026-01-15, Second Dose:2026-02-15'
                },
                emergency: {
                    name: 'Emergency Preparedness',
                    description: 'Emergency response and preparedness training',
                    type: 'emergency',
                    targetAudience: 'Community Leaders, First Responders',
                    milestones: 'Planning Phase:2026-01-01, Training Start:2026-01-15, Drill Exercise:2026-02-01'
                },
                health: {
                    name: 'Health Awareness Campaign',
                    description: 'Public health education and awareness program',
                    type: 'health',
                    targetAudience: 'General Public, Schools',
                    milestones: 'Campaign Launch:2026-01-01, Workshop Series:2026-01-15, Health Fair:2026-02-01'
                },
                safety: {
                    name: 'Safety Campaign',
                    description: 'Community safety and accident prevention',
                    type: 'safety',
                    targetAudience: 'Residents, Business Owners',
                    milestones: 'Assessment Phase:2026-01-01, Implementation:2026-01-15, Evaluation:2026-02-01'
                }
            };
            
            const template = templates[type];
            if (template) {
                createNewCampaign(); // Reset form first
                
                // Set template values after a short delay to ensure form is reset
                setTimeout(() => {
                    document.getElementById('campaignName').value = template.name;
                    document.getElementById('campaignDescription').value = template.description;
                    document.getElementById('campaignType').value = template.type;
                    document.getElementById('targetAudience').value = template.targetAudience;
                    document.getElementById('milestones').value = template.milestones;
                }, 50);
            }
        }
        
        // Modal close handlers
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            campaignModal.style.display = 'none';
        });
        
        document.getElementById('cancelModalBtn').addEventListener('click', () => {
            campaignModal.style.display = 'none';
        });
        
        document.getElementById('closeConfirmModalBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        // Delete campaign
        document.getElementById('deleteCampaignBtn').addEventListener('click', () => {
            confirmModal.style.display = 'flex';
        });
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            const campaignId = document.getElementById('campaignId').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_campaign">
                <input type="hidden" name="campaign_id" value="${campaignId}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === campaignModal) {
                campaignModal.style.display = 'none';
            }
            if (e.target === confirmModal) {
                confirmModal.style.display = 'none';
            }
        });
        
        // Form validation
        campaignForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            
            if (endDate < startDate) {
                e.preventDefault();
                alert('End date must be after start date');
                return false;
            }
        });
    </script>

    <script src="../Scripts/utils.js"></script>
    <script src="../Scripts/mod1.js"></script>
</body>
</html>