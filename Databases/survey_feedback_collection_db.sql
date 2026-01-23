-- Database schema for Survey & Feedback Collection module

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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
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

-- Insert default distribution channels
INSERT IGNORE INTO distribution_channels (name, channel_type, response_rate, total_distributed, responses_received) VALUES
('Email', 'email', 45.00, 1245, 560),
('SMS', 'sms', 32.00, 892, 285),
('Web Portal', 'web', 68.00, 856, 582),
('QR Code', 'qr_code', 52.00, 465, 242);

-- Insert sample surveys
INSERT IGNORE INTO surveys (title, description, survey_type, status, start_date, end_date) VALUES
('Summer Safety Campaign Feedback', 'Measuring campaign effectiveness and public awareness', 'campaign', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('Community First Aid Workshop Evaluation', 'Post-event feedback for workshop improvement', 'event', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY)),
('Emergency Response Satisfaction', 'Service quality assessment', 'service', 'analysis', DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
('Public Safety Mobile App Feedback', 'App usability and feature requests', 'service', 'closed', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
('Community Safety Needs Assessment', 'Identifying priority safety concerns', 'general', 'draft', NULL, NULL);