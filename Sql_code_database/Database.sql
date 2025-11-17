-- Create database
CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    place VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Voters table
CREATE TABLE IF NOT EXISTS voters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fingerprint_id INT UNIQUE NOT NULL,
    name VARCHAR(255),
    dob DATE,
    aadhaar VARCHAR(12) UNIQUE,
    voter_id VARCHAR(255) UNIQUE,
    place VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Votes table
CREATE TABLE IF NOT EXISTS votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fingerprint_id INT NOT NULL,
    candidate_id INT NOT NULL,
    place VARCHAR(255) NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fingerprint_id) REFERENCES voters(fingerprint_id) ON DELETE CASCADE
);

-- Device heartbeats table
CREATE TABLE IF NOT EXISTS device_heartbeats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    place VARCHAR(255) NOT NULL,
    wifi_status VARCHAR(255),
    sensor_status VARCHAR(255),
    template_count INT,
    ip_address VARCHAR(255),
    last_enroll_id INT,
    votes_total INT,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    symbol VARCHAR(255)
);

-- Insert default admin accounts
INSERT IGNORE INTO admin (username, password, place) VALUES 
('23ECR117', 'Kumar@010506', 'Erode'),
('admin_coimbatore', 'admin123', 'Coimbatore'),
('admin_tiruppur', 'admin123', 'Tiruppur');

-- Insert default candidates
INSERT IGNORE INTO candidates (id, name, symbol) VALUES
(1, 'Candidate 1', 'Symbol1'),
(2, 'Candidate 2', 'Symbol2'),
(3, 'Candidate 3', 'Symbol3'),
(4, 'Candidate 4', 'Symbol4');

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_voters_fingerprint ON voters(fingerprint_id);
CREATE INDEX IF NOT EXISTS idx_voters_place ON voters(place);
CREATE INDEX IF NOT EXISTS idx_voters_status ON voters(status);
CREATE INDEX IF NOT EXISTS idx_votes_fingerprint ON votes(fingerprint_id);
CREATE INDEX IF NOT EXISTS idx_votes_place ON votes(place);
CREATE INDEX IF NOT EXISTS idx_votes_candidate ON votes(candidate_id);
CREATE INDEX IF NOT EXISTS idx_heartbeats_place ON device_heartbeats(place);
CREATE INDEX IF NOT EXISTS idx_heartbeats_time ON device_heartbeats(last_heartbeat);
CREATE INDEX IF NOT EXISTS idx_admin_username ON admin(username);
CREATE INDEX IF NOT EXISTS idx_admin_place ON admin(place);

SELECT 'Voting System Database Created Successfully!' as message;