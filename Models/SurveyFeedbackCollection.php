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
$user_role = $_SESSION['user_role'] ?? 'Feedback Analyst';
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'create_survey':
            case 'update_survey':
                try {
                    $survey_id = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '');
                    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
                    $survey_type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?? 'general';
                    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? 'draft';
                    
                    // Validate required fields
                    if (empty($title)) {
                        throw new Exception('Please enter a survey title');
                    }
                    
                    if ($survey_id) {
                        // Update existing survey
                        $stmt = $pdo->prepare("
                            UPDATE surveys 
                            SET title = ?, description = ?, survey_type = ?, status = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([
                            $title, $description, $survey_type, $status,
                            $survey_id, $user_id
                        ]);
                        $success_message = 'Survey updated successfully!';
                    } else {
                        // Create new survey
                        $stmt = $pdo->prepare("
                            INSERT INTO surveys 
                            (title, description, survey_type, status, created_by)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $title, $description, $survey_type, $status, $user_id
                        ]);
                        $survey_id = $pdo->lastInsertId();
                        $success_message = 'Survey created successfully!';
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
                
            case 'delete_survey':
                try {
                    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;
                    if ($survey_id > 0) {
                        $stmt = $pdo->prepare("
                            DELETE FROM surveys 
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([$survey_id, $user_id]);
                        $success_message = 'Survey deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error deleting survey: ' . $e->getMessage();
                }
                break;
                
            case 'add_question':
                try {
                    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;
                    $question_text = trim(filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_STRING) ?? '');
                    $question_type = filter_input(INPUT_POST, 'question_type', FILTER_SANITIZE_STRING) ?? 'text';
                    $required = isset($_POST['required']) ? 1 : 0;
                    
                    if (empty($question_text) || $survey_id <= 0) {
                        throw new Exception('Please enter a question and select a valid survey');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO survey_questions 
                        (survey_id, question_text, question_type, required)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$survey_id, $question_text, $question_type, $required]);
                    $success_message = 'Question added successfully!';
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
        }
    }
}

// Fetch surveys data
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.title, s.description, s.survey_type, s.status, 
               s.start_date, s.end_date, s.created_at,
               COUNT(sr.id) as response_count
        FROM surveys s
        LEFT JOIN survey_responses sr ON s.id = sr.survey_id
        WHERE s.created_by = ? OR ? IN ('admin', 'manager')
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id, $user_role]);
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_surveys = count($surveys);
    $active_surveys = count(array_filter($surveys, fn($s) => $s['status'] === 'active'));
    $closed_surveys = count(array_filter($surveys, fn($s) => $s['status'] === 'closed'));
    $total_responses = array_sum(array_column($surveys, 'response_count'));
    
    // Calculate average completion rate (assuming a simple calculation based on responses)
    $avg_completion_rate = $total_surveys > 0 ? round(($total_responses / $total_surveys) * 10) : 0;
    
    // Fetch distribution channels
    $stmt = $pdo->query("SELECT * FROM distribution_channels ORDER BY name");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recent feedback/responses for display
    $stmt = $pdo->query("
        SELECT f.feedback_text, f.rating, f.submitted_at, f.feedback_type
        FROM feedback f
        ORDER BY f.submitted_at DESC
        LIMIT 3
    ");
    $recent_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $surveys = [];
    $channels = [];
    $recent_feedback = [];
    $total_surveys = 0;
    $active_surveys = 0;
    $closed_surveys = 0;
    $total_responses = 0;
    $avg_completion_rate = 0;
    error_log("Error fetching surveys: " . $e->getMessage());
}

// Helper functions
function get_survey_type_label($type) {
    switch ($type) {
        case 'campaign': return 'Campaign';
        case 'event': return 'Event';
        case 'service': return 'Service';
        case 'research': return 'Research';
        case 'general': return 'General';
        default: return ucfirst($type);
    }
}

function get_survey_status_class($status) {
    switch ($status) {
        case 'active': return 'status-active';
        case 'closed': return 'status-closed';
        case 'draft': return 'status-draft';
        case 'analysis': return 'status-analysis';
        default: return 'status-default';
    }
}

