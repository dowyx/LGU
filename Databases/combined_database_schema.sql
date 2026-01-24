-- Combined Database Schema for Public Safety Campaign Management System
-- This script combines all database schemas from multiple modules into one unified database

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS public_safety_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE public_safety_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff', 'moderator', 'content_manager', 'user') DEFAULT 'staff',
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Campaigns table (from main setup and campaign module)
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('safety', 'health', 'emergency', 'vaccination', 'awareness', 'education', 'enforcement', 'awareness', 'education', 'enforcement', 'emergency') DEFAULT 'awareness',
    status ENUM('draft', 'upcoming', 'active', 'completed', 'cancelled', 'on_hold', 'planned', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    target_audience VARCHAR(255),
    budget DECIMAL(12,2) DEFAULT 0.00,
    actual_cost DECIMAL(12,2) DEFAULT 0.00,
    target_reach INT DEFAULT 0,
    actual_reach INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    location VARCHAR(255),
    created_by INT NOT NULL,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT `chk_dates` CHECK (end_date >= start_date),
    CONSTRAINT `chk_budget` CHECK (budget >= 0),
    CONSTRAINT `chk_completion` CHECK (completion_percentage >= 0 AND completion_percentage <= 100),
    CONSTRAINT `chk_reach` CHECK (target_reach >= 0 AND actual_reach >= 0)
    -- Foreign keys will be added after all referenced tables are created
);

-- Campaign milestones table
CREATE TABLE IF NOT EXISTS campaign_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    target_date DATE NOT NULL,
    actual_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assigned_to INT,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT chk_milestone_completion CHECK (completion_percentage >= 0 AND completion_percentage <= 100)
);

-- Campaign resources table
CREATE TABLE IF NOT EXISTS campaign_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    resource_type ENUM('personnel', 'equipment', 'venue', 'material', 'budget', 'other') NOT NULL,
    resource_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity_allocated INT DEFAULT 0,
    quantity_used INT DEFAULT 0,
    cost_per_unit DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('available', 'allocated', 'in_use', 'completed', 'unavailable') DEFAULT 'available',
    allocated_date DATE,
    returned_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT chk_resource_quantity CHECK (quantity_allocated >= 0 AND quantity_used >= 0),
    CONSTRAINT chk_resource_cost CHECK (cost_per_unit >= 0 AND total_cost >= 0)
);

-- Campaign team members table
CREATE TABLE IF NOT EXISTS campaign_team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('manager', 'coordinator', 'member', 'volunteer', 'consultant') DEFAULT 'member',
    responsibilities TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    hours_worked DECIMAL(8,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT chk_team_dates CHECK (end_date IS NULL OR end_date >= start_date),
    CONSTRAINT chk_team_hours CHECK (hours_worked >= 0)
);

-- Campaign documents table
CREATE TABLE IF NOT EXISTS campaign_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    document_type ENUM('plan', 'report', 'proposal', 'contract', 'permit', 'media', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    file_type VARCHAR(100),
    version VARCHAR(20) DEFAULT '1.0',
    status ENUM('draft', 'review', 'approved', 'archived') DEFAULT 'draft',
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT chk_document_size CHECK (file_size >= 0)
);

-- Campaign activities log table
CREATE TABLE IF NOT EXISTS campaign_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    activity_type ENUM('created', 'updated', 'deleted', 'status_change', 'milestone_completed', 'resource_allocated', 'team_assigned', 'document_uploaded') NOT NULL,
    description TEXT NOT NULL,
    user_id INT NOT NULL,
    activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_values JSON,
    new_values JSON,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Campaign categories table (for categorizing campaigns)
CREATE TABLE IF NOT EXISTS campaign_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Campaign category assignments
CREATE TABLE IF NOT EXISTS campaign_category_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES campaign_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_category (campaign_id, category_id)
);

-- Campaign templates table
CREATE TABLE IF NOT EXISTS campaign_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('safety', 'health', 'emergency', 'vaccination', 'awareness', 'education', 'enforcement') NOT NULL,
    template_data JSON, -- Contains default values for campaign fields
    milestone_template JSON, -- Contains default milestones
    resource_template JSON, -- Contains default resource requirements
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Incidents table
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('emergency', 'health', 'safety', 'fire', 'police', 'other') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('reported', 'active', 'resolved', 'closed') DEFAULT 'reported',
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    reported_by INT,
    assigned_to INT,
    response_time INT DEFAULT NULL, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Incident responses table
CREATE TABLE IF NOT EXISTS incident_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    responder_id INT,
    response_type ENUM('dispatch', 'investigation', 'resolution', 'follow_up') NOT NULL,
    notes TEXT,
    response_time INT, -- in seconds from incident creation
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (responder_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_type ENUM('seminar', 'workshop', 'training', 'conference', 'fair', 'other') DEFAULT 'other',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    location VARCHAR(255),
    capacity INT DEFAULT 0,
    registration_count INT DEFAULT 0,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled', 'planning') DEFAULT 'planning',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'absent') DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id)
);

-- Venues table
CREATE TABLE IF NOT EXISTS venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    capacity INT DEFAULT 0,
    equipment_available TEXT,
    availability_status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Resources table
CREATE TABLE IF NOT EXISTS event_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    total_quantity INT DEFAULT 0,
    available_quantity INT DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event resources allocation table
CREATE TABLE IF NOT EXISTS event_resource_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    resource_id INT NOT NULL,
    allocated_quantity INT DEFAULT 0,
    allocated_date DATE,
    deallocated_date DATE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES event_resources(id) ON DELETE CASCADE
);

-- Event feedback/satisfaction table
CREATE TABLE IF NOT EXISTS event_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comments TEXT,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Surveys table
CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    survey_type ENUM('campaign', 'event', 'service', 'research', 'general') DEFAULT 'general',
    status ENUM('draft', 'active', 'closed', 'analysis') DEFAULT 'draft',
    start_date DATETIME,
    end_date DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Survey questions table
CREATE TABLE IF NOT EXISTS survey_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'checkbox', 'rating', 'text', 'textarea') DEFAULT 'text',
    required BOOLEAN DEFAULT FALSE,
    options JSON, -- Store options for multiple choice/checkbox questions
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Survey responses table
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    respondent_id INT, -- User who responded (optional for anonymous surveys)
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45), -- For anonymous responses
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Survey answers table
CREATE TABLE IF NOT EXISTS survey_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT, -- For text/textarea answers
    answer_value VARCHAR(255), -- For selected options
    rating_value INT CHECK (rating_value >= 1 AND rating_value <= 5), -- For rating questions
    FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
);

