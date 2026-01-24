-- Database schema for Campaign Planning & Calendar Module
-- This SQL creates the necessary tables specifically for the campaign management module

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS campaign_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campaign_management;

-- Campaigns table - Main campaign data
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('safety', 'health', 'emergency', 'vaccination', 'awareness', 'education', 'enforcement') DEFAULT 'safety',
    status ENUM('draft', 'upcoming', 'active', 'completed', 'cancelled', 'on_hold') DEFAULT 'draft',
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
    CONSTRAINT chk_dates CHECK (end_date >= start_date),
    CONSTRAINT chk_budget CHECK (budget >= 0),
    CONSTRAINT chk_completion CHECK (completion_percentage >= 0 AND completion_percentage <= 100),
    CONSTRAINT chk_reach CHECK (target_reach >= 0 AND actual_reach >= 0)
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

-- Create indexes for better performance
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_campaigns_type ON campaigns(type);
CREATE INDEX idx_campaigns_dates ON campaigns(start_date, end_date);
CREATE INDEX idx_campaigns_created_by ON campaigns(created_by);
CREATE INDEX idx_campaigns_priority ON campaigns(priority);

CREATE INDEX idx_milestones_campaign ON campaign_milestones(campaign_id);
CREATE INDEX idx_milestones_status ON campaign_milestones(status);
CREATE INDEX idx_milestones_date ON campaign_milestones(target_date);

CREATE INDEX idx_resources_campaign ON campaign_resources(campaign_id);
CREATE INDEX idx_resources_type ON campaign_resources(resource_type);
CREATE INDEX idx_resources_status ON campaign_resources(status);

CREATE INDEX idx_team_campaign ON campaign_team_members(campaign_id);
CREATE INDEX idx_team_user ON campaign_team_members(user_id);
CREATE INDEX idx_team_role ON campaign_team_members(role);

CREATE INDEX idx_documents_campaign ON campaign_documents(campaign_id);
CREATE INDEX idx_documents_type ON campaign_documents(document_type);
CREATE INDEX idx_documents_status ON campaign_documents(status);

CREATE INDEX idx_activities_campaign ON campaign_activities(campaign_id);
CREATE INDEX idx_activities_type ON campaign_activities(activity_type);
CREATE INDEX idx_activities_date ON campaign_activities(activity_date);

-- Create views for common queries
CREATE VIEW campaign_summary AS
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

CREATE VIEW upcoming_milestones AS
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

CREATE PROCEDURE sp_create_campaign(
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

CREATE PROCEDURE sp_update_campaign_status(
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

CREATE PROCEDURE sp_get_campaign_statistics(IN p_user_id INT)
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

DELIMITER ;

-- Create triggers for automatic logging
DELIMITER //

CREATE TRIGGER tr_campaign_after_update
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

CREATE TRIGGER tr_campaign_after_delete
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

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON campaign_management.* TO 'campaign_user'@'localhost';
-- GRANT SELECT ON campaign_management.* TO 'campaign_viewer'@'localhost';
-- FLUSH PRIVILEGES;