function get_survey_type_class($type) {
    switch ($type) {
        case 'campaign': return 'type-campaign';
        case 'event': return 'type-event';
        case 'service': return 'type-service';
        case 'research': return 'type-research';
        case 'general': return 'type-general';
        default: return 'type-other';
    }
}

function render_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span class="star filled"><i class="fas fa-star"></i></span>';
        } else {
            $stars .= '<span class="star"><i class="fas fa-star"></i></span>';
        }
    }
    return $stars;
}

function format_date($date_str) {
    if (!$date_str) return 'N/A';
    $date = new DateTime($date_str);
    return $date->format('M j, Y');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/survey.css">
    <title>Survey & Feedback Collection</title>
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
                <a href="SurveyFeedbackCollection.php" class="nav-link active">
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
                <h2>Survey & Feedback Collection</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search surveys, feedback, responses...">
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
                    <h1 class="module-title">Survey & Feedback Collection</h1>
                    <p class="module-subtitle">Gather, analyze, and act on public feedback and survey responses</p>
                </div>
                <button class="btn btn-success" onclick="createNewSurvey()">
                    <i class="fas fa-plus"></i> Create Survey
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active">All Surveys</div>
                <div class="filter-item">Active</div>
                <div class="filter-item">Campaign Feedback</div>
                <div class="filter-item">Event Feedback</div>
                <div class="filter-item">Service Satisfaction</div>
                <div class="filter-item">High Priority</div>
            </div>

            <div class="module-grid">
                <!-- Active Surveys -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Active Surveys</div>
                        <div class="card-icon">
                            <i class="fas fa-poll"></i>
                        </div>
                    </div>
                    <div class="survey-list">
                        <?php if (empty($surveys)): ?>
                        <div class="no-data">No surveys found. Create your first survey!</div>
                        <?php else: ?>
                        <?php foreach (array_filter($surveys, fn($s) => $s['status'] === 'active' || $s['status'] === 'analysis') as $survey): ?>
                        <?php 
                        $completion_rate = $survey['response_count'] > 0 ? round(($survey['response_count'] / 1000) * 100) : 0;
                        if ($completion_rate > 100) $completion_rate = 100; // Cap at 100%
                        ?>
                        <div class="survey-item <?php echo $survey['status']; ?>">
                            <div class="survey-name"><?php echo htmlspecialchars($survey['title']); ?></div>
                            <div class="survey-details"><?php echo htmlspecialchars($survey['description'] ?? 'No description'); ?></div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo $completion_rate; ?>%"></div>
                            </div>
                            <div class="survey-stats">
                                <span class="response-count"><?php echo $survey['response_count']; ?> responses</span>
                                <span class="completion-rate"><?php echo $completion_rate; ?>% completion</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Survey Statistics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Survey Statistics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $active_surveys; ?></div>
                            <div class="stat-label">Active Surveys</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($total_responses); ?></div>
                            <div class="stat-label">Total Responses</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $avg_completion_rate; ?>%</div>
                            <div class="stat-label">Avg. Completion Rate</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">4.2★</div>
                            <div class="stat-label">Avg. Satisfaction</div>
                        </div>
                    </div>
                    <div class="response-chart">
                        <div style="text-align: center; color: var(--text-gray);">
                            <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; color: var(--accent);"></i>
                            <h4>Response Trends</h4>
                            <p>Weekly survey participation</p>
                        </div>
                    </div>
                </div>

                <!-- Create New Survey -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Create New Survey</div>
                        <div class="card-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                    </div>
                    <div class="survey-builder">
                        <form id="createSurveyForm" method="POST">
                            <input type="hidden" name="action" value="create_survey">
                            <div class="survey-form">
                                <div class="form-group">
                                    <label>Survey Title</label>
                                    <input type="text" name="title" placeholder="Enter survey title" required>
                                </div>
                                <div class="form-group">
                                    <label>Survey Type</label>
                                    <select name="type">
                                        <option value="campaign">Campaign Feedback</option>
                                        <option value="event">Event Evaluation</option>
                                        <option value="service">Service Satisfaction</option>
                                        <option value="research">Public Research</option>
                                        <option value="general">General Feedback</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" placeholder="Enter survey description"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Question Types</label>
                                    <div class="question-types">
                                        <div class="question-type">
                                            <i class="fas fa-dot-circle"></i>
                                            <div>Multiple Choice</div>
                                        </div>
                                        <div class="question-type">
                                            <i class="fas fa-check-square"></i>
                                            <div>Checkboxes</div>
                                        </div>
                                        <div class="question-type">
                                            <i class="fas fa-star"></i>
                                            <div>Rating Scale</div>
                                        </div>
                                        <div class="question-type">
                                            <i class="fas fa-comment"></i>
                                            <div>Open Text</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-magic"></i> Launch Survey Builder
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sentiment Analysis -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Sentiment Analysis</div>
                        <div class="card-icon">
                            <i class="fas fa-smile"></i>
                        </div>
                    </div>
                    <div class="sentiment-analysis">
                        <div class="sentiment-item positive">
                            <div class="sentiment-label">
                                <div class="sentiment-icon">
                                    <i class="fas fa-smile"></i>
                                </div>
                                <span>Positive</span>
                            </div>
                            <div class="sentiment-bar">
                                <div class="sentiment-fill" style="width: 65%"></div>
                            </div>
                            <span>65%</span>
                        </div>
                        <div class="sentiment-item neutral">
                            <div class="sentiment-label">
                                <div class="sentiment-icon">
                                    <i class="fas fa-meh"></i>
                                </div>
                                <span>Neutral</span>
                            </div>
                            <div class="sentiment-bar">
                                <div class="sentiment-fill" style="width: 25%"></div>
                            </div>
                            <span>25%</span>
                        </div>
                        <div class="sentiment-item negative">
                            <div class="sentiment-label">
                                <div class="sentiment-icon">
                                    <i class="fas fa-frown"></i>
                                </div>
                                <span>Negative</span>
                            </div>
                            <div class="sentiment-bar">
                                <div class="sentiment-fill" style="width: 10%"></div>
                            </div>
                            <span>10%</span>
                        </div>
                    </div>
                    <div class="word-cloud">
                        <span class="word-item word-size-5">helpful</span>
                        <span class="word-item word-size-4">timely</span>
                        <span class="word-item word-size-3">professional</span>
                        <span class="word-item word-size-2">informative</span>
                        <span class="word-item word-size-4">effective</span>
                        <span class="word-item word-size-3">clear</span>
                        <span class="word-item word-size-2">responsive</span>
                        <span class="word-item word-size-3">supportive</span>
                        <span class="word-item word-size-1">detailed</span>
                    </div>
                </div>

                <!-- Recent Responses -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Recent Responses</div>
                        <div class="card-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                    <div class="response-list">
                        <?php if (empty($recent_feedback)): ?>
                        <div class="no-data">No recent feedback available</div>
                        <?php else: ?>
                        <?php foreach ($recent_feedback as $feedback): ?>
                        <div class="response-item">
                            <div class="rating-display">
                                <?php echo render_stars($feedback['rating'] ?? 0); ?>
                            </div>
                            <div class="response-text">
                                "<?php echo htmlspecialchars($feedback['feedback_text']); ?>"
                            </div>
                            <div class="response-meta">
                                <span>From: <?php echo get_survey_type_label($feedback['feedback_type'] ?? 'general'); ?></span>
                                <span><?php echo time_elapsed_string($feedback['submitted_at']); ?> ago</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-eye"></i> View All Responses
                    </button>
                </div>

                <!-- Distribution Channels -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Distribution Channels</div>
                        <div class="card-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                    </div>
                    <div class="channel-stats">
                        <?php foreach ($channels as $channel): ?>
                        <div class="channel-item">
                            <i class="fas <?php 
                                echo $channel['channel_type'] === 'email' ? 'fa-envelope' : 
                                     ($channel['channel_type'] === 'sms' ? 'fa-mobile-alt' : 
                                     ($channel['channel_type'] === 'web' ? 'fa-globe' : 'fa-qrcode')); 
                            ?>"></i>
                            <div class="channel-details">
                                <div class="channel-name"><?php echo htmlspecialchars($channel['name']); ?></div>
                                <div class="channel-response">
                                    <?php echo $channel['response_rate']; ?>% response rate • <?php echo $channel['responses_received']; ?> responses
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Channel Insight</div>
                        <div style="color: var(--success); font-size: 14px;">
                            <i class="fas fa-arrow-up"></i> Web portal shows highest completion rate
                        </div>
                    </div>
                </div>
            </div>

            <!-- Surveys Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">All Surveys</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="surveys-table">
                    <thead>
                        <tr>
                            <th>Survey Name</th>
                            <th>Type</th>
                            <th>Responses</th>
                            <th>Completion Rate</th>
                            <th>Avg. Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($surveys)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No surveys found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($surveys as $survey): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($survey['title']); ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($survey['description'] ?? 'No description'); ?></div>
                            </td>
                            <td><span class="survey-type <?php echo get_survey_type_class($survey['survey_type']); ?>"><?php echo get_survey_type_label($survey['survey_type']); ?></span></td>
                            <td>
                                <div><?php echo $survey['response_count']; ?></div>
                                <div style="height: 4px; background-color: var(--dark-gray); border-radius: 2px; margin-top: 5px;">
                                    <?php 
                                    $completion_rate = $survey['response_count'] > 0 ? round(($survey['response_count'] / 1000) * 100) : 0;
                                    if ($completion_rate > 100) $completion_rate = 100; // Cap at 100%
                                    ?>
                                    <div style="width: <?php echo $completion_rate; ?>%; height: 100%; background-color: var(--success); border-radius: 2px;"></div>
                                </div>
                            </td>
                            <td><?php echo $completion_rate; ?>%</td>
                            <td>
                                <div class="rating-display">
                                    <?php echo render_stars(4); // Assuming average rating of 4 ?>
                                </div>
                            </td>
                            <td><span class="survey-status <?php echo get_survey_status_class($survey['status']); ?>"><?php echo ucfirst($survey['status']); ?></span></td>
                            <td>
                                <div class="survey-actions">
                                    <i class="fas fa-chart-bar" title="Analytics" onclick="viewAnalytics(<?php echo $survey['id']; ?>)"></i>
                                    <i class="fas fa-download" title="Export" onclick="exportSurvey(<?php echo $survey['id']; ?>)"></i>
                                    <i class="fas fa-edit" title="Edit" onclick="editSurvey(<?php echo $survey['id']; ?>)"></i>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Additional Features -->
            <div class="module-grid" style="margin-top: 30px;">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="quick-actions-grid">
                        <button class="action-btn" onclick="sendSurveyReminder()">
                            <i class="fas fa-bell"></i>
                            <span>Send Reminders</span>
                        </button>
                        <button class="action-btn" onclick="exportSurveyData()">
                            <i class="fas fa-file-export"></i>
                            <span>Export Data</span>
                        </button>
                        <button class="action-btn" onclick="analyzeTrends()">
                            <i class="fas fa-chart-line"></i>
                            <span>Analyze Trends</span>
                        </button>
                        <button class="action-btn" onclick="generateInsights()">
                            <i class="fas fa-lightbulb"></i>
                            <span>Generate Insights</span>
                        </button>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Action Items</div>
                        <div style="font-size: 14px; color: var(--text-gray);">
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Analyze campaign feedback</span>
                                <span style="color: var(--warning);">Due: Today</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Share workshop results</span>
                                <span style="color: var(--success);">Completed</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Follow up on low ratings</span>
                                <span style="color: var(--warning);">In Progress</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Feedback Impact</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                    <div class="analytics-dashboard">
                        <div class="metric">
                            <div class="metric-value">42</div>
                            <div class="metric-label">Issues Identified</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">28</div>
                            <div class="metric-label">Actions Taken</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">65%</div>
                            <div class="metric-label">Satisfaction Improvement</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">18</div>
                            <div class="metric-label">Process Changes</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Recent Impact</div>
                        <div style="font-size: 14px; color: var(--text-gray);">
                            Based on feedback, extended workshop hours by 30 minutes
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Survey Modal -->
    <div class="modal-overlay" id="surveyModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">New Survey</h3>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="surveyForm" method="POST">
                    <input type="hidden" name="action" value="create_survey">
                    <input type="hidden" id="surveyId" name="id">

                    <div class="form-group">
                        <label for="surveyTitle">Survey Title</label>
                        <input type="text" id="surveyTitle" name="title" required
                               placeholder="Enter survey title" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="surveyType">Survey Type</label>
                        <select id="surveyType" name="type" class="form-input">
                            <option value="campaign">Campaign Feedback</option>
                            <option value="event">Event Evaluation</option>
                            <option value="service">Service Satisfaction</option>
                            <option value="research">Public Research</option>
                            <option value="general">General Feedback</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="surveyStatus">Status</label>
                        <select id="surveyStatus" name="status" class="form-input">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                            <option value="analysis">Analysis</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="surveyDescription">Description</label>
                        <textarea id="surveyDescription" name="description" rows="3"
                                  placeholder="Enter survey description" class="form-input"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-danger" id="deleteSurveyBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Survey
                        </button>
                        <button type="button" class="btn" id="cancelModalBtn">
                            Cancel
                        </button>
                        <button type="submit" class="btn" style="background-color: var(--success);">
                            <i class="fas fa-save"></i> Save Survey
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
                    Are you sure you want to delete this survey?
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
        // Survey data from PHP
        const surveysData = <?php echo json_encode($surveys); ?>;
        
        // Modal management
        const surveyModal = document.getElementById('surveyModal');
        const confirmModal = document.getElementById('confirmModal');
        const surveyForm = document.getElementById('surveyForm');
        
        function createNewSurvey() {
            document.getElementById('modalTitle').textContent = 'New Survey';
            surveyForm.reset();
            document.getElementById('surveyId').value = '';
            document.querySelector('input[name="action"]').value = 'create_survey';
            document.getElementById('deleteSurveyBtn').style.display = 'none';
            surveyModal.style.display = 'flex';
        }
        
        function editSurvey(id) {
            const survey = surveysData.find(s => s.id == id);
            if (!survey) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Survey';
            document.getElementById('surveyId').value = survey.id;
            document.getElementById('surveyTitle').value = survey.title;
            document.getElementById('surveyType').value = survey.survey_type;
            document.getElementById('surveyStatus').value = survey.status;
            document.getElementById('surveyDescription').value = survey.description || '';
            
            document.querySelector('input[name="action"]').value = 'update_survey';
            document.getElementById('deleteSurveyBtn').style.display = 'inline-block';
            surveyModal.style.display = 'flex';
        }
        
        // Modal close handlers
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            surveyModal.style.display = 'none';
        });
        
        document.getElementById('cancelModalBtn').addEventListener('click', () => {
            surveyModal.style.display = 'none';
        });
        
        document.getElementById('closeConfirmModalBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        // Delete survey
        document.getElementById('deleteSurveyBtn').addEventListener('click', () => {
            confirmModal.style.display = 'flex';
        });
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            const surveyId = document.getElementById('surveyId').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_survey">
                <input type="hidden" name="survey_id" value="${surveyId}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === surveyModal) {
                surveyModal.style.display = 'none';
            }
            if (e.target === confirmModal) {
                confirmModal.style.display = 'none';
            }
        });
        
        // Quick action functions
        function sendSurveyReminder() {
            alert('Sending survey reminders to participants...');
        }
        
        function exportSurveyData() {
            alert('Exporting survey data...');
        }
        
        function analyzeTrends() {
            alert('Analyzing survey trends...');
        }
        
        function generateInsights() {
            alert('Generating insights from survey data...');
        }
        
        function viewAnalytics(surveyId) {
            alert(`Viewing analytics for survey ID: ${surveyId}`);
        }
        
        function exportSurvey(surveyId) {
            alert(`Exporting survey data for ID: ${surveyId}`);
        }
        
        // Initialize question types on page load
        document.addEventListener('DOMContentLoaded', function() {
            const questionTypes = document.querySelectorAll('.question-type');
            questionTypes.forEach(type => {
                type.addEventListener('click', function() {
                    questionTypes.forEach(t => t.style.backgroundColor = 'var(--dark-gray)');
                    this.style.backgroundColor = 'var(--medium-gray)';
                });
            });
        });
    </script>

    <script src="../Scripts/utils.js"></script>
    <script src="../Scripts/mod5.js"></script>

    <style>
        /* Additional styles for the survey module */
        .star.filled {
            color: gold;
        }
        
        .star {
            color: #ccc;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--text-gray);
            font-style: italic;
        }
        
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
        
        .close-modal {
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
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
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-success { background-color: #28a745; color: white; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-warning { background-color: #ffc107; color: #000; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-icon { background-color: transparent; color: #007bff; padding: 5px; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
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