-- Content repository table
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('document', 'image', 'video', 'audio', 'presentation', 'other', 'plan', 'report', 'proposal', 'contract', 'permit', 'media') DEFAULT 'document',
    category VARCHAR(100),
    tags JSON,
    file_path VARCHAR(500),
    file_size INT,
    file_type VARCHAR(100),
    status ENUM('draft', 'published', 'archived', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    access_level ENUM('public', 'internal', 'restricted') DEFAULT 'internal',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_date TIMESTAMP NULL,
    rejected_date TIMESTAMP NULL,
    expiry_date DATE NULL,
    download_count INT DEFAULT 0,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Content items table (alternative content structure)
CREATE TABLE IF NOT EXISTS content_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    category VARCHAR(100) NOT NULL,
    size VARCHAR(20),
    file_type VARCHAR(50),
    description TEXT,
    status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'pending',
    version VARCHAR(20) DEFAULT '1.0',
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_date TIMESTAMP NULL,
    rejected_date TIMESTAMP NULL,
    expiry_date DATE NULL,
    uploaded_by INT DEFAULT NULL,
    download_count INT DEFAULT 0,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Content categories table
CREATE TABLE IF NOT EXISTS content_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content tags table
CREATE TABLE IF NOT EXISTS content_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to link content items with tags (many-to-many relationship)
CREATE TABLE IF NOT EXISTS content_item_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES content_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_content_tag (content_item_id, tag_id)
);

-- Table for content download logs
CREATE TABLE IF NOT EXISTS download_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT NOT NULL,
    user_id INT,
    download_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE
);

-- Table for content approval workflow
CREATE TABLE IF NOT EXISTS approval_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT NOT NULL,
    approver_id INT,
    status_before VARCHAR(20),
    status_after VARCHAR(20),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id)
);

-- Survey feedback table (for general feedback that isn't part of a specific survey)
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    feedback_text TEXT NOT NULL,
    feedback_type ENUM('complaint', 'suggestion', 'compliment', 'inquiry') DEFAULT 'suggestion',
    rating INT CHECK (rating >= 1 AND rating <= 5),
    respondent_id INT, -- Optional user who provided feedback
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL
);



-- General feedback table (separate from survey feedback)
CREATE TABLE IF NOT EXISTS feedback_general (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    feedback_text TEXT NOT NULL,
    feedback_type ENUM('complaint', 'suggestion', 'compliment', 'inquiry') DEFAULT 'suggestion',
    rating INT CHECK (rating >= 1 AND rating <= 5),
    respondent_id INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Login attempts table for security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_email_attempts (email, attempted_at)
);

-- User activity log table
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'create', 'update', 'delete', 'view') NOT NULL,
    activity_details TEXT,
    activity_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, activity_time)
);

-- Segments table (for target group segmentation)
CREATE TABLE IF NOT EXISTS segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('demographic', 'behavioral', 'geographic', 'psychographic') NOT NULL,
    size_estimate INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_updated DATE,
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    criteria JSON,
    privacy_compliance_level VARCHAR(50) DEFAULT 'standard',
    created_by INT DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Demographic criteria table
CREATE TABLE IF NOT EXISTS demographic_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    age_min INT,
    age_max INT,
    gender ENUM('male', 'female', 'other', 'any') DEFAULT 'any',
    location VARCHAR(255),
    language_preference VARCHAR(50),
    education_level ENUM('elementary', 'high_school', 'college', 'graduate', 'other'),
    occupation VARCHAR(255),
    income_bracket VARCHAR(100),
    family_status ENUM('single', 'married', 'divorced', 'widowed', 'other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Behavioral criteria table
CREATE TABLE IF NOT EXISTS behavioral_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    past_engagement_score DECIMAL(5,2),
    response_history TEXT,
    preferred_channels JSON,
    service_usage_patterns TEXT,
    purchase_behavior TEXT,
    online_activity TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Geographic criteria table
CREATE TABLE IF NOT EXISTS geographic_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    country VARCHAR(100),
    state_province VARCHAR(100),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    region VARCHAR(100),
    coordinates_lat DECIMAL(10, 8),
    coordinates_lng DECIMAL(11, 8),
    radius_km DECIMAL(8, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Psychographic criteria table
CREATE TABLE IF NOT EXISTS psychographic_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    lifestyle_preferences JSON,
    personality_traits JSON,
    values_beliefs JSON,
    interests_hobbies JSON,
    social_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Segment members table
CREATE TABLE IF NOT EXISTS segment_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    member_id INT NOT NULL,  -- This would typically reference a person/users table
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'opt_out') DEFAULT 'active',
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_segment (segment_id, member_id)
);

-- Communication channels table
CREATE TABLE IF NOT EXISTS communication_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    preference_score DECIMAL(5,2) DEFAULT 0.00,  -- How much users prefer this channel
    reach_percentage DECIMAL(5,2) DEFAULT 0.00,   -- How much of the segment this channel reaches
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Segment channel preferences table
CREATE TABLE IF NOT EXISTS segment_channel_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    channel_id INT NOT NULL,
    preference_score DECIMAL(5,2) DEFAULT 0.00,
    reach_percentage DECIMAL(5,2) DEFAULT 0.00,
    effectiveness_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES communication_channels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_segment_channel (segment_id, channel_id)
);

-- A/B testing groups table
CREATE TABLE IF NOT EXISTS ab_testing_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    segment_id INT NOT NULL,
    group_type ENUM('control', 'variant_a', 'variant_b', 'variant_c') NOT NULL,
    size INT DEFAULT 0,
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Privacy compliance table
CREATE TABLE IF NOT EXISTS privacy_compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    regulation_type ENUM('gdpr', 'hipaa', 'ccpa', 'other') NOT NULL,
    compliance_status ENUM('compliant', 'non_compliant', 'pending_review') DEFAULT 'pending_review',
    last_audit_date DATE,
    next_audit_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE
);

-- Segment overlap table
CREATE TABLE IF NOT EXISTS segment_overlap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment1_id INT NOT NULL,
    segment2_id INT NOT NULL,
    overlap_percentage DECIMAL(5,2) DEFAULT 0.00,
    overlap_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment1_id) REFERENCES segments(id) ON DELETE CASCADE,
    FOREIGN KEY (segment2_id) REFERENCES segments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_segment_pair (segment1_id, segment2_id)
);

-- Segment analytics table
CREATE TABLE IF NOT EXISTS segment_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    measurement_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_segment_metric_date (segment_id, metric_name, measurement_date)
);

-- Distribution channels table
CREATE TABLE IF NOT EXISTS distribution_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    channel_type ENUM('email', 'sms', 'web', 'qr_code', 'social_media') DEFAULT 'web',
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    total_distributed INT DEFAULT 0,
    responses_received INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Survey distribution mapping
CREATE TABLE IF NOT EXISTS survey_distribution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    channel_id INT NOT NULL,
    distributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES distribution_channels(id) ON DELETE CASCADE
);

-- Campaign metrics table
CREATE TABLE IF NOT EXISTS campaign_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    date_recorded DATE NOT NULL,
    reach INT DEFAULT 0,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    roi DECIMAL(10,2) DEFAULT 0.00,
    cost DECIMAL(10,2) DEFAULT 0.00,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_date (campaign_id, date_recorded)
);

-- Campaign demographics table
CREATE TABLE IF NOT EXISTS campaign_demographics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    demographic_category VARCHAR(50) NOT NULL,
    demographic_value VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    reach INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_demo (campaign_id, demographic_category)
);

