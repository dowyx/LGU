<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the target group segmentation model
require_once '../Models/TargetGroupSegmentation.php';

$segModel = new TargetGroupSegmentation();
$segments = $segModel->getSegments();
$analytics = $segModel->getSegmentAnalytics();
$channels = $segModel->getCommunicationChannels();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/group.css">
    <link rel="stylesheet" href="../Styles/userprofile.css">
    <title>Target Group Segmentation</title>
    <style>
        /* Additional CSS for new features */
        .feature-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
        }
        
        .feature-tab {
            background: none;
            border: none;
            padding: 10px 20px;
            color: var(--text-color);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .feature-tab:hover {
            background: var(--dark-gray);
        }
        
        .feature-tab.active {
            background: var(--accent);
            color: white;
        }
        
        .feature-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .feature-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .demographic-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .demographic-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
        }
        
        .demographic-chart {
            height: 200px;
            margin-top: 15px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .summary-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-color);
            margin: 10px 0;
        }
        
        .summary-label {
            font-size: 14px;
            color: var(--text-gray);
        }
        
        .behavior-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .behavior-tag {
            padding: 6px 12px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .behavior-tag.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .behavior-tag.inactive {
            background: var(--dark-gray);
            color: var(--text-gray);
        }
        
        .map-container {
            height: 400px;
            background: var(--dark-gray);
            border-radius: 12px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        
        .map-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10;
            max-width: 300px;
        }
        
        .map-marker {
            position: absolute;
            width: 12px;
            height: 12px;
            background: var(--accent);
            border-radius: 50%;
            border: 2px solid white;
            transform: translate(-50%, -50%);
            cursor: pointer;
        }
        
        .map-marker.high-density {
            width: 16px;
            height: 16px;
            background: var(--danger);
        }
        
        .profile-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            background: var(--card-bg);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .profile-stat {
            text-align: center;
            padding: 15px;
            background: var(--dark-gray);
            border-radius: 8px;
        }
        
        .channel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .channel-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        .channel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .channel-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .channel-progress {
            height: 8px;
            background: var(--dark-gray);
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .channel-progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .channel-stats {
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .feature-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 10px;
            }
            
            .feature-tab {
                padding: 8px 15px;
                font-size: 14px;
            }
            
            .demographic-grid,
            .channel-grid {
                grid-template-columns: 1fr;
            }
            
            .map-overlay {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 15px;
                max-width: 100%;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
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
                    <a href="../home.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./Modules/Module-1.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Campaign Planning & Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/Content-Repository.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span class="nav-text">Content Repository</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/Target-Group-Segmentation.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Target Group Segmentation</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/EventSeminarManagement.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Event & Seminar Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/SurveyFeedbackCollection.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="nav-text">Survey & Feedback Collection</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/CampaignAnalyticsReports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Campaign Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../Models/HealthPoliceIntegration.php" class="nav-link">
                        <i class="fas fa-link"></i>
                        <span class="nav-text">Community</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h2>Target Group Segmentation</h2>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search segments, criteria, tags..." id="searchInput">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)); ?></div>
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></div>
                            <div style="font-size: 13px; color: var(--text-gray);">Segmentation Analyst</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Header with Action Buttons -->
            <div class="module-header">
                <div>
                    <h1 class="module-title">Target Group Segmentation</h1>
                    <p class="module-subtitle">Create and manage audience segments for targeted communication</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-secondary" onclick="exportSegments()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <label class="btn btn-secondary" style="cursor: pointer;">
                        <i class="fas fa-upload"></i> Import
                        <input type="file" accept=".json,.csv" style="display: none;" onchange="importSegments(event)">
                    </label>
                    <button class="btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Segment
                    </button>
                </div>
            </div>

            <!-- Advanced Features Tabs -->
            <div class="module-card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <div class="card-title">Advanced Audience Analysis</div>
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                
                <div class="feature-tabs">
                    <button class="feature-tab active" onclick="switchFeature('demographic')">
                        <i class="fas fa-user-circle"></i>
                        Demographic Analysis
                    </button>
                    <button class="feature-tab" onclick="switchFeature('behavioral')">
                        <i class="fas fa-chart-bar"></i>
                        Behavioral Segments
                    </button>
                    <button class="feature-tab" onclick="switchFeature('geographic')">
                        <i class="fas fa-map"></i>
                        Geographic Mapping
                    </button>
                    <button class="feature-tab" onclick="switchFeature('profiling')">
                        <i class="fas fa-id-card"></i>
                        Audience Profiling
                    </button>
                    <button class="feature-tab" onclick="switchFeature('channel')">
                        <i class="fas fa-mobile-alt"></i>
                        Channel Preferences
                    </button>
                </div>
            </div>

            <!-- Feature Content Sections -->
            
            <!-- Demographic Analysis Content -->
            <div id="demographicFeature" class="feature-content active">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Demographic Analysis</div>
                        <div class="card-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="summary-value" id="medianAge">42</div>
                            <div class="summary-label">Median Age</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="genderRatio">52%</div>
                            <div class="summary-label">Female Population</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="avgHousehold">3.2</div>
                            <div class="summary-label">Avg. Household Size</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="incomeLevel">Mid</div>
                            <div class="summary-label">Average Income Level</div>
                        </div>
                    </div>
                    
                    <div class="demographic-grid">
                        <div class="demographic-card">
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Age Distribution</h4>
                            <div class="demographic-chart">
                                <canvas id="detailedAgeChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="demographic-card">
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Gender Distribution</h4>
                            <div class="demographic-chart">
                                <canvas id="genderChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="demographic-card">
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Education Level</h4>
                            <div class="demographic-chart">
                                <canvas id="educationChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="demographic-card">
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Employment Status</h4>
                            <div class="demographic-chart">
                                <canvas id="employmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn" onclick="exportDemographicData()">
                            <i class="fas fa-download"></i> Export Demographic Report
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Behavioral Segments Content -->
            <div id="behavioralFeature" class="feature-content">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Behavioral Segmentation</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    
                    <div class="behavior-tags" id="behaviorTags">
                        <!-- Behavioral tags will be generated dynamically -->
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Behavioral Patterns</h4>
                            <div id="behaviorPatterns">
                                <!-- Behavioral patterns will be loaded here -->
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--text-color); margin-bottom: 15px;">Engagement Metrics</h4>
                            <div style="height: 250px;">
                                <canvas id="engagementChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn btn-primary" onclick="createBehavioralSegment()">
                            <i class="fas fa-plus"></i> Create Behavioral Segment
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Geographic Mapping Content -->
            <div id="geographicFeature" class="feature-content">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Geographic Audience Distribution</div>
                        <div class="card-icon">
                            <i class="fas fa-map"></i>
                        </div>
                    </div>
                    
                    <div class="map-overlay">
                        <h4 style="margin: 0 0 10px 0;">Population Density</h4>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            <div style="width: 12px; height: 12px; background: var(--accent); border-radius: 50%;"></div>
                            <span style="font-size: 12px;">Low Density (< 100)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 16px; height: 16px; background: var(--danger); border-radius: 50%;"></div>
                            <span style="font-size: 12px;">High Density (≥ 100)</span>
                        </div>
                    </div>
                    
                    <div class="map-container" id="geographicMap">
                        <!-- Map will be generated here -->
                    </div>
                    
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="summary-value" id="totalAreas">8</div>
                            <div class="summary-label">Covered Areas</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="highRiskAreas">3</div>
                            <div class="summary-label">High-Risk Zones</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="urbanAreas">5</div>
                            <div class="summary-label">Urban Areas</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value" id="ruralAreas">3</div>
                            <div class="summary-label">Rural Areas</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Audience Profiling Content -->
            <div id="profilingFeature" class="feature-content">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Audience Persona Profiles</div>
                        <div class="card-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">SR</div>
                            <div>
                                <h4 style="margin: 0 0 5px 0;">Senior Resident</h4>
                                <p style="color: var(--text-gray); margin: 0; font-size: 14px;">
                                    Age 60+, limited mobility, prefers traditional communication
                                </p>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">68%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">SMS Preference</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">2.3</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Avg. Campaigns/Month</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">High</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Risk Awareness</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">42%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Response Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">YA</div>
                            <div>
                                <h4 style="margin: 0 0 5px 0;">Young Adult</h4>
                                <p style="color: var(--text-gray); margin: 0; font-size: 14px;">
                                    Age 18-30, tech-savvy, prefers digital communication
                                </p>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">85%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Mobile App Usage</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">4.7</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Avg. Campaigns/Month</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">Medium</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Risk Awareness</div>
                            </div>
                            <div class="profile-stat">
                                <div style="font-size: 18px; font-weight: 600; color: var(--text-color);">65%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Response Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn" onclick="createNewPersona()">
                            <i class="fas fa-plus"></i> Create New Persona
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Channel Preferences Content -->
            <div id="channelFeature" class="feature-content">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Communication Channel Preferences</div>
                        <div class="card-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                    </div>
                    
                    <div class="channel-grid">
                        <div class="channel-card" onclick="viewChannelDetail('sms')">
                            <div class="channel-icon" style="color: var(--success);">
                                <i class="fas fa-sms"></i>
                            </div>
                            <h4 style="margin: 0 0 10px 0;">SMS/Text</h4>
                            <p style="color: var(--text-gray); font-size: 14px; margin-bottom: 15px;">
                                Direct text messaging
                            </p>
                            <div class="channel-stats">
                                <div style="font-size: 24px; font-weight: 600; color: var(--text-color);">68%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Preference Rate</div>
                                <div class="channel-progress">
                                    <div class="channel-progress-fill" style="width: 68%;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="channel-card" onclick="viewChannelDetail('email')">
                            <div class="channel-icon" style="color: var(--accent);">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4 style="margin: 0 0 10px 0;">Email</h4>
                            <p style="color: var(--text-gray); font-size: 14px; margin-bottom: 15px;">
                                Email newsletters and alerts
                            </p>
                            <div class="channel-stats">
                                <div style="font-size: 24px; font-weight: 600; color: var(--text-color);">45%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Preference Rate</div>
                                <div class="channel-progress">
                                    <div class="channel-progress-fill" style="width: 45%;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="channel-card" onclick="viewChannelDetail('mobile')">
                            <div class="channel-icon" style="color: #8b5cf6;">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4 style="margin: 0 0 10px 0;">Mobile App</h4>
                            <p style="color: var(--text-gray); font-size: 14px; margin-bottom: 15px;">
                                Push notifications and in-app messages
                            </p>
                            <div class="channel-stats">
                                <div style="font-size: 24px; font-weight: 600; color: var(--text-color);">72%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Preference Rate</div>
                                <div class="channel-progress">
                                    <div class="channel-progress-fill" style="width: 72%;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="channel-card" onclick="viewChannelDetail('social')">
                            <div class="channel-icon" style="color: var(--warning);">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <h4 style="margin: 0 0 10px 0;">Social Media</h4>
                            <p style="color: var(--text-gray); font-size: 14px; margin-bottom: 15px;">
                                Facebook, Twitter, and other platforms
                            </p>
                            <div class="channel-stats">
                                <div style="font-size: 24px; font-weight: 600; color: var(--text-color);">38%</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Preference Rate</div>
                                <div class="channel-progress">
                                    <div class="channel-progress-fill" style="width: 38%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn" onclick="optimizeChannelMix()">
                            <i class="fas fa-cog"></i> Optimize Channel Mix
                        </button>
                    </div>
                </div>
            </div>

            <!-- Original Content Below -->

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-item active">All Segments</div>
                <div class="filter-item">High Priority</div>
                <div class="filter-item">Demographic</div>
                <div class="filter-item">Behavioral</div>
                <div class="filter-item">Geographic</div>
                <div class="filter-item">Active Campaigns</div>
            </div>

            <!-- Rest of the original content remains the same -->
            <div class="module-grid">
                <!-- Segment Library -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Library</div>
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="segment-list">
                        <?php foreach (array_slice($segments, 0, 4) as $segment): ?>
                        <div class="segment-item <?php echo strtolower(str_replace(' ', '-', explode(' ', $segment['name'])[0] ?? '')); ?>">
                            <div class="segment-name"><?php echo htmlspecialchars($segment['name']); ?></div>
                            <div class="segment-count"><?php echo $segment['size_estimate']; ?> individuals</div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo $segment['engagement_rate']; ?>%; background-color: 
                                    <?php 
                                        if ($segment['engagement_rate'] > 80) echo 'var(--success);';
                                        elseif ($segment['engagement_rate'] > 60) echo 'var(--accent);';
                                        else echo 'var(--warning);';
                                    ?>"></div>
                            </div>
                            <div class="segment-tags">
                                <span class="segment-tag"><?php echo ucfirst($segment['type'] ?? 'demographic'); ?></span>
                                <span class="segment-tag"><?php echo $segment['status'] ?? 'draft'; ?> Priority</span>
                                <span class="segment-tag"><?php echo round($segment['engagement_rate'] ?? 0); ?>% Engaged</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Create New Segment -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Create New Segment</div>
                        <div class="card-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                    </div>
                    <div class="segment-builder">
                        <div class="criteria-group">
                            <h4>Demographic Criteria</h4>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Age Range</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Location (City/District)</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Language Preference</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Education Level</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Occupation</span>
                                </label>
                            </div>
                        </div>
                        <div class="criteria-group">
                            <h4>Behavioral Criteria</h4>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Past Campaign Engagement</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Response History</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Preferred Communication Channels</span>
                                </label>
                            </div>
                            <div class="criteria-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Service Usage Patterns</span>
                                </label>
                            </div>
                        </div>
                        <button class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-magic"></i> Build Segment
                        </button>
                    </div>
                </div>

                <!-- Segment Analytics -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Analytics</div>
                        <div class="card-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                    <div class="analytics-dashboard">
                        <div class="metric">
                            <div class="metric-value"><?php echo $analytics['total_segments']; ?></div>
                            <div class="metric-label">Active Segments</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo $analytics['engagement_stats']['average']; ?>%</div>
                            <div class="metric-label">Avg. Engagement Rate</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo round($analytics['total_members'] / max($analytics['total_segments'], 1)); ?></div>
                            <div class="metric-label">Avg. Size per Segment</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo count($channels); ?></div>
                            <div class="metric-label">Communication Channels</div>
                        </div>
                    </div>
                    <div class="segment-visualization">
                        <div class="visualization-placeholder">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Segment Performance</h4>
                            <p>Visual analytics dashboard</p>
                        </div>
                    </div>
                </div>

                <!-- Communication Channels -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Communication Channels</div>
                        <div class="card-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                    </div>
                    <div class="channel-distribution">
                        <?php foreach (array_slice($channels, 0, 4) as $channel): ?>
                        <div class="channel-item">
                            <i class="fas <?php 
                                if (strpos(strtolower($channel['name']), 'email') !== false) echo 'fa-envelope';
                                elseif (strpos(strtolower($channel['name']), 'sms') !== false) echo 'fa-mobile-alt';
                                elseif (strpos(strtolower($channel['name']), 'social') !== false) echo 'fa-hashtag';
                                else echo 'fa-newspaper';
                            ?>"></i>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($channel['name']); ?></div>
                                <div class="channel-stats"><?php echo $channel['preference_score']; ?>% prefer • <?php echo $channel['reach_percentage']; ?>% reach</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-sliders-h"></i> Optimize Channels
                    </button>
                </div>

                <!-- A/B Testing Groups -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">A/B Testing Groups</div>
                        <div class="card-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>
                    <div class="testing-groups">
                        <div class="test-group">
                            <div class="group-name">Group A - Control</div>
                            <div class="group-size">5,000 recipients • 68% response</div>
                        </div>
                        <div class="test-group">
                            <div class="group-name">Group B - Variant 1</div>
                            <div class="group-size">5,000 recipients • 72% response</div>
                        </div>
                        <div class="test-group">
                            <div class="group-name">Group C - Variant 2</div>
                            <div class="group-size">5,000 recipients • 81% response</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">Test Result</div>
                        <div style="color: var(--success); font-size: 14px;">
                            <i class="fas fa-arrow-up"></i> Variant 2 shows 13% improvement
                        </div>
                    </div>
                </div>

                <!-- Privacy Compliance -->
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Privacy Compliance</div>
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="compliance-status">
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>GDPR Compliant</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>HIPAA Compliant</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>Data Encrypted</span>
                        </div>
                        <div class="status-item compliant">
                            <i class="fas fa-check-circle"></i>
                            <span>Consent Managed</span>
                        </div>
                    </div>
                    <p style="margin-top: 15px; font-size: 14px; color: var(--text-gray);">
                        All data handling follows strict privacy regulations with regular audits
                    </p>
                </div>
            </div>

            <!-- Segment Table -->
            <div class="module-card" style="margin-top: 30px;">
                <div class="card-header">
                    <div class="card-title">All Segments</div>
                    <div class="card-icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <table class="segment-table">
                    <thead>
                        <tr>
                            <th>Segment Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Engagement Rate</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segments as $segment): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($segment['name']); ?></div>
                                <div style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($segment['description']); ?></div>
                            </td>
                            <td><span class="segment-type type-<?php echo $segment['type'] ?? 'demographic'; ?>"><?php echo ucfirst($segment['type'] ?? 'demographic'); ?></span></td>
                            <td><?php echo number_format($segment['size_estimate'] ?? 0); ?></td>
                            <td>
                               <div><?php echo $segment['engagement_rate'] ?? 0; ?>%</div>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($segment['updated_at'])); ?></td>
                            <td><span style="color: <?php echo ($segment['status'] ?? 'draft') === 'active' ? 'var(--success)' : 'var(--text-gray)'; ?>"><?php echo ucfirst($segment['status'] ?? 'draft'); ?></span></td>
                            <td>
                                <div class="segment-actions">
                                    <i class="fas fa-edit" title="Edit" onclick="editSegment(<?php echo $segment['id']; ?>)"></i>
                                    <i class="fas fa-chart-line" title="Analytics" onclick="showAnalytics(<?php echo $segment['id']; ?>)"></i>
                                    <i class="fas fa-bullhorn" title="Target" onclick="targetSegment(<?php echo $segment['id']; ?>)"></i>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Segment Overlap Analysis -->
            <div class="module-grid" style="margin-top: 30px;">
                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Segment Overlap Analysis</div>
                        <div class="card-icon">
                            <i class="fas fa-venn-diagram"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--danger); border-radius: 4px; margin-right: 10px;"></div>
                            <span>High-Risk Population</span>
                        </div>
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--warning); border-radius: 4px; margin-right: 10px;"></div>
                            <span>Senior Citizens</span>
                        </div>
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 20px; height: 20px; background-color: var(--accent); border-radius: 4px; margin-right: 10px;"></div>
                            <span>Parents with Children</span>
                        </div>
                        <div style="margin-top: 20px; padding: 15px; background-color: var(--dark-gray); border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 5px;">Overlap Insight</div>
                            <div style="font-size: 14px; color: var(--text-gray);">
                                42% of High-Risk individuals are also Senior Citizens
                            </div>
                        </div>
                    </div>
                </div>

                <div class="module-card">
                    <div class="card-header">
                        <div class="card-title">Quick Segmentation</div>
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> By Location
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-birthday-cake"></i> By Age Group
                        </button>
                        <button class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-history"></i> By Engagement History
                        </button>
                        <button class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-heartbeat"></i> By Health Condition
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../Scripts/mod3.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Feature switching functionality
        let currentFeature = 'demographic';
        
        function switchFeature(featureName) {
            currentFeature = featureName;
            
            // Update tab buttons
            document.querySelectorAll('.feature-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show selected feature
            document.querySelectorAll('.feature-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${featureName}Feature`).classList.add('active');
            
            // Initialize feature-specific content
            switch(featureName) {
                case 'demographic':
                    loadDemographicAnalysis();
                    break;
                case 'behavioral':
                    loadBehavioralSegments();
                    break;
                case 'geographic':
                    loadGeographicMapping();
                    break;
                case 'profiling':
                    loadAudienceProfiling();
                    break;
                case 'channel':
                    loadChannelPreferences();
                    break;
            }
        }
        
        // Demographic Analysis Functions
        function loadDemographicAnalysis() {
            // This would typically make an AJAX call to get real data
            console.log('Loading demographic analysis...');
            
            // For demo purposes, create sample charts
            setTimeout(() => {
                createDemographicCharts();
            }, 100);
        }
        
        function createDemographicCharts() {
            // Age Distribution Chart
            const ageCtx = document.getElementById('detailedAgeChart');
            if (ageCtx) {
                new Chart(ageCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['0-17', '18-25', '26-35', '36-50', '51-65', '66+'],
                        datasets: [{
                            label: 'Population',
                            data: [12, 45, 78, 92, 67, 34],
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--accent'),
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Gender Distribution
            const genderCtx = document.getElementById('genderChart');
            if (genderCtx) {
                new Chart(genderCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Male', 'Female', 'Other'],
                        datasets: [{
                            data: [48, 52, 0],
                            backgroundColor: [
                                getComputedStyle(document.documentElement).getPropertyValue('--accent'),
                                '#8b5cf6',
                                getComputedStyle(document.documentElement).getPropertyValue('--success')
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Education Level
            const educationCtx = document.getElementById('educationChart');
            if (educationCtx) {
                new Chart(educationCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['High School', 'Some College', 'Bachelor', 'Master', 'PhD'],
                        datasets: [{
                            label: 'Count',
                            data: [120, 85, 67, 34, 12],
                            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--success')
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Employment Status
            const employmentCtx = document.getElementById('employmentChart');
            if (employmentCtx) {
                new Chart(employmentCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: ['Employed', 'Unemployed', 'Self-Employed', 'Student', 'Retired'],
                        datasets: [{
                            data: [45, 12, 18, 15, 10],
                            backgroundColor: [
                                getComputedStyle(document.documentElement).getPropertyValue('--accent'),
                                getComputedStyle(document.documentElement).getPropertyValue('--warning'),
                                getComputedStyle(document.documentElement).getPropertyValue('--success'),
                                '#8b5cf6',
                                getComputedStyle(document.documentElement).getPropertyValue('--danger')
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }
        
        function exportDemographicData() {
            alert('Exporting demographic data...');
            // In a real implementation, this would generate and download a CSV/PDF
        }
        
        // Behavioral Segments Functions
        function loadBehavioralSegments() {
            const behaviorTags = [
                { name: 'High Engagement', count: 156, active: true },
                { name: 'Low Response', count: 42, active: false },
                { name: 'Night Active', count: 89, active: true },
                { name: 'Weekend Users', count: 67, active: true },
                { name: 'Mobile Only', count: 124, active: true },
                { name: 'Multi-Channel', count: 78, active: true },
                { name: 'Seasonal', count: 23, active: false },
                { name: 'High Risk', count: 45, active: true }
            ];
            
            const container = document.getElementById('behaviorTags');
            if (container) {
                container.innerHTML = behaviorTags.map(tag => `
                    <div class="behavior-tag ${tag.active ? 'active' : 'inactive'}">
                        <i class="fas ${tag.active ? 'fa-check' : 'fa-times'}"></i>
                        ${tag.name} (${tag.count})
                    </div>
                `).join('');
            }
            
            // Create engagement chart
            const engagementCtx = document.getElementById('engagementChart');
            if (engagementCtx) {
                new Chart(engagementCtx.getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: ['SMS', 'Email', 'App', 'Social', 'Calls', 'Web'],
                        datasets: [{
                            label: 'Engagement Level',
                            data: [85, 45, 72, 38, 62, 54],
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--accent'),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        }
        
        function createBehavioralSegment() {
            alert('Creating behavioral segment...');
            // In a real implementation, this would open a modal
        }
        
        // Geographic Mapping Functions
        function loadGeographicMapping() {
            const mapContainer = document.getElementById('geographicMap');
            if (!mapContainer) return;
            
            // Create simulated map with markers
            const locations = [
                { name: 'Manila', x: 25, y: 40, density: 150 },
                { name: 'Quezon City', x: 40, y: 35, density: 120 },
                { name: 'Makati', x: 30, y: 50, density: 180 },
                { name: 'Tondo', x: 20, y: 45, density: 200 },
                { name: 'Navotas', x: 15, y: 30, density: 90 },
                { name: 'Malabon', x: 25, y: 25, density: 110 },
                { name: 'Pasay', x: 35, y: 55, density: 130 },
                { name: 'Mandaluyong', x: 45, y: 45, density: 95 }
            ];
            
            // Clear map
            mapContainer.innerHTML = '';
            
            // Add markers
            locations.forEach(location => {
                const marker = document.createElement('div');
                marker.className = `map-marker ${location.density >= 100 ? 'high-density' : ''}`;
                marker.style.left = `${location.x}%`;
                marker.style.top = `${location.y}%`;
                marker.title = `${location.name}: ${location.density} contacts`;
                
                marker.addEventListener('click', () => {
                    showLocationDetails(location);
                });
                
                mapContainer.appendChild(marker);
            });
            
            // Add area labels
            locations.forEach(location => {
                const label = document.createElement('div');
                label.style.position = 'absolute';
                label.style.left = `${location.x}%`;
                label.style.top = `${location.y + 3}%`;
                label.style.transform = 'translateX(-50%)';
                label.style.fontSize = '11px';
                label.style.color = getComputedStyle(document.documentElement).getPropertyValue('--text-color');
                label.style.background = 'rgba(255,255,255,0.9)';
                label.style.padding = '2px 6px';
                label.style.borderRadius = '3px';
                label.textContent = location.name;
                
                mapContainer.appendChild(label);
            });
        }
        
        function showLocationDetails(location) {
            alert(`${location.name}: ${location.density} contacts`);
            // In a real implementation, this would open a modal with details
        }
        
        // Audience Profiling Functions
        function loadAudienceProfiling() {
            console.log('Loading audience profiles...');
        }
        
        function createNewPersona() {
            alert('Creating new audience persona...');
            // In a real implementation, this would open a modal
        }
        
        // Channel Preferences Functions
        function loadChannelPreferences() {
            console.log('Loading channel preferences...');
        }
        
        function viewChannelDetail(channelId) {
            const channelData = {
                sms: {
                    name: 'SMS/Text Messaging',
                    description: 'Direct text messages for urgent alerts',
                    preference: 68,
                    reach: 92,
                    cost: 'Low',
                    bestFor: 'Urgent alerts, time-sensitive information'
                },
                email: {
                    name: 'Email',
                    description: 'Email newsletters and detailed updates',
                    preference: 45,
                    reach: 78,
                    cost: 'Very Low',
                    bestFor: 'Detailed reports, newsletters, non-urgent updates'
                },
                mobile: {
                    name: 'Mobile App',
                    description: 'Push notifications and in-app messages',
                    preference: 72,
                    reach: 65,
                    cost: 'Medium',
                    bestFor: 'Engaged users, frequent updates, interactive content'
                },
                social: {
                    name: 'Social Media',
                    description: 'Facebook, Twitter, and other social platforms',
                    preference: 38,
                    reach: 85,
                    cost: 'Low',
                    bestFor: 'Community engagement, awareness campaigns'
                }
            };
            
            const data = channelData[channelId];
            alert(`${data.name}\nPreference: ${data.preference}%\nBest For: ${data.bestFor}`);
        }
        
        function optimizeChannelMix() {
            alert('Optimizing channel mix...');
            // In a real implementation, this would show optimization recommendations
        }
        
        // Original functions (keep these)
        function openCreateModal() {
            alert('Create Segment functionality would open a modal here');
        }
        
        function editSegment(segmentId) {
            alert('Editing segment ID: ' + segmentId);
        }
        
        function showAnalytics(segmentId) {
            alert('Showing analytics for segment ID: ' + segmentId);
        }
        
        function targetSegment(segmentId) {
            alert('Targeting segment ID: ' + segmentId + ' for campaign');
        }
        
        function exportSegments() {
            alert('Exporting segments...');
        }
        
        function importSegments(event) {
            const file = event.target.files[0];
            if (file) {
                alert('Importing segments from: ' + file.name);
            }
        }
        
        // Initialize features on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load the first feature by default
            loadDemographicAnalysis();
            loadGeographicMapping();
            
            // Add search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    console.log('Searching for:', searchTerm);
                });
            }
            
            // Add filter functionality
            document.querySelectorAll('.filter-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.filter-item').forEach(el => {
                        el.classList.remove('active');
                    });
                    this.classList.add('active');
                    console.log('Filter selected:', this.textContent);
                });
            });
        });
    </script>

</body>
</html>