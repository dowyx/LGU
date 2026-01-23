-- Database setup script for Public Safety Campaign Management System
-- Run this script to create the necessary database tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS public_safety_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE public_safety_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Campaigns table
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('awareness', 'education', 'enforcement', 'emergency') DEFAULT 'awareness',
    status ENUM('draft', 'planned', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE,
    end_date DATE,
    target_audience VARCHAR(255),
    budget DECIMAL(10,2),
    target_reach INT DEFAULT 0,
    actual_reach INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
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
    type ENUM('seminar', 'workshop', 'training', 'meeting', 'other') DEFAULT 'seminar',
    status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
    start_datetime DATETIME,
    end_datetime DATETIME,
    location VARCHAR(255),
    max_participants INT DEFAULT 0,
    current_participants INT DEFAULT 0,
    organizer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Event participants table
CREATE TABLE IF NOT EXISTS event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    participant_id INT,
    participant_name VARCHAR(255),
    participant_email VARCHAR(255),
    registration_status ENUM('registered', 'confirmed', 'attended', 'no_show') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Surveys table
CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('feedback', 'satisfaction', 'assessment', 'poll') DEFAULT 'feedback',
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    target_audience VARCHAR(255),
    start_date DATE,
    end_date DATE,
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
    question_type ENUM('text', 'multiple_choice', 'rating', 'yes_no', 'scale') DEFAULT 'text',
    options JSON, -- For multiple choice questions
    is_required BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

-- Survey responses table
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_id INT NOT NULL,
    respondent_id INT,
    response_text TEXT,
    response_value INT, -- For rating/scale questions
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (respondent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Content repository table
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('document', 'image', 'video', 'audio', 'presentation', 'other') DEFAULT 'document',
    category VARCHAR(100),
    tags JSON,
    file_path VARCHAR(500),
    file_size INT,
    file_type VARCHAR(100),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    access_level ENUM('public', 'internal', 'restricted') DEFAULT 'internal',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    incident_id INT,
    type ENUM('complaint', 'suggestion', 'compliment', 'other') DEFAULT 'suggestion',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    satisfaction_score INT CHECK (satisfaction_score >= 0 AND satisfaction_score <= 100),
    respondent_name VARCHAR(255),
    respondent_email VARCHAR(255),
    respondent_phone VARCHAR(20),
    status ENUM('new', 'reviewed', 'resolved', 'closed') DEFAULT 'new',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
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

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, department) 
VALUES 
('Administrator', 'dricxterrosano0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT'),
('Dricxter Rosano', 'dricxterrosano0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Safety'),
('Jane Smith', 'jane.smith@safety.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Communications')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- Insert sample campaigns
INSERT INTO campaigns (name, description, type, status, start_date, end_date, target_audience, budget, target_reach, actual_reach, completion_percentage) 
VALUES 
('Summer Safety', 'Beach and pool safety awareness campaign', 'awareness', 'active', '2024-06-01', '2024-08-31', 'General Public', 50000.00, 10000, 7500, 75.00),
('School Zone Safety', 'Safety awareness around schools', 'education', 'active', '2024-09-01', '2024-09-30', 'Parents & Students', 30000.00, 8000, 5200, 60.00),
('Home Safety Week', 'Community workshops on home safety', 'education', 'planned', '2024-10-15', '2024-10-19', 'Homeowners', 25000.00, 10000, 0, 10.00),
('Road Safety Month', 'Traffic safety awareness campaign', 'enforcement', 'completed', '2024-05-01', '2024-05-31', 'Drivers', 40000.00, 15000, 12500, 100.00)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert sample incidents
INSERT INTO incidents (title, description, type, severity, status, location, reported_by) 
VALUES 
('Fire Emergency - Downtown District', 'Reported fire incident in commercial building', 'fire', 'high', 'active', 'Downtown District', 2),
('Medical Emergency - City Park', 'Person requiring medical attention', 'emergency', 'medium', 'resolved', 'City Park', 2),
('Traffic Accident - Highway 101', 'Multi-vehicle collision reported', 'police', 'medium', 'active', 'Highway 101', 2)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Create indexes for better performance
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_campaigns_dates ON campaigns(start_date, end_date);
CREATE INDEX idx_incidents_status ON incidents(status);
CREATE INDEX idx_incidents_type ON incidents(type);
CREATE INDEX idx_events_dates ON events(start_datetime, end_datetime);
CREATE INDEX idx_surveys_status ON surveys(status);
CREATE INDEX idx_feedback_status ON feedback(status);
CREATE INDEX idx_content_type ON content(type);
CREATE INDEX idx_content_status ON content(status);

-- Set up foreign key constraints
ALTER TABLE campaigns ADD CONSTRAINT fk_campaigns_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_reported_by FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE events ADD CONSTRAINT fk_events_organizer_id FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE surveys ADD CONSTRAINT fk_surveys_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE content ADD CONSTRAINT fk_content_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON public_safety_db.* TO 'safety_user'@'localhost';
-- FLUSH PRIVILEGES;