-- Channel analytics table
CREATE TABLE IF NOT EXISTS channel_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    channel_name VARCHAR(100) NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0.00,
    roi DECIMAL(10,2) DEFAULT 0.00,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_channel (campaign_id, channel_name)
);

-- Generated reports table
CREATE TABLE IF NOT EXISTS generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('performance', 'financial', 'audience', 'comparative', 'custom') DEFAULT 'custom',
    report_period_start DATE,
    report_period_end DATE,
    report_data JSON,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500),
    status ENUM('draft', 'generated', 'shared') DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Campaign scores table
CREATE TABLE IF NOT EXISTS campaign_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    engagement_score INT DEFAULT 0,
    roi_score INT DEFAULT 0,
    satisfaction_score INT DEFAULT 0,
    overall_score INT DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Predictive forecasts table
CREATE TABLE IF NOT EXISTS predictive_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    forecast_type ENUM('reach', 'engagement', 'roi', 'conversion') DEFAULT 'reach',
    forecast_date DATE NOT NULL,
    predicted_value DECIMAL(15,2) DEFAULT 0.00,
    confidence_level DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
);

-- Integration systems table
CREATE TABLE IF NOT EXISTS integration_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    system_type ENUM('health', 'police', 'emergency', 'data') NOT NULL,
    connected_system VARCHAR(255),
    description TEXT,
    status ENUM('active', 'disabled', 'maintenance', 'error') DEFAULT 'active',
    api_endpoint VARCHAR(500),
    api_version VARCHAR(20),
    rate_limit INT DEFAULT 100,
    last_sync TIMESTAMP,
    uptime_percentage DECIMAL(5,2) DEFAULT 99.99,
    avg_response_time_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Data flows table
CREATE TABLE IF NOT EXISTS data_flows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    flow_direction ENUM('inbound', 'outbound', 'bidirectional') DEFAULT 'bidirectional',
    data_points TEXT, -- JSON string of data points exchanged
    daily_count INT DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 100.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE
);

-- API logs table
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT,
    endpoint VARCHAR(500),
    method ENUM('GET', 'POST', 'PUT', 'DELETE') DEFAULT 'GET',
    status_code INT,
    response_time_ms INT,
    request_payload TEXT,
    response_payload TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL
);

-- Security & compliance table
CREATE TABLE IF NOT EXISTS security_compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    compliance_standard ENUM('HIPAA', 'CJIS', 'GDPR', 'SOX', 'OTHER') NOT NULL,
    status ENUM('compliant', 'non_compliant', 'pending', 'audit_required') DEFAULT 'pending',
    last_audit_date DATE,
    next_audit_date DATE,
    encryption_enabled BOOLEAN DEFAULT TRUE,
    encryption_type VARCHAR(50) DEFAULT 'AES-256',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE
);

-- Alert triggers table
CREATE TABLE IF NOT EXISTS alert_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(255) NOT NULL,
    trigger_condition TEXT,
    trigger_action TEXT,
    active BOOLEAN DEFAULT TRUE,
    last_triggered TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Integration logs table
CREATE TABLE IF NOT EXISTS integration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT,
    log_level ENUM('INFO', 'WARNING', 'ERROR', 'SUCCESS') DEFAULT 'INFO',
    message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL
);

-- Emergency alerts table
CREATE TABLE IF NOT EXISTS emergency_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(100) NOT NULL,
    alert_message TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    recipients TEXT, -- JSON array of recipient IDs/systems
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active'
);

