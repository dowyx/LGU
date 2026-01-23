-- Database schema for Event & Seminar Management module

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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- Insert default venues
INSERT IGNORE INTO venues (name, address, capacity, equipment_available, availability_status) VALUES
('City Community Center', 'Main Street, City Center', 200, 'Projector,WiFi,Parking,Sound System', 'available'),
('Public Safety HQ - Conference Room A', 'Safety Department Building', 50, 'AV System,Whiteboard,Projector', 'available'),
('Senior Community Center', 'Elderly Services Area', 80, 'Chairs,Tables,Accessible Facilities', 'available');

-- Insert default resources
INSERT IGNORE INTO event_resources (name, category, total_quantity, available_quantity, description) VALUES
('Projectors', 'AV Equipment', 15, 12, 'Standard presentation projectors'),
('Audio Systems', 'AV Equipment', 10, 8, 'Microphones and speakers'),
('Chairs', 'Furniture', 500, 450, 'Standard folding chairs'),
('First Aid Kits', 'Safety', 30, 25, 'Emergency medical supplies'),
('Tables', 'Furniture', 50, 45, 'Standard folding tables'),
('Laptops', 'Technology', 20, 18, 'For registration/check-in purposes');