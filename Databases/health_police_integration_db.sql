-- Database schema for Health & Police Integration module

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

-- Insert sample integration systems
INSERT IGNORE INTO integration_systems (name, system_type, connected_system, description, status, api_endpoint, api_version, rate_limit) VALUES
('Public Health Database', 'health', 'State Health Department', 'Immunization Registry & Disease Surveillance', 'active', 'https://health-api.gov.ph/v2', '2.1', 100),
('Police CAD System', 'police', 'City Police Department', 'Computer-Aided Dispatch & Incident Reports', 'active', 'https://police-api.gov.ph/v1', '1.4', 50),
('Hospital EHR System', 'health', 'Regional Hospital Network', 'Emergency Department & Bed Availability', 'maintenance', 'https://hospital-api.health.gov', '2.0', 75),
('Emergency Services', 'emergency', 'County Fire Department', 'Fire & Rescue Dispatch Systems', 'active', 'https://emergency-api.gov.ph/v3', '3.0', 100),
('Traffic Management System', 'data', 'Transportation Department', 'Real-time traffic and road condition data', 'active', 'https://traffic-api.gov.ph/v1', '1.0', 200);

-- Insert sample data flows
INSERT IGNORE INTO data_flows (integration_id, flow_direction, data_points, daily_count, success_rate) VALUES
(1, 'bidirectional', '["immunizations", "disease_reports", "lab_results"]', 1245, 99.2),
(2, 'bidirectional', '["incidents", "dispatch", "resources"]', 892, 98.7),
(3, 'bidirectional', '["bed_status", "ed_capacity", "specialists"]', 567, 99.5),
(4, 'bidirectional', '["alerts", "deployments", "resources"]', 342, 99.8),
(5, 'bidirectional', '["traffic_flow", "accidents", "road_closures"]', 289, 99.1);

-- Insert sample security compliance records
INSERT IGNORE INTO security_compliance (integration_id, compliance_standard, status, encryption_enabled, encryption_type) VALUES
(1, 'HIPAA', 'compliant', TRUE, 'AES-256'),
(1, 'GDPR', 'compliant', TRUE, 'AES-256'),
(2, 'CJIS', 'compliant', TRUE, 'AES-256'),
(3, 'HIPAA', 'compliant', TRUE, 'AES-256'),
(4, 'CJIS', 'compliant', TRUE, 'AES-256');

-- Insert sample alert triggers
INSERT IGNORE INTO alert_triggers (trigger_name, trigger_condition, trigger_action) VALUES
('Disease Outbreak Detected', 'More than 10 disease reports in 24 hours', 'Automatically notify Public Health & Emergency Services'),
('Major Incident Reported', 'Incident severity is critical', 'Alert Police, Fire, and Medical Teams'),
('Hospital Capacity Critical', 'Emergency department capacity exceeds 90%', 'Redirect emergencies to alternative facilities'),
('Weather Emergency Declared', 'Weather alert level is severe', 'Activate emergency response protocols');

-- Insert sample integration logs
INSERT IGNORE INTO integration_logs (integration_id, log_level, message) VALUES
(2, 'SUCCESS', 'Police Incident API sync completed successfully'),
(1, 'INFO', 'Health data flow increased by 15% (threshold alert)'),
(3, 'WARNING', 'Hospital EHR system entered maintenance mode'),
(4, 'SUCCESS', 'Emergency alert test notification sent successfully'),
(1, 'SUCCESS', 'Data encryption key rotation completed'),
(1, 'ERROR', 'Temporary connection loss with Public Health Database (restored)'),
(1, 'SUCCESS', 'API rate limits adjusted based on usage patterns');