-- Insert default users
INSERT INTO users (name, email, password, role, department) 
VALUES 
('Administrator', 'dricxterrosano0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT'),
('Dricxter Rosano', 'dricxterrosano0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Safety'),
('Jane Smith', 'jane.smith@safety.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Communications')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- Insert sample campaigns
INSERT INTO campaigns (name, description, type, status, start_date, end_date, target_audience, budget, target_reach, actual_reach, completion_percentage, created_by) 
VALUES 
('Summer Safety', 'Beach and pool safety awareness campaign', 'awareness', 'active', '2024-06-01', '2024-08-31', 'General Public', 50000.00, 10000, 7500, 75.00, 1),
('School Zone Safety', 'Safety awareness around schools', 'education', 'active', '2024-09-01', '2024-09-30', 'Parents & Students', 30000.00, 8000, 5200, 60.00, 1),
('Home Safety Week', 'Community workshops on home safety', 'education', 'planned', '2024-10-15', '2024-10-19', 'Homeowners', 25000.00, 10000, 0, 10.00, 1),
('Road Safety Month', 'Traffic safety awareness campaign', 'enforcement', 'completed', '2024-05-01', '2024-05-31', 'Drivers', 40000.00, 15000, 12500, 100.00, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert sample incidents
INSERT INTO incidents (title, description, type, severity, status, location, reported_by) 
VALUES 
('Fire Emergency - Downtown District', 'Reported fire incident in commercial building', 'fire', 'high', 'active', 'Downtown District', 2),
('Medical Emergency - City Park', 'Person requiring medical attention', 'emergency', 'medium', 'resolved', 'City Park', 2),
('Traffic Accident - Highway 101', 'Multi-vehicle collision reported', 'police', 'medium', 'active', 'Highway 101', 2)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default campaign categories
INSERT INTO campaign_categories (name, description, color_code, icon) VALUES
('Safety', 'Safety awareness and prevention campaigns', '#28a745', 'fa-shield-alt'),
('Health', 'Health education and medical campaigns', '#17a2b8', 'fa-heartbeat'),
('Emergency', 'Emergency response and preparedness', '#dc3545', 'fa-exclamation-triangle'),
('Vaccination', 'Vaccination drives and immunization campaigns', '#6f42c1', 'fa-syringe'),
('Awareness', 'General awareness and education campaigns', '#fd7e14', 'fa-bullhorn'),
('Education', 'Educational and training campaigns', '#20c997', 'fa-graduation-cap'),
('Enforcement', 'Policy enforcement and compliance campaigns', '#343a40', 'fa-gavel');

-- Insert default campaign templates
INSERT INTO campaign_templates (name, description, type, template_data, milestone_template, resource_template, created_by) VALUES
('Vaccination Campaign Template', 'Template for community vaccination drives', 'vaccination', 
'{"target_audience": "General Public", "priority": "high", "estimated_duration": "60 days"}',
'[{"name": "Planning Phase", "description": "Initial planning and coordination", "estimated_days": 7}, {"name": "Registration", "description": "Open registration for vaccination", "estimated_days": 14}, {"name": "First Dose Administration", "description": "Administer first vaccine dose", "estimated_days": 21}, {"name": "Second Dose Administration", "description": "Administer second vaccine dose", "estimated_days": 21}, {"name": "Follow-up", "description": "Monitor and follow up", "estimated_days": 7}]',
'[{"resource_type": "personnel", "resource_name": "Healthcare Workers", "quantity_per_1000": 2}, {"resource_type": "venue", "resource_name": "Vaccination Center", "quantity_per_campaign": 1}, {"resource_type": "equipment", "resource_name": "Vaccine Storage", "quantity_per_campaign": 1}]',
1),

('Emergency Preparedness Template', 'Template for emergency preparedness campaigns', 'emergency',
'{"target_audience": "Community Leaders, First Responders", "priority": "critical", "estimated_duration": "90 days"}',
'[{"name": "Risk Assessment", "description": "Assess community risks and vulnerabilities", "estimated_days": 14}, {"name": "Planning", "description": "Develop emergency response plans", "estimated_days": 21}, {"name": "Training", "description": "Train first responders and community leaders", "estimated_days": 30}, {"name": "Drills", "description": "Conduct emergency drills", "estimated_days": 14}, {"name": "Evaluation", "description": "Evaluate and refine plans", "estimated_days": 11}]',
'[{"resource_type": "personnel", "resource_name": "Emergency Coordinators", "quantity_per_campaign": 3}, {"resource_type": "equipment", "resource_name": "Communication Systems", "quantity_per_campaign": 1}, {"resource_type": "material", "resource_name": "Emergency Kits", "quantity_per_1000": 10}]',
1),

('Health Awareness Template', 'Template for health awareness campaigns', 'health',
'{"target_audience": "General Public, Schools", "priority": "medium", "estimated_duration": "45 days"}',
'[{"name": "Research", "description": "Research health topics and target audience", "estimated_days": 7}, {"name": "Content Development", "description": "Develop educational materials", "estimated_days": 14}, {"name": "Campaign Launch", "description": "Launch awareness campaign", "estimated_days": 7}, {"name": "Workshop Series", "description": "Conduct educational workshops", "estimated_days": 14}, {"name": "Health Fair", "description": "Organize community health fair", "estimated_days": 3}]',
'[{"resource_type": "personnel", "resource_name": "Health Educators", "quantity_per_campaign": 2}, {"resource_type": "venue", "resource_name": "Community Centers", "quantity_per_campaign": 2}, {"resource_type": "material", "resource_name": "Educational Materials", "quantity_per_1000": 50}]',
1),

('Safety Campaign Template', 'Template for community safety campaigns', 'safety',
'{"target_audience": "Residents, Business Owners", "priority": "medium", "estimated_duration": "60 days"}',
'[{"name": "Assessment", "description": "Assess community safety needs", "estimated_days": 10}, {"name": "Implementation", "description": "Implement safety measures", "estimated_days": 30}, {"name": "Education", "description": "Conduct safety education", "estimated_days": 14}, {"name": "Evaluation", "description": "Evaluate effectiveness", "estimated_days": 6}]',
'[{"resource_type": "personnel", "resource_name": "Safety Officers", "quantity_per_campaign": 2}, {"resource_type": "equipment", "resource_name": "Safety Equipment", "quantity_per_1000": 5}, {"resource_type": "material", "resource_name": "Safety Signs", "quantity_per_campaign": 20}]',
1);

-- Insert default safety categories
INSERT INTO content_categories (name, description, icon_class) VALUES
('Emergency Response', 'Critical emergency procedures and response protocols', 'fa-exclamation-triangle'),
('Fire Safety', 'Fire prevention, detection, and response materials', 'fa-fire'),
('Public Health', 'Health awareness and disease prevention resources', 'fa-heartbeat'),
('Disaster Preparedness', 'Natural disaster preparedness and recovery guides', 'fa-cloud-showers-heavy'),
('Traffic Safety', 'Road safety awareness and accident prevention', 'fa-car-crash'),
('Cyber Security', 'Digital safety and cybersecurity awareness', 'fa-shield-alt');

-- Insert default communication channels
INSERT INTO communication_channels (name, description, preference_score, reach_percentage) VALUES
('Email', 'Electronic mail communication', 65.00, 92.00),
('SMS', 'Short Message Service', 25.00, 98.00),
('Traditional Media', 'TV, Radio, Print', 8.00, 75.00),
('Social Media', 'Facebook, Twitter, Instagram', 2.00, 45.00);

-- Insert default distribution channels
INSERT INTO distribution_channels (name, channel_type, response_rate, total_distributed, responses_received) VALUES
('Email', 'email', 45.00, 1245, 560),
('SMS', 'sms', 32.00, 892, 285),
('Web Portal', 'web', 68.00, 856, 582),
('QR Code', 'qr_code', 52.00, 465, 242);

-- Insert default venues
INSERT INTO venues (name, address, capacity, equipment_available, availability_status) VALUES
('City Community Center', 'Main Street, City Center', 200, 'Projector,WiFi,Parking,Sound System', 'available'),
('Public Safety HQ - Conference Room A', 'Safety Department Building', 50, 'AV System,Whiteboard,Projector', 'available'),
('Senior Community Center', 'Elderly Services Area', 80, 'Chairs,Tables,Accessible Facilities', 'available');

-- Insert default resources
INSERT INTO event_resources (name, category, total_quantity, available_quantity, description) VALUES
('Projectors', 'AV Equipment', 15, 12, 'Standard presentation projectors'),
('Audio Systems', 'AV Equipment', 10, 8, 'Microphones and speakers'),
('Chairs', 'Furniture', 500, 450, 'Standard folding chairs'),
('First Aid Kits', 'Safety', 30, 25, 'Emergency medical supplies'),
('Tables', 'Furniture', 50, 45, 'Standard folding tables'),
('Laptops', 'Technology', 20, 18, 'For registration/check-in purposes');

-- Insert default segments for demonstration
INSERT INTO segments (name, description, type, size_estimate, engagement_rate, status, last_updated, created_by) VALUES
('High-Risk Population', 'Individuals with chronic conditions and elderly', 'demographic', 12847, 92.00, 'active', '2024-07-15', 1),
('Senior Citizens (65+)', 'Population aged 65 and above', 'demographic', 45231, 62.00, 'active', '2024-07-15', 1),
('Parents with Children', 'Families with children of any age', 'demographic', 28456, 45.00, 'active', '2024-07-15', 1),
('College Students', 'Students enrolled in higher education', 'demographic', 15782, 38.00, 'active', '2024-07-14', 1),
('Past Campaign Responders', 'Individuals who responded to previous campaigns', 'behavioral', 8452, 95.00, 'active', '2024-07-13', 1),
('Downtown Residents', 'Residents of urban core areas', 'geographic', 23456, 65.00, 'active', '2024-07-12', 1),
('Healthcare Workers', 'Medical professionals and healthcare staff', 'demographic', 5234, 88.00, 'draft', '2024-07-11', 1);

-- Insert demographic criteria for the default segments
INSERT INTO demographic_criteria (segment_id, age_min, age_max, location, education_level, family_status) VALUES
(1, 65, 120, NULL, NULL, NULL),  -- High-Risk Population (age 65+)
(2, 65, 120, NULL, NULL, NULL),  -- Senior Citizens (age 65+)
(3, NULL, NULL, NULL, NULL, 'married'),  -- Parents with Children (family status married)
(4, 18, 25, NULL, 'college', NULL),  -- College Students (college age, education level)
(5, NULL, NULL, NULL, NULL, NULL),  -- Past Campaign Responders (no specific demographic)
(6, NULL, NULL, 'Downtown', NULL, NULL),  -- Downtown Residents (location specific)
(7, 25, 65, NULL, 'college', NULL);  -- Healthcare Workers (working age, education level)

-- Insert sample integration systems
INSERT INTO integration_systems (name, system_type, connected_system, description, status, api_endpoint, api_version, rate_limit) VALUES
('Public Health Database', 'health', 'State Health Department', 'Immunization Registry & Disease Surveillance', 'active', 'https://health-api.gov.ph/v2', '2.1', 100),
('Police CAD System', 'police', 'City Police Department', 'Computer-Aided Dispatch & Incident Reports', 'active', 'https://police-api.gov.ph/v1', '1.4', 50),
('Hospital EHR System', 'health', 'Regional Hospital Network', 'Emergency Department & Bed Availability', 'maintenance', 'https://hospital-api.health.gov', '2.0', 75),
('Emergency Services', 'emergency', 'County Fire Department', 'Fire & Rescue Dispatch Systems', 'active', 'https://emergency-api.gov.ph/v3', '3.0', 100),
('Traffic Management System', 'data', 'Transportation Department', 'Real-time traffic and road condition data', 'active', 'https://traffic-api.gov.ph/v1', '1.0', 200);

-- Insert sample data flows
INSERT INTO data_flows (integration_id, flow_direction, data_points, daily_count, success_rate) VALUES
(1, 'bidirectional', '["immunizations", "disease_reports", "lab_results"]', 1245, 99.2),
(2, 'bidirectional', '["incidents", "dispatch", "resources"]', 892, 98.7),
(3, 'bidirectional', '["bed_status", "ed_capacity", "specialists"]', 567, 99.5),
(4, 'bidirectional', '["alerts", "deployments", "resources"]', 342, 99.8),
(5, 'bidirectional', '["traffic_flow", "accidents", "road_closures"]', 289, 99.1);

-- Insert sample security compliance records
INSERT INTO security_compliance (integration_id, compliance_standard, status, encryption_enabled, encryption_type) VALUES
(1, 'HIPAA', 'compliant', TRUE, 'AES-256'),
(1, 'GDPR', 'compliant', TRUE, 'AES-256'),
(2, 'CJIS', 'compliant', TRUE, 'AES-256'),
(3, 'HIPAA', 'compliant', TRUE, 'AES-256'),
(4, 'CJIS', 'compliant', TRUE, 'AES-256');

-- Insert sample alert triggers
INSERT INTO alert_triggers (trigger_name, trigger_condition, trigger_action) VALUES
('Disease Outbreak Detected', 'More than 10 disease reports in 24 hours', 'Automatically notify Public Health & Emergency Services'),
('Major Incident Reported', 'Incident severity is critical', 'Alert Police, Fire, and Medical Teams'),
('Hospital Capacity Critical', 'Emergency department capacity exceeds 90%', 'Redirect emergencies to alternative facilities'),
('Weather Emergency Declared', 'Weather alert level is severe', 'Activate emergency response protocols');

-- Insert sample integration logs
INSERT INTO integration_logs (integration_id, log_level, message) VALUES
(2, 'SUCCESS', 'Police Incident API sync completed successfully'),
(1, 'INFO', 'Health data flow increased by 15% (threshold alert)'),
(3, 'WARNING', 'Hospital EHR system entered maintenance mode'),
(4, 'SUCCESS', 'Emergency alert test notification sent successfully'),
(1, 'SUCCESS', 'Data encryption key rotation completed'),
(1, 'ERROR', 'Temporary connection loss with Public Health Database (restored)'),
(1, 'SUCCESS', 'API rate limits adjusted based on usage patterns');

-- Insert additional distribution channels
INSERT IGNORE INTO distribution_channels (name, channel_type, response_rate, total_distributed, responses_received) VALUES
('Email', 'email', 45.00, 1245, 560),
('SMS', 'sms', 32.00, 892, 285),
('Web Portal', 'web', 68.00, 856, 582),
('QR Code', 'qr_code', 52.00, 465, 242);

-- Insert additional venues
INSERT IGNORE INTO venues (name, address, capacity, equipment_available, availability_status) VALUES
('City Community Center', 'Main Street, City Center', 200, 'Projector,WiFi,Parking,Sound System', 'available'),
('Public Safety HQ - Conference Room A', 'Safety Department Building', 50, 'AV System,Whiteboard,Projector', 'available'),
('Senior Community Center', 'Elderly Services Area', 80, 'Chairs,Tables,Accessible Facilities', 'available');

-- Insert additional event resources
INSERT IGNORE INTO event_resources (name, category, total_quantity, available_quantity, description) VALUES
('Projectors', 'AV Equipment', 15, 12, 'Standard presentation projectors'),
('Audio Systems', 'AV Equipment', 10, 8, 'Microphones and speakers'),
('Chairs', 'Furniture', 500, 450, 'Standard folding chairs'),
('First Aid Kits', 'Safety', 30, 25, 'Emergency medical supplies'),
('Tables', 'Furniture', 50, 45, 'Standard folding tables'),
('Laptops', 'Technology', 20, 18, 'For registration/check-in purposes');

-- Insert additional sample surveys
INSERT IGNORE INTO surveys (title, description, survey_type, status, start_date, end_date, created_by) VALUES
('Summer Safety Campaign Feedback', 'Measuring campaign effectiveness and public awareness', 'campaign', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('Community First Aid Workshop Evaluation', 'Post-event feedback for workshop improvement', 'event', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 1),
('Emergency Response Satisfaction', 'Service quality assessment', 'service', 'analysis', DATE_SUB(NOW(), INTERVAL 30 DAY), NOW(), 1);

-- Insert additional campaign analytics data
INSERT IGNORE INTO campaign_demographics (campaign_id, demographic_category, demographic_value, percentage, reach) VALUES
(1, 'age_group', '25-44', 45.00, 110250),
(1, 'age_group', '45-64', 32.00, 78400),
(1, 'age_group', '18-24', 18.00, 44100),
(1, 'age_group', '65+', 5.00, 12250);

INSERT IGNORE INTO channel_analytics (campaign_id, channel_name, impressions, clicks, conversions, cost, roi, engagement_rate) VALUES
(1, 'email', 50000, 2500, 125, 50000.00, 3.4, 5.00),
(1, 'social_media', 100000, 8000, 400, 100000.00, 4.2, 8.00),
(1, 'radio', 75000, 1500, 75, 75000.00, 2.8, 2.00),
(1, 'tv', 125000, 3750, 188, 125000.00, 3.1, 3.00);

INSERT IGNORE INTO generated_reports (report_name, report_type, report_period_start, report_period_end, generated_by, status) VALUES
('Q4 Performance Summary', 'performance', '2024-10-01', '2024-12-31', 1, 'generated'),
('Financial Analysis Report', 'financial', '2024-01-01', '2024-12-31', 1, 'generated'),
('Audience Insights Q1', 'audience', '2024-01-01', '2024-03-31', 1, 'generated');

INSERT IGNORE INTO campaign_scores (campaign_id, engagement_score, roi_score, satisfaction_score, overall_score) VALUES
(1, 88, 95, 85, 92),
(2, 75, 80, 78, 78),
(3, 92, 98, 90, 94);

-- Additional sample surveys with different types
INSERT IGNORE INTO surveys (title, description, survey_type, status, start_date, end_date) VALUES
('Public Safety Mobile App Feedback', 'App usability and feature requests', 'service', 'closed', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('Community Safety Needs Assessment', 'Identifying priority safety concerns', 'general', 'draft', NULL, NULL);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON campaigns(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_type ON campaigns(type);
CREATE INDEX IF NOT EXISTS idx_campaigns_dates ON campaigns(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_campaigns_created_by ON campaigns(created_by);
CREATE INDEX IF NOT EXISTS idx_campaigns_priority ON campaigns(priority);

CREATE INDEX IF NOT EXISTS idx_milestones_campaign ON campaign_milestones(campaign_id);
CREATE INDEX IF NOT EXISTS idx_milestones_status ON campaign_milestones(status);
CREATE INDEX IF NOT EXISTS idx_milestones_date ON campaign_milestones(target_date);

CREATE INDEX IF NOT EXISTS idx_resources_campaign ON campaign_resources(campaign_id);
CREATE INDEX IF NOT EXISTS idx_resources_type ON campaign_resources(resource_type);
CREATE INDEX IF NOT EXISTS idx_resources_status ON campaign_resources(status);

CREATE INDEX IF NOT EXISTS idx_team_campaign ON campaign_team_members(campaign_id);
CREATE INDEX IF NOT EXISTS idx_team_user ON campaign_team_members(user_id);
CREATE INDEX IF NOT EXISTS idx_team_role ON campaign_team_members(role);

CREATE INDEX IF NOT EXISTS idx_documents_campaign ON campaign_documents(campaign_id);
CREATE INDEX IF NOT EXISTS idx_documents_type ON campaign_documents(document_type);
CREATE INDEX IF NOT EXISTS idx_documents_status ON campaign_documents(status);

CREATE INDEX IF NOT EXISTS idx_activities_campaign ON campaign_activities(campaign_id);
CREATE INDEX IF NOT EXISTS idx_activities_type ON campaign_activities(activity_type);
CREATE INDEX IF NOT EXISTS idx_activities_date ON campaign_activities(activity_date);

CREATE INDEX IF NOT EXISTS idx_surveys_status ON surveys(status);
CREATE INDEX IF NOT EXISTS idx_surveys_type ON surveys(survey_type);
CREATE INDEX IF NOT EXISTS idx_surveys_dates ON surveys(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_events_status ON events(status);
CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_dates ON events(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_campaign_metrics_date ON campaign_metrics(date_recorded);

CREATE INDEX IF NOT EXISTS idx_segments_type ON segments(type);
CREATE INDEX IF NOT EXISTS idx_segments_status ON segments(status);
CREATE INDEX IF NOT EXISTS idx_segments_created_at ON segments(created_at);
CREATE INDEX IF NOT EXISTS idx_segment_members_segment ON segment_members(segment_id);
CREATE INDEX IF NOT EXISTS idx_segment_analytics_date ON segment_analytics(measurement_date);

CREATE INDEX IF NOT EXISTS idx_survey_questions_survey ON survey_questions(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_questions_type ON survey_questions(question_type);
CREATE INDEX IF NOT EXISTS idx_survey_responses_survey ON survey_responses(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_responses_respondent ON survey_responses(respondent_id);
CREATE INDEX IF NOT EXISTS idx_survey_answers_response ON survey_answers(response_id);
CREATE INDEX IF NOT EXISTS idx_survey_answers_question ON survey_answers(question_id);
CREATE INDEX IF NOT EXISTS idx_feedback_respondent ON feedback(respondent_id);
CREATE INDEX IF NOT EXISTS idx_feedback_type ON feedback(feedback_type);
CREATE INDEX IF NOT EXISTS idx_distribution_channels_type ON distribution_channels(channel_type);
CREATE INDEX IF NOT EXISTS idx_survey_distribution_survey ON survey_distribution(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_distribution_channel ON survey_distribution(channel_id);
CREATE INDEX IF NOT EXISTS idx_event_registrations_event ON event_registrations(event_id);
CREATE INDEX IF NOT EXISTS idx_event_registrations_user ON event_registrations(user_id);
CREATE INDEX IF NOT EXISTS idx_venues_status ON venues(availability_status);
CREATE INDEX IF NOT EXISTS idx_event_resources_category ON event_resources(category);
CREATE INDEX IF NOT EXISTS idx_event_resource_allocations_event ON event_resource_allocations(event_id);
CREATE INDEX IF NOT EXISTS idx_event_resource_allocations_resource ON event_resource_allocations(resource_id);
CREATE INDEX IF NOT EXISTS idx_event_feedback_event ON event_feedback(event_id);
CREATE INDEX IF NOT EXISTS idx_event_feedback_user ON event_feedback(user_id);
CREATE INDEX IF NOT EXISTS idx_campaign_demographics_campaign ON campaign_demographics(campaign_id);
CREATE INDEX IF NOT EXISTS idx_channel_analytics_campaign ON channel_analytics(campaign_id);
CREATE INDEX IF NOT EXISTS idx_generated_reports_type ON generated_reports(report_type);
CREATE INDEX IF NOT EXISTS idx_generated_reports_generated_by ON generated_reports(generated_by);
CREATE INDEX IF NOT EXISTS idx_campaign_scores_campaign ON campaign_scores(campaign_id);
CREATE INDEX IF NOT EXISTS idx_predictive_forecasts_campaign ON predictive_forecasts(campaign_id);
CREATE INDEX IF NOT EXISTS idx_predictive_forecasts_type ON predictive_forecasts(forecast_type);

-- Create views for common queries
CREATE VIEW IF NOT EXISTS campaign_summary AS
SELECT 
    c.id,
    c.name,
    c.type,
    c.status,
    c.start_date,
    c.end_date,
    c.budget,
    c.actual_cost,
    c.target_reach,
    c.actual_reach,
    c.completion_percentage,
    c.priority,
    COUNT(DISTINCT cm.id) as milestone_count,
    COUNT(DISTINCT CASE WHEN cm.status = 'completed' THEN cm.id END) as completed_milestones,
    COUNT(DISTINCT ct.id) as team_members_count,
    COUNT(DISTINCT cr.id) as resources_count,
    u.name as created_by_name,
    DATEDIFF(c.end_date, c.start_date) as duration_days
FROM campaigns c
LEFT JOIN campaign_milestones cm ON c.id = cm.campaign_id
LEFT JOIN campaign_team_members ct ON c.id = ct.campaign_id
LEFT JOIN campaign_resources cr ON c.id = cr.campaign_id
LEFT JOIN users u ON c.created_by = u.id
GROUP BY c.id;

CREATE VIEW IF NOT EXISTS upcoming_milestones AS
SELECT 
    cm.id,
    cm.name,
    cm.description,
    cm.target_date,
    cm.status,
    cm.priority,
    c.name as campaign_name,
    c.type as campaign_type,
    DATEDIFF(cm.target_date, CURDATE()) as days_until,
    CASE 
        WHEN cm.target_date < CURDATE() AND cm.status != 'completed' THEN 'overdue'
        WHEN DATEDIFF(cm.target_date, CURDATE()) <= 7 THEN 'urgent'
        WHEN DATEDIFF(cm.target_date, CURDATE()) <= 14 THEN 'soon'
        ELSE 'normal'
    END as urgency
FROM campaign_milestones cm
JOIN campaigns c ON cm.campaign_id = c.id
WHERE cm.status != 'completed'
ORDER BY cm.target_date ASC;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_create_campaign(
    IN p_name VARCHAR(255),
    IN p_description TEXT,
    IN p_type VARCHAR(50),
    IN p_status VARCHAR(50),
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_target_audience VARCHAR(255),
    IN p_budget DECIMAL(12,2),
    IN p_target_reach INT,
    IN p_priority VARCHAR(20),
    IN p_location VARCHAR(255),
    IN p_created_by INT
)
BEGIN
    DECLARE v_campaign_id INT;
    
    INSERT INTO campaigns (
        name, description, type, status, start_date, end_date,
        target_audience, budget, target_reach, priority, location, created_by
    ) VALUES (
        p_name, p_description, p_type, p_status, p_start_date, p_end_date,
        p_target_audience, p_budget, p_target_reach, p_priority, p_location, p_created_by
    );
    
    SET v_campaign_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO campaign_activities (campaign_id, activity_type, description, user_id)
    VALUES (v_campaign_id, 'created', CONCAT('Campaign "', p_name, '" was created'), p_created_by);
    
    SELECT v_campaign_id as campaign_id;
END //

CREATE PROCEDURE IF NOT EXISTS sp_update_campaign_status(
    IN p_campaign_id INT,
    IN p_new_status VARCHAR(50),
    IN p_user_id INT
)
BEGIN
    DECLARE v_old_status VARCHAR(50);
    
    -- Get old status
    SELECT status INTO v_old_status FROM campaigns WHERE id = p_campaign_id;
    
    -- Update status
    UPDATE campaigns 
    SET status = p_new_status, updated_at = CURRENT_TIMESTAMP
    WHERE id = p_campaign_id;
    
    -- Log activity
    INSERT INTO campaign_activities (campaign_id, activity_type, description, user_id, old_values, new_values)
    VALUES (
        p_campaign_id, 
        'status_change', 
        CONCAT('Status changed from "', v_old_status, '" to "', p_new_status, '"'),
        p_user_id,
        JSON_OBJECT('old_status', v_old_status),
        JSON_OBJECT('new_status', p_new_status)
    );
END //

CREATE PROCEDURE IF NOT EXISTS sp_get_campaign_statistics(IN p_user_id INT)
BEGIN
    SELECT 
        COUNT(*) as total_campaigns,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_campaigns,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_campaigns,
        COUNT(CASE WHEN status = 'upcoming' THEN 1 END) as upcoming_campaigns,
        SUM(budget) as total_budget,
        SUM(actual_cost) as total_actual_cost,
        AVG(completion_percentage) as avg_completion,
        COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_campaigns,
        COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_priority_campaigns
    FROM campaigns 
    WHERE created_by = p_user_id OR p_user_id IN (SELECT id FROM users WHERE role IN ('admin', 'manager'));
END //


-- Create triggers for automatic logging
DELIMITER //

CREATE TRIGGER IF NOT EXISTS tr_campaign_after_update
AFTER UPDATE ON campaigns
FOR EACH ROW
BEGIN
    IF OLD.name != NEW.name OR OLD.description != NEW.description OR 
       OLD.budget != NEW.budget OR OLD.target_audience != NEW.target_audience THEN
        INSERT INTO campaign_activities (campaign_id, activity_type, description, user_id, old_values, new_values)
        VALUES (
            NEW.id,
            'updated',
            'Campaign details were updated',
            NEW.created_by,
            JSON_OBJECT(
                'name', OLD.name,
                'description', OLD.description,
                'budget', OLD.budget,
                'target_audience', OLD.target_audience
            ),
            JSON_OBJECT(
                'name', NEW.name,
                'description', NEW.description,
                'budget', NEW.budget,
                'target_audience', NEW.target_audience
            )
        );
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS tr_campaign_after_delete
AFTER DELETE ON campaigns
FOR EACH ROW
BEGIN
    INSERT INTO campaign_activities (campaign_id, activity_type, description, user_id, old_values)
    VALUES (
        OLD.id,
        'deleted',
        CONCAT('Campaign "', OLD.name, '" was deleted'),
        OLD.created_by,
        JSON_OBJECT(
            'name', OLD.name,
            'type', OLD.type,
            'status', OLD.status
        )
    );
END //

-- Add indexes to referenced columns if they don't exist
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_id (id);

-- Clean up any invalid foreign key references before adding constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Campaigns table foreign keys
UPDATE campaigns SET created_by = NULL WHERE created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaigns SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE campaigns ADD CONSTRAINT fk_campaigns_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE campaigns ADD CONSTRAINT fk_campaigns_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Surveys table foreign keys
UPDATE surveys SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE surveys ADD CONSTRAINT fk_surveys_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Events table foreign keys
UPDATE events SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE events ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Content table foreign keys
UPDATE content SET uploaded_by = NULL WHERE uploaded_by IS NOT NULL AND uploaded_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE content ADD CONSTRAINT fk_content_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL;

-- Feedback table foreign keys
UPDATE feedback SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE feedback SET campaign_id = NULL WHERE campaign_id IS NOT NULL AND campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE feedback SET incident_id = NULL WHERE incident_id IS NOT NULL AND incident_id NOT IN (SELECT id FROM incidents WHERE id IS NOT NULL);
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_incident_id FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL;

-- Segments table foreign keys
UPDATE segments SET created_by = NULL WHERE created_by IS NOT NULL AND created_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE segments ADD CONSTRAINT fk_segments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Incident responses table foreign keys
UPDATE incident_responses SET responder_id = NULL WHERE responder_id IS NOT NULL AND responder_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE incident_responses ADD CONSTRAINT fk_incident_responses_responder_id FOREIGN KEY (responder_id) REFERENCES users(id) ON DELETE SET NULL;

-- Event registrations table foreign keys
UPDATE event_registrations SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE event_registrations ADD CONSTRAINT fk_event_registrations_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Campaign-related foreign keys
UPDATE campaign_resources SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_team_members SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_team_members SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_documents SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_activities SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_activities SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_metrics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_demographics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE channel_analytics SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE generated_reports SET generated_by = NULL WHERE generated_by IS NOT NULL AND generated_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE campaign_scores SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE predictive_forecasts SET campaign_id = NULL WHERE campaign_id IS NOT NULL AND campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);

ALTER TABLE campaign_resources ADD CONSTRAINT fk_campaign_resources_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_team_members ADD CONSTRAINT fk_campaign_team_members_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_team_members ADD CONSTRAINT fk_campaign_team_members_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE campaign_documents ADD CONSTRAINT fk_campaign_documents_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_activities ADD CONSTRAINT fk_campaign_activities_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_activities ADD CONSTRAINT fk_campaign_activities_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE campaign_metrics ADD CONSTRAINT fk_campaign_metrics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_demographics ADD CONSTRAINT fk_campaign_demographics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE channel_analytics ADD CONSTRAINT fk_channel_analytics_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE generated_reports ADD CONSTRAINT fk_generated_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE campaign_scores ADD CONSTRAINT fk_campaign_scores_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE predictive_forecasts ADD CONSTRAINT fk_predictive_forecasts_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;

-- Integration systems foreign keys
UPDATE integration_logs SET integration_id = NULL WHERE integration_id IS NOT NULL AND integration_id NOT IN (SELECT id FROM integration_systems WHERE id IS NOT NULL);
UPDATE api_logs SET integration_id = NULL WHERE integration_id IS NOT NULL AND integration_id NOT IN (SELECT id FROM integration_systems WHERE id IS NOT NULL);
ALTER TABLE integration_logs ADD CONSTRAINT fk_integration_logs_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL;
ALTER TABLE api_logs ADD CONSTRAINT fk_api_logs_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE SET NULL;
ALTER TABLE security_compliance ADD CONSTRAINT fk_security_compliance_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE;
ALTER TABLE data_flows ADD CONSTRAINT fk_data_flows_integration_id FOREIGN KEY (integration_id) REFERENCES integration_systems(id) ON DELETE CASCADE;

-- Incidents table foreign keys
UPDATE incidents SET reported_by = NULL WHERE reported_by IS NOT NULL AND reported_by NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE incidents SET assigned_to = NULL WHERE assigned_to IS NOT NULL AND assigned_to NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_reported_by FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Survey-related foreign keys
UPDATE survey_questions SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_responses SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_responses SET respondent_id = NULL WHERE respondent_id IS NOT NULL AND respondent_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE survey_answers SET response_id = NULL WHERE response_id NOT IN (SELECT id FROM survey_responses WHERE id IS NOT NULL);
UPDATE survey_answers SET question_id = NULL WHERE question_id NOT IN (SELECT id FROM survey_questions WHERE id IS NOT NULL);
UPDATE survey_distribution SET survey_id = NULL WHERE survey_id NOT IN (SELECT id FROM surveys WHERE id IS NOT NULL);
UPDATE survey_distribution SET channel_id = NULL WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM distribution_channels WHERE id IS NOT NULL);

ALTER TABLE survey_questions ADD CONSTRAINT fk_survey_questions_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_responses ADD CONSTRAINT fk_survey_responses_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_responses ADD CONSTRAINT fk_survey_responses_respondent_id FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE survey_answers ADD CONSTRAINT fk_survey_answers_response_id FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE;
ALTER TABLE survey_answers ADD CONSTRAINT fk_survey_answers_question_id FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE;
ALTER TABLE survey_distribution ADD CONSTRAINT fk_survey_distribution_survey_id FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE;
ALTER TABLE survey_distribution ADD CONSTRAINT fk_survey_distribution_channel_id FOREIGN KEY (channel_id) REFERENCES distribution_channels(id) ON DELETE CASCADE;

-- Event-related foreign keys
UPDATE event_resource_allocations SET event_id = NULL WHERE event_id NOT IN (SELECT id FROM events WHERE id IS NOT NULL);
UPDATE event_resource_allocations SET resource_id = NULL WHERE resource_id IS NOT NULL AND resource_id NOT IN (SELECT id FROM event_resources WHERE id IS NOT NULL);
UPDATE event_feedback SET event_id = NULL WHERE event_id NOT IN (SELECT id FROM events WHERE id IS NOT NULL);
UPDATE event_feedback SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);

ALTER TABLE event_resource_allocations ADD CONSTRAINT fk_event_resource_allocations_event_id FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE;
ALTER TABLE event_resource_allocations ADD CONSTRAINT fk_event_resource_allocations_resource_id FOREIGN KEY (resource_id) REFERENCES event_resources(id) ON DELETE CASCADE;
ALTER TABLE event_feedback ADD CONSTRAINT fk_event_feedback_event_id FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE;
ALTER TABLE event_feedback ADD CONSTRAINT fk_event_feedback_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Campaign-related foreign keys (continued)
UPDATE campaign_milestones SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_category_assignments SET campaign_id = NULL WHERE campaign_id NOT IN (SELECT id FROM campaigns WHERE id IS NOT NULL);
UPDATE campaign_category_assignments SET category_id = NULL WHERE category_id NOT IN (SELECT id FROM campaign_categories WHERE id IS NOT NULL);

ALTER TABLE campaign_milestones ADD CONSTRAINT fk_campaign_milestones_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_category_assignments ADD CONSTRAINT fk_campaign_category_assignments_campaign_id FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE campaign_category_assignments ADD CONSTRAINT fk_campaign_category_assignments_category_id FOREIGN KEY (category_id) REFERENCES campaign_categories(id) ON DELETE CASCADE;

-- Segmentation-related foreign keys
UPDATE demographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE behavioral_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE geographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE psychographic_criteria SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_members SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_members SET member_id = NULL WHERE member_id NOT IN (SELECT id FROM users WHERE id IS NOT NULL);
UPDATE segment_channel_preferences SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_channel_preferences SET channel_id = NULL WHERE channel_id IS NOT NULL AND channel_id NOT IN (SELECT id FROM communication_channels WHERE id IS NOT NULL);
UPDATE ab_testing_groups SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE privacy_compliance SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_overlap SET segment1_id = NULL WHERE segment1_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_overlap SET segment2_id = NULL WHERE segment2_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);
UPDATE segment_analytics SET segment_id = NULL WHERE segment_id NOT IN (SELECT id FROM segments WHERE id IS NOT NULL);

ALTER TABLE demographic_criteria ADD CONSTRAINT fk_demographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE behavioral_criteria ADD CONSTRAINT fk_behavioral_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE geographic_criteria ADD CONSTRAINT fk_geographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE psychographic_criteria ADD CONSTRAINT fk_psychographic_criteria_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_members ADD CONSTRAINT fk_segment_members_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_channel_preferences ADD CONSTRAINT fk_segment_channel_preferences_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_channel_preferences ADD CONSTRAINT fk_segment_channel_preferences_channel_id FOREIGN KEY (channel_id) REFERENCES communication_channels(id) ON DELETE CASCADE;
ALTER TABLE ab_testing_groups ADD CONSTRAINT fk_ab_testing_groups_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE privacy_compliance ADD CONSTRAINT fk_privacy_compliance_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_overlap ADD CONSTRAINT fk_segment_overlap_segment1_id FOREIGN KEY (segment1_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_overlap ADD CONSTRAINT fk_segment_overlap_segment2_id FOREIGN KEY (segment2_id) REFERENCES segments(id) ON DELETE CASCADE;
ALTER TABLE segment_analytics ADD CONSTRAINT fk_segment_analytics_segment_id FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

DELIMITER ;