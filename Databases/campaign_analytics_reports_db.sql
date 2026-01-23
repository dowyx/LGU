-- Database schema for Campaign Analytics & Reports module

-- Campaign performance metrics table
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
    demographic_category VARCHAR(50) NOT NULL, -- age_group, gender, location, etc.
    demographic_value VARCHAR(100) NOT NULL, -- e.g., '25-44', 'male', 'Manila'
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
    channel_name VARCHAR(100) NOT NULL, -- email, social_media, radio, tv, etc.
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
    report_data JSON, -- Store the report data in JSON format
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500), -- Path to the exported report file
    status ENUM('draft', 'generated', 'shared') DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Campaign performance scores table
CREATE TABLE IF NOT EXISTS campaign_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    engagement_score INT DEFAULT 0, -- 0-100 scale
    roi_score INT DEFAULT 0, -- 0-100 scale
    satisfaction_score INT DEFAULT 0, -- 0-100 scale
    overall_score INT DEFAULT 0, -- 0-100 scale
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Predictive analytics forecasts table
CREATE TABLE IF NOT EXISTS predictive_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    forecast_type ENUM('reach', 'engagement', 'roi', 'conversion') DEFAULT 'reach',
    forecast_date DATE NOT NULL, -- Date for which the forecast is made
    predicted_value DECIMAL(15,2) DEFAULT 0.00,
    confidence_level DECIMAL(5,2) DEFAULT 0.00, -- Percentage confidence
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
);

-- Insert sample data for demographics
INSERT IGNORE INTO campaign_demographics (campaign_id, demographic_category, demographic_value, percentage, reach) VALUES
(1, 'age_group', '25-44', 45.00, 110250),
(1, 'age_group', '45-64', 32.00, 78400),
(1, 'age_group', '18-24', 18.00, 44100),
(1, 'age_group', '65+', 5.00, 12250);

-- Insert sample channel analytics data
INSERT IGNORE INTO channel_analytics (campaign_id, channel_name, impressions, clicks, conversions, cost, roi, engagement_rate) VALUES
(1, 'email', 50000, 2500, 125, 50000.00, 3.4, 5.00),
(1, 'social_media', 100000, 8000, 400, 100000.00, 4.2, 8.00),
(1, 'radio', 75000, 1500, 75, 75000.00, 2.8, 2.00),
(1, 'tv', 125000, 3750, 188, 125000.00, 3.1, 3.00);

-- Insert sample generated reports
INSERT IGNORE INTO generated_reports (report_name, report_type, report_period_start, report_period_end, generated_by, status) VALUES
('Q4 Performance Summary', 'performance', '2024-10-01', '2024-12-31', 1, 'generated'),
('Financial Analysis Report', 'financial', '2024-01-01', '2024-12-31', 1, 'generated'),
('Audience Insights Q1', 'audience', '2024-01-01', '2024-03-31', 1, 'generated');

-- Insert sample campaign scores
INSERT IGNORE INTO campaign_scores (campaign_id, engagement_score, roi_score, satisfaction_score, overall_score) VALUES
(1, 88, 95, 85, 92),
(2, 75, 80, 78, 78),
(3, 92, 98, 90, 94);