-- Database for Content Repository Module
CREATE DATABASE IF NOT EXISTS content_repository_db;

USE content_repository_db;

-- Table for storing content items
CREATE TABLE content_items (
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
    download_count INT DEFAULT 0
);

-- Table for user accounts (if needed for content management)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator', 'content_manager', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for content categories
CREATE TABLE content_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default safety categories
INSERT INTO content_categories (name, description, icon_class) VALUES
('Emergency Response', 'Critical emergency procedures and response protocols', 'fa-exclamation-triangle'),
('Fire Safety', 'Fire prevention, detection, and response materials', 'fa-fire'),
('Public Health', 'Health awareness and disease prevention resources', 'fa-heartbeat'),
('Disaster Preparedness', 'Natural disaster preparedness and recovery guides', 'fa-cloud-showers-heavy'),
('Traffic Safety', 'Road safety awareness and accident prevention', 'fa-car-crash'),
('Cyber Security', 'Digital safety and cybersecurity awareness', 'fa-shield-alt');

-- Table for content tags
CREATE TABLE content_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to link content items with tags (many-to-many relationship)
CREATE TABLE content_item_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES content_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_content_tag (content_item_id, tag_id)
);

-- Table for content download logs
CREATE TABLE download_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT NOT NULL,
    user_id INT,
    download_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE
);

-- Table for content approval workflow
CREATE TABLE approval_workflow (
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

-- Indexes for better performance
CREATE INDEX idx_content_status ON content_items(status);
CREATE INDEX idx_content_category ON content_items(category);
CREATE INDEX idx_content_created ON content_items(created_at);
CREATE INDEX idx_content_expiry ON content_items(expiry_date);