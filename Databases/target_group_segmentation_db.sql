-- Database for Target Group Segmentation Module
CREATE DATABASE IF NOT EXISTS target_group_segmentation_db;

USE target_group_segmentation_db;

-- Table for storing segment information
CREATE TABLE segments (
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

-- Table for storing demographic criteria
CREATE TABLE demographic_criteria (
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

-- Table for storing behavioral criteria
CREATE TABLE behavioral_criteria (
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

-- Table for storing geographic criteria
CREATE TABLE geographic_criteria (
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

-- Table for storing psychographic criteria
CREATE TABLE psychographic_criteria (
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

-- Table for storing segment membership (many-to-many relationship)
CREATE TABLE segment_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    member_id INT NOT NULL,  -- This would typically reference a person/users table
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'opt_out') DEFAULT 'active',
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_segment (segment_id, member_id)
);

-- Table for storing communication channel preferences
CREATE TABLE communication_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    preference_score DECIMAL(5,2) DEFAULT 0.00,  -- How much users prefer this channel
    reach_percentage DECIMAL(5,2) DEFAULT 0.00,   -- How much of the segment this channel reaches
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for storing channel preferences per segment
CREATE TABLE segment_channel_preferences (
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

-- Table for storing A/B testing groups
CREATE TABLE ab_testing_groups (
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

-- Table for storing privacy compliance information
CREATE TABLE privacy_compliance (
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

-- Table for storing segment overlap analysis
CREATE TABLE segment_overlap (
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

-- Table for storing segment analytics
CREATE TABLE segment_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    measurement_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES segments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_segment_metric_date (segment_id, metric_name, measurement_date)
);

-- Insert default communication channels
INSERT INTO communication_channels (name, description, preference_score, reach_percentage) VALUES
('Email', 'Electronic mail communication', 65.00, 92.00),
('SMS', 'Short Message Service', 25.00, 98.00),
('Traditional Media', 'TV, Radio, Print', 8.00, 75.00),
('Social Media', 'Facebook, Twitter, Instagram', 2.00, 45.00);

-- Insert some default segments for demonstration
INSERT INTO segments (name, description, type, size_estimate, engagement_rate, status, last_updated) VALUES
('High-Risk Population', 'Individuals with chronic conditions and elderly', 'demographic', 12847, 92.00, 'active', '2024-07-15'),
('Senior Citizens (65+)', 'Population aged 65 and above', 'demographic', 45231, 62.00, 'active', '2024-07-15'),
('Parents with Children', 'Families with children of any age', 'demographic', 28456, 45.00, 'active', '2024-07-15'),
('College Students', 'Students enrolled in higher education', 'demographic', 15782, 38.00, 'active', '2024-07-14'),
('Past Campaign Responders', 'Individuals who responded to previous campaigns', 'behavioral', 8452, 95.00, 'active', '2024-07-13'),
('Downtown Residents', 'Residents of urban core areas', 'geographic', 23456, 65.00, 'active', '2024-07-12'),
('Healthcare Workers', 'Medical professionals and healthcare staff', 'demographic', 5234, 88.00, 'draft', '2024-07-11');

-- Insert demographic criteria for the default segments
INSERT INTO demographic_criteria (segment_id, age_min, age_max, location, education_level, family_status) VALUES
(1, 65, 120, NULL, NULL, NULL),  -- High-Risk Population (age 65+)
(2, 65, 120, NULL, NULL, NULL),  -- Senior Citizens (age 65+)
(3, NULL, NULL, NULL, NULL, 'married'),  -- Parents with Children (family status married)
(4, 18, 25, NULL, 'college', NULL),  -- College Students (college age, education level)
(5, NULL, NULL, NULL, NULL, NULL),  -- Past Campaign Responders (no specific demographic)
(6, NULL, NULL, 'Downtown', NULL, NULL),  -- Downtown Residents (location specific)
(7, 25, 65, NULL, 'college', NULL);  -- Healthcare Workers (working age, education level)

-- Create indexes for better performance
CREATE INDEX idx_segments_type ON segments(type);
CREATE INDEX idx_segments_status ON segments(status);
CREATE INDEX idx_segments_created_at ON segments(created_at);
CREATE INDEX idx_segment_members_segment ON segment_members(segment_id);
CREATE INDEX idx_segment_analytics_date ON segment_analytics(measurement_date);