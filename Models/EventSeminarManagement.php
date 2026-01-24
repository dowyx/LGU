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

// Include Content Repository model for cross-module integration
require_once './ContentRepository.php';

// Include Target Group Segmentation model for cross-module integration
require_once './TargetGroupSegmentation.php';

// Initialize Content Repository
$contentRepo = null;
try {
    $contentRepo = new ContentRepository();
} catch (Exception $e) {
    error_log("Failed to initialize ContentRepository: " . $e->getMessage());
}

// Get user data
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Event Coordinator';
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        switch ($action) {
            case 'create_event':
            case 'update_event':
                try {
                    $event_id = isset($_POST['id']) ? intval($_POST['id']) : null;
                    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '');
                    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
                    $event_type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?? 'seminar';
                    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING) ?? '';
                    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING) ?? '';
                    $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING) ?? '');
                    $capacity = intval($_POST['capacity'] ?? 0);
                    
                    // Validate required fields
                    if (empty($title) || empty($start_date)) {
                        throw new Exception('Please fill in all required fields');
                    }
                    
                    if ($end_date && strtotime($end_date) < strtotime($start_date)) {
                        throw new Exception('End date must be after start date');
                    }
                    
                    if ($event_id) {
                        // Update existing event
                        $stmt = $pdo->prepare("
                            UPDATE events 
                            SET title = ?, description = ?, event_type = ?, 
                                start_date = ?, end_date = ?, location = ?, capacity = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([
                            $title, $description, $event_type, 
                            $start_date, $end_date, $location, $capacity,
                            $event_id, $user_id
                        ]);
                        $success_message = 'Event updated successfully!';
                    } else {
                        // Create new event
                        $stmt = $pdo->prepare("
                            INSERT INTO events 
                            (title, description, event_type, start_date, end_date, 
                             location, capacity, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $title, $description, $event_type, $start_date, $end_date, 
                            $location, $capacity, $user_id
                        ]);
                        $event_id = $pdo->lastInsertId();
                        $success_message = 'Event created successfully!';
                    }
                    
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                break;
                
            case 'delete_event':
                try {
                    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
                    if ($event_id > 0) {
                        $stmt = $pdo->prepare("
                            DELETE FROM events 
                            WHERE id = ? AND created_by = ?
                        ");
                        $stmt->execute([$event_id, $user_id]);
                        $success_message = 'Event deleted successfully!';
                    }
                } catch (Exception $e) {
                    $error_message = 'Error deleting event: ' . $e->getMessage();
                }
                break;
                
            case 'register_event':
                try {
                    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
                    if ($event_id > 0) {
                        // Check if already registered
                        $check_stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
                        $check_stmt->execute([$event_id, $user_id]);
                        if (!$check_stmt->fetch()) {
                            // Register for event
                            $stmt = $pdo->prepare("
                                INSERT INTO event_registrations 
                                (event_id, user_id, registration_date)
                                VALUES (?, ?, NOW())
                            ");
                            $stmt->execute([$event_id, $user_id]);
                            
                            // Update registration count
                            $pdo->prepare("UPDATE events SET registration_count = registration_count + 1 WHERE id = ?")->execute([$event_id]);
                            
                            $success_message = 'Successfully registered for event!';
                        } else {
                            $error_message = 'You are already registered for this event.';
                        }
                    }
                } catch (Exception $e) {
                    $error_message = 'Error registering for event: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch events data
try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, event_type, start_date, end_date, 
               location, capacity, registration_count, status, created_at
        FROM events 
        WHERE created_by = ? OR ? IN ('admin', 'manager')
        ORDER BY start_date ASC
    ");
    $stmt->execute([$user_id, $user_role]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_events = count($events);
    $upcoming_events = count(array_filter($events, fn($e) => $e['status'] === 'upcoming' || $e['status'] === 'planning'));
    $ongoing_events = count(array_filter($events, fn($e) => $e['status'] === 'ongoing'));
    $completed_events = count(array_filter($events, fn($e) => $e['status'] === 'completed'));
    $total_registrations = array_sum(array_column($events, 'registration_count'));
    
    // Fetch venues
    $stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch resources
    $stmt = $pdo->query("SELECT * FROM event_resources ORDER BY category, name");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $events = [];
    $venues = [];
    $resources = [];
    $total_events = 0;
    $upcoming_events = 0;
    $ongoing_events = 0;
    $completed_events = 0;
    $total_registrations = 0;
    error_log("Error fetching events: " . $e->getMessage());
}

// Helper functions
function format_event_date($date_str) {
    $date = new DateTime($date_str);
    return $date->format('M j, Y g:i A');
}

function format_event_date_short($date_str) {
    $date = new DateTime($date_str);
    return $date->format('M j');
}

function get_event_type_class($type) {
    switch ($type) {
        case 'seminar': return 'type-seminar';
        case 'workshop': return 'type-workshop';
        case 'training': return 'type-training';
        case 'conference': return 'type-conference';
        case 'fair': return 'type-fair';
        default: return 'type-other';
    }
}

function get_event_status_class($status) {
    switch ($status) {
        case 'upcoming': return 'status-upcoming';
        case 'ongoing': return 'status-ongoing';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        case 'planning': return 'status-planning';
        default: return 'status-default';
    }
}

function get_event_icon($type) {
    switch ($type) {
        case 'seminar': return 'fa-chalkboard-teacher';
        case 'workshop': return 'fa-tools';
        case 'training': return 'fa-graduation-cap';
        case 'conference': return 'fa-comments';
        case 'fair': return 'fa-hands-helping';
        default: return 'fa-calendar-alt';
    }
}

// Cross-module integration functions for linking with Content Repository
function getEventContent($eventId) {
    global $contentRepo, $pdo;
    try {
        // Try to use ContentRepository model if available
        if ($contentRepo !== null) {
            return $contentRepo->getContentForEvent($eventId);
        } else {
            // Fallback to direct database query
            $stmt = $pdo->prepare(
                "SELECT ec.*, ci.name as content_name, ci.description as content_description, 
                       ci.file_type, ci.category, ci.status as content_status
                 FROM event_content ec
                 JOIN content_items ci ON ec.content_item_id = ci.id
                 WHERE ec.event_id = ?
                 ORDER BY ec.relevance_score DESC"
            );
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching event content: " . $e->getMessage());
        return [];
    }
}

function linkContentToEvent($contentId, $eventId, $relevanceScore = 5) {
    global $contentRepo, $pdo;
    try {
        // Try to use ContentRepository model if available
        if ($contentRepo !== null) {
            return $contentRepo->linkContentToEvent($contentId, $eventId, $relevanceScore);
        } else {
            // Fallback to direct database query
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO event_content 
                 (content_item_id, event_id, relevance_score, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            return $stmt->execute([$contentId, $eventId, $relevanceScore]);
        }
    } catch (Exception $e) {
        error_log("Error linking content to event: " . $e->getMessage());
        return false;
    }
}

function unlinkContentFromEvent($contentId, $eventId) {
    global $contentRepo, $pdo;
    try {
        // Try to use ContentRepository model if available
        if ($contentRepo !== null) {
            // For unlinking, we need to use direct SQL since ContentRepository doesn't have this method
            $stmt = $pdo->prepare(
                "DELETE FROM event_content 
                 WHERE content_item_id = ? AND event_id = ?"
            );
            return $stmt->execute([$contentId, $eventId]);
        } else {
            // Fallback to direct database query
            $stmt = $pdo->prepare(
                "DELETE FROM event_content 
                 WHERE content_item_id = ? AND event_id = ?"
            );
            return $stmt->execute([$contentId, $eventId]);
        }
    } catch (Exception $e) {
        error_log("Error unlinking content from event: " . $e->getMessage());
        return false;
    }
}

function getEventRelatedContent($eventId) {
    global $contentRepo, $pdo;
    try {
        // Try to use ContentRepository model if available
        if ($contentRepo !== null) {
            return $contentRepo->getContentForEvent($eventId);
        } else {
            // Fallback to direct database query
            $stmt = $pdo->prepare(
                "SELECT ci.*, ec.relevance_score
                 FROM content_items ci
                 JOIN event_content ec ON ci.id = ec.content_item_id
                 WHERE ec.event_id = ? AND ci.status = 'approved'
                 ORDER BY ec.relevance_score DESC"
            );
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching related content for event: " . $e->getMessage());
        return [];
    }
}

function getAvailableContentForEvent($eventId) {
    global $contentRepo, $pdo;
    try {
        // Try to use ContentRepository model if available
        if ($contentRepo !== null) {
            // Get all approved content
            $allContent = $contentRepo->getContentItems(['status' => 'approved']);
            // Get content already linked to the event
            $linkedContent = $contentRepo->getContentForEvent($eventId);
            
            // Extract IDs of linked content
            $linkedIds = array_column($linkedContent, 'id');
            
            // Filter out linked content
            $availableContent = array_filter($allContent, function($item) use ($linkedIds) {
                return !in_array($item['id'], $linkedIds);
            });
            
            // Sort by created_at descending
            usort($availableContent, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array_values($availableContent);
        } else {
            // Fallback to direct database query
            $stmt = $pdo->prepare(
                "SELECT ci.* 
                 FROM content_items ci
                 WHERE ci.status = 'approved'
                 AND ci.id NOT IN (
                     SELECT ec.content_item_id 
                     FROM event_content ec 
                     WHERE ec.event_id = ?
                 )
                 ORDER BY ci.created_at DESC"
            );
            $stmt->execute([$eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching available content for event: " . $e->getMessage());
        return [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/event.css">
    <title>Event & Seminar Management</title>
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
                    <a href="Content-Repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Target-Group-Segmentation.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="EventSeminarManagement.php" class="nav-link active">
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
                <h2>Event & Seminar Management</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search events, venues, topics...">
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
                    <h1 class="module-title">Event & Seminar Management</h1>
                    <p class="module-subtitle">Plan, organize, and track public safety events and educational seminars</p>
                </div>
                <button class="btn btn-success" onclick="createNewEvent()">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active">All Events</div>
                <div class="filter-item">Upcoming</div>
                <div class="filter-item">This Week</div>
                <div class="filter-item">Seminars</div>
                <div class="filter-item">Workshops</div>
                <div class="filter-item">Training</div>
            </div>

            <div class="module-grid">
                <!-- Upcoming Events -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Upcoming Events</div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="event-list">
                        <?php if (empty($events)): ?>
                        <div class="no-data">No events found. Create your first event!</div>
                        <?php else: ?>
                        <?php foreach (array_filter($events, fn($e) => $e['status'] === 'upcoming' || $e['status'] === 'planning') as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <div class="date-day"><?php echo date('d', strtotime($event['start_date'])); ?></div>
                                <div class="date-month"><?php echo date('M', strtotime($event['start_date'])); ?></div>
                            </div>
                            <div class="event-details">
                                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="event-location"><?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?></div>
                                <div class="event-attendees">
                                    <?php echo $event['registration_count']; ?> registered • Capacity: <?php echo $event['capacity']; ?>
                                </div>
                                <div class="progress-container">
                                    <?php $percent = $event['capacity'] > 0 ? round(($event['registration_count'] / $event['capacity']) * 100) : 0; ?>
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Statistics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Event Statistics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $upcoming_events; ?></div>
                            <div class="stat-label">Upcoming Events</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_registrations; ?></div>
                            <div class="stat-label">Total Registrations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">85%</div>
                            <div class="stat-label">Avg. Attendance Rate</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">4.7★</div>
                            <div class="stat-label">Avg. Satisfaction</div>
                        </div>
                    </div>
                    <div class="attendance-chart">
                        <div style="text-align: center; color: var(--text-gray);">
                            <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; color: var(--accent);"></i>
                            <h4>Attendance Trends</h4>
                            <p>Monthly event participation</p>
                        </div>
                    </div>
                </div>

                <!-- Create New Event -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Create New Event</div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                    <div class="event-form">
                        <form id="createEventForm" method="POST">
                            <input type="hidden" name="action" value="create_event">
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="title" placeholder="Enter event title" required>
                            </div>
                            <div class="form-group">
                                <label>Event Type</label>
                                <select name="type">
                                    <option value="seminar">Seminar</option>
                                    <option value="workshop">Workshop</option>
                                    <option value="training">Training</option>
                                    <option value="conference">Conference</option>
                                    <option value="fair">Community Fair</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date & Time</label>
                                <input type="datetime-local" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label>End Date & Time</label>
                                <input type="datetime-local" name="end_date">
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <select name="location">
                                    <option value="">Select a venue</option>
                                    <?php foreach ($venues as $venue): ?>
                                    <option value="<?php echo htmlspecialchars($venue['name']); ?>">
                                        <?php echo htmlspecialchars($venue['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="number" name="capacity" placeholder="Enter capacity" min="1" value="50">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" placeholder="Enter event description"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save & Schedule
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Venue Management -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Venue Management</div>
                        <div class="card-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                    <div class="venue-list">
                        <?php foreach ($venues as $venue): ?>
                        <div class="venue-item">
                            <div class="venue-icon">
                                <i class="fas fa-<?php echo $venue['name'] === 'City Community Center' ? 'university' : ($venue['name'] === 'Public Safety HQ - Conference Room A' ? 'building' : 'home'); ?>"></i>
                            </div>
                            <div class="venue-details">
                                <div class="venue-name"><?php echo htmlspecialchars($venue['name']); ?></div>
                                <div class="venue-capacity">Capacity: <?php echo $venue['capacity']; ?> • Equipment: <?php echo htmlspecialchars($venue['equipment_available'] ?? 'None'); ?></div>
                                <div>
                                    <?php if ($venue['equipment_available']): ?>
                                    <?php $equipments = explode(',', $venue['equipment_available']); ?>
                                    <?php foreach ($equipments as $equipment): ?>
                                    <span class="badge"><?php echo trim($equipment); ?></span>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-warning" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-plus"></i> Add New Venue
                    </button>
                </div>

                <!-- Registration Statistics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Registration Statistics</div>
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="registration-stats">
                        <?php foreach (array_slice($events, 0, 4) as $event): ?>
                        <div class="reg-item">
                            <span><?php echo htmlspecialchars($event['title']); ?> (<?php echo date('M d', strtotime($event['start_date'])); ?>)</span>
                            <span class="reg-count"><?php echo $event['registration_count']; ?>/<?php echo $event['capacity']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Registration Rate</div>
                        <div style="color: var(--success); font-size: 14px;">
                            <i class="fas fa-arrow-up"></i> 15% increase from last month
                        </div>
                    </div>
                </div>

                <!-- Resource Management -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Resource Management</div>
                        <div class="card-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="resource-grid">
                        <?php foreach (array_slice($resources, 0, 4) as $resource): ?>
                        <div class="resource-item">
                            <div style="font-size: 24px; color: var(--accent); margin-bottom: 10px;">
                                <i class="fas <?php echo $resource['category'] === 'AV Equipment' ? 'fa-laptop' : ($resource['category'] === 'Furniture' ? 'fa-chair' : 'fa-first-aid'); ?>"></i>
                            </div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($resource['name']); ?></div>
                            <div style="font-size: 14px; color: var(--text-gray);"><?php echo $resource['available_quantity']; ?>/<?php echo $resource['total_quantity']; ?> available</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-clipboard-check"></i> Check Availability
                    </button>
                </div>
            </div>

            <!-- Events Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">All Events</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Registrations</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No events found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($event['description'] ?? ''); ?></div>
                            </td>
                            <td><span class="event-type <?php echo get_event_type_class($event['event_type']); ?>"><i class="fas <?php echo get_event_icon($event['event_type']); ?>"></i> <?php echo ucfirst($event['event_type']); ?></span></td>
                            <td>
                                <div><?php echo format_event_date_short($event['start_date']); ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo date('g:i A', strtotime($event['start_date'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?></td>
                            <td>
                                <div><?php echo $event['registration_count']; ?>/<?php echo $event['capacity']; ?></div>
                                <div style="height: 4px; background-color: var(--dark-gray); border-radius: 2px; margin-top: 5px;">
                                    <?php $percent = $event['capacity'] > 0 ? round(($event['registration_count'] / $event['capacity']) * 100) : 0; ?>
                                    <div style="width: <?php echo $percent; ?>%; height: 100%; background-color: var(--success); border-radius: 2px;"></div>
                                </div>
                            </td>
                            <td><span class="event-status <?php echo get_event_status_class($event['status']); ?>"><?php echo ucfirst($event['status']); ?></span></td>
                            <td>
                                <div class="event-actions">
                                    <i class="fas fa-edit" title="Edit" onclick="editEvent(<?php echo $event['id']; ?>)"></i>
                                    <i class="fas fa-users" title="Attendees"></i>
                                    <i class="fas fa-envelope" title="Notify"></i>
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
                        <div class="card-title">Event Calendar</div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>

                    <div class="calendar-view">
                        <div style="width: 100%;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="color: var(--white);">Upcoming Schedule</h3>
                                <button class="btn btn-icon" onclick="updateCalendarView()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <?php foreach (array_slice(array_filter($events, fn($e) => $e['status'] === 'upcoming'), 0, 3) as $event): ?>
                                <div class="calendar-event-item">
                                    <div class="calendar-event-icon <?php echo $event['event_type']; ?>">
                                        <i class="fas <?php echo get_event_icon($event['event_type']); ?>"></i>
                                    </div>
                                    <div class="calendar-event-details">
                                        <div class="calendar-event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                        <div class="calendar-event-time"><?php echo format_event_date_short($event['start_date']); ?> • <?php echo date('g:i A', strtotime($event['start_date'])); ?></div>
                                        <div class="calendar-event-location"><?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?></div>
                                    </div>
                                    <?php $percent = $event['capacity'] > 0 ? round(($event['registration_count'] / $event['capacity']) * 100) : 0; ?>
                                    <span class="badge badge-<?php echo $percent > 80 ? 'success' : ($percent > 60 ? 'warning' : 'info'); ?>"><?php echo $percent; ?>% Full</span>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <button class="btn btn-primary" onclick="showAllEvents()" style="width: 100%; margin-top: 20px;">
                                <i class="fas fa-calendar"></i> View All Scheduled Events
                            </button>
                        </div>
                    </div>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="quick-actions-grid">
                        <button class="action-btn" onclick="sendReminders()">
                            <i class="fas fa-bell"></i>
                            <span>Send Reminders</span>
                        </button>
                        <button class="action-btn" onclick="printMaterials()">
                            <i class="fas fa-print"></i>
                            <span>Print Materials</span>
                        </button>
                        <button class="action-btn" onclick="exportAttendees()">
                            <i class="fas fa-file-export"></i>
                            <span>Export Attendees</span>
                        </button>
                        <button class="action-btn" onclick="generateReports()">
                            <i class="fas fa-chart-pie"></i>
                            <span>Generate Reports</span>
                        </button>
                    </div>
                    <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 10px;">Today's Tasks</div>
                        <div style="font-size: 14px; color: var(--text-gray);">
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Confirm venue for upcoming event</span>
                                <span style="color: var(--warning);">Pending</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Send reminder emails</span>
                                <span style="color: var(--success);">Completed</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                                <span>Prepare training materials</span>
                                <span style="color: var(--warning);">In Progress</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Event Modal -->
    <div class="modal-overlay" id="eventModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">New Event</h3>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="eventForm" method="POST">
                    <input type="hidden" name="action" value="create_event">
                    <input type="hidden" id="eventId" name="id">

                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" name="title" required
                               placeholder="Enter event title" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="eventType">Event Type</label>
                        <select id="eventType" name="type" class="form-input">
                            <option value="seminar">Seminar</option>
                            <option value="workshop">Workshop</option>
                            <option value="training">Training</option>
                            <option value="conference">Conference</option>
                            <option value="fair">Community Fair</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date & Time</label>
                            <input type="datetime-local" id="startDate" name="start_date" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date & Time</label>
                            <input type="datetime-local" id="endDate" name="end_date" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventLocation">Location</label>
                        <select id="eventLocation" name="location" class="form-input">
                            <option value="">Select a venue</option>
                            <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo htmlspecialchars($venue['name']); ?>">
                                <?php echo htmlspecialchars($venue['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="eventCapacity">Capacity</label>
                        <input type="number" id="eventCapacity" name="capacity" 
                               placeholder="Enter capacity" min="1" value="50" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="eventDescription">Description</label>
                        <textarea id="eventDescription" name="description" rows="3"
                                  placeholder="Enter event description" class="form-input"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Event
                        </button>
                        <button type="button" class="btn" id="cancelModalBtn">
                            Cancel
                        </button>
                        <button type="submit" class="btn" style="background-color: var(--success);">
                            <i class="fas fa-save"></i> Save Event
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
                    Are you sure you want to delete this event?
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
        // Event data from PHP
        const eventsData = <?php echo json_encode($events); ?>;
        
        // Modal management
        const eventModal = document.getElementById('eventModal');
        const confirmModal = document.getElementById('confirmModal');
        const eventForm = document.getElementById('eventForm');
        
        function createNewEvent() {
            document.getElementById('modalTitle').textContent = 'New Event';
            eventForm.reset();
            document.getElementById('eventId').value = '';
            document.querySelector('input[name="action"]').value = 'create_event';
            document.getElementById('deleteEventBtn').style.display = 'none';
            eventModal.style.display = 'flex';
        }
        
        function editEvent(id) {
            const event = eventsData.find(e => e.id == id);
            if (!event) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Event';
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventType').value = event.event_type;
            document.getElementById('eventLocation').value = event.location || '';
            document.getElementById('eventCapacity').value = event.capacity || 50;
            document.getElementById('eventDescription').value = event.description || '';
            
            // Format dates for datetime-local inputs
            const startDate = new Date(event.start_date);
            const endDate = event.end_date ? new Date(event.end_date) : null;
            
            // Format as YYYY-MM-DDTHH:mm for datetime-local input
            const formatDateForInput = (date) => {
                if (!date) return '';
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            };
            
            document.getElementById('startDate').value = formatDateForInput(startDate);
            if (endDate) {
                document.getElementById('endDate').value = formatDateForInput(endDate);
            }
            
            document.querySelector('input[name="action"]').value = 'update_event';
            document.getElementById('deleteEventBtn').style.display = 'inline-block';
            eventModal.style.display = 'flex';
        }
        
        // Modal close handlers
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            eventModal.style.display = 'none';
        });
        
        document.getElementById('cancelModalBtn').addEventListener('click', () => {
            eventModal.style.display = 'none';
        });
        
        document.getElementById('closeConfirmModalBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        // Delete event
        document.getElementById('deleteEventBtn').addEventListener('click', () => {
            confirmModal.style.display = 'flex';
        });
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            const eventId = document.getElementById('eventId').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_event">
                <input type="hidden" name="event_id" value="${eventId}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === eventModal) {
                eventModal.style.display = 'none';
            }
            if (e.target === confirmModal) {
                confirmModal.style.display = 'none';
            }
        });
        
        // Quick action functions
        function sendReminders() {
            alert('Sending reminders to registered attendees...');
        }
        
        function printMaterials() {
            alert('Printing event materials...');
        }
        
        function exportAttendees() {
            alert('Exporting attendee list...');
        }
        
        function generateReports() {
            alert('Generating event reports...');
        }
        
        function updateCalendarView() {
            alert('Updating calendar view...');
        }
        
        function showAllEvents() {
            alert('Showing all scheduled events...');
        }
    </script>

    <script src="../Scripts/utils.js"></script>
    <script src="../Scripts/mod4.js"></script>
    
    <script>
        // Function to handle the save from the form in the Create New Event section
        function saveNewEventFromForm() {
            // Submit the form programmatically
            document.getElementById('createEventForm').submit();
        }
        
        // Placeholder functions for various actions that might be missing
        function refreshSchedule() {
            updateCalendarView();
            showNotification('Schedule refreshed', 'info');
        }
        
        function viewAllEvents() {
            showNotification('Showing all events', 'info');
        }
        
        function sendNotifications(eventId) {
            showNotification('Sending notifications for event', 'info');
        }
        
        function exportEvent(eventId) {
            showNotification('Exporting event data', 'info');
        }
        
        function viewTasks(eventId) {
            showNotification('Showing tasks for event', 'info');
        }
        
        function scheduleEvent(eventId) {
            showNotification('Scheduling event', 'info');
        }
        
        // Override the existing functions if needed to ensure they work properly
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the page after all scripts are loaded
            if (typeof initializeData === 'function') {
                initializeData();
            }
            if (typeof updateAllViews === 'function') {
                updateAllViews();
            }
            if (typeof initializeFilters === 'function') {
                initializeFilters();
            }
            if (typeof setupEventListeners === 'function') {
                setupEventListeners();
            }
        });
        
        // Notification function
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'check-circle'}"></i>${message}`;
            
            // Add to container
            const container = document.getElementById('notification-container') || document.body.appendChild(document.createElement('div'));
            container.id = 'notification-container';
            container.appendChild(notification);
            
            // Remove after delay
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
    
    <!-- Hidden modals and additional elements that JavaScript expects -->
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000;"></div>
    
    <style>
        /* Notification styles */
        .notification {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification-success { background-color: #28a745; }
        .notification-error { background-color: #dc3545; }
        .notification-info { background-color: #17a2b8; }
        .notification-warning { background-color: #ffc107; color: #000; }
        
        /* Modal styles */
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