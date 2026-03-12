-- Database Schema for Calldesk CRM

CREATE DATABASE IF NOT EXISTS calldesk;
USE calldesk;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'executive') NOT NULL DEFAULT 'executive',
    status TINYINT(1) DEFAULT 1,
    api_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lead Sources Table
CREATE TABLE IF NOT EXISTS lead_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leads Table
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    source_id INT NULL,
    status ENUM('New', 'Follow-up', 'Interested', 'Converted', 'Lost') DEFAULT 'New',
    assigned_to INT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (source_id) REFERENCES lead_sources(id) ON DELETE SET NULL
);

-- Call Logs Table (Sync ready)
CREATE TABLE IF NOT EXISTS call_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    type ENUM('Incoming', 'Outgoing', 'Missed') NOT NULL,
    duration INT DEFAULT 0, -- in seconds
    call_time DATETIME NOT NULL,
    lead_id INT NULL,
    executive_id INT NULL,
    recording_path VARCHAR(255) NULL,
    is_converted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (executive_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Follow-up History Table
CREATE TABLE IF NOT EXISTS follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    executive_id INT NOT NULL,
    remark TEXT,
    next_follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (executive_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin (Password: admin123)
-- Hash generated for 'admin123'
REPLACE INTO users (id, name, mobile, password, role, status) VALUES 
(1, 'System Admin', '9999999999', '$2y$10$mC7GjtL7E6S2S.mYn8m6u.VvGqj7R1.e5G5G5G5G5G5G5G5G5G5G', 'admin', 1);
-- Insert Default Lead Sources
INSERT IGNORE INTO lead_sources (source_name) VALUES ('Facebook'), ('Google'), ('Website'), ('WhatsApp'), ('Referral');
