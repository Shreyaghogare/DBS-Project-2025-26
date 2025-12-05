-- =====================================================
-- Delivery Applications Database Setup
-- =====================================================
-- This SQL file creates a separate database for 
-- managing delivery boy job applications
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS foodshare_delivery_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Use the database
USE foodshare_delivery_db;

-- =====================================================
-- Create delivery_applications table
-- =====================================================
CREATE TABLE IF NOT EXISTS delivery_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    city VARCHAR(120) NOT NULL,
    experience ENUM('None', '0-1 years', '1-3 years', '3+ years') DEFAULT 'None',
    availability ENUM('Full-time', 'Part-time', 'Weekends', 'Flexible') DEFAULT 'Flexible',
    transport VARCHAR(120) NOT NULL,
    message TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Optional: Insert sample data (uncomment if needed)
-- =====================================================
/*
INSERT INTO delivery_applications 
    (full_name, email, phone, city, experience, availability, transport, message, status) 
VALUES
    ('John Doe', 'john.doe@example.com', '1234567890', 'New York', '1-3 years', 'Full-time', 'Bike', 'Experienced delivery driver', 'pending'),
    ('Jane Smith', 'jane.smith@example.com', '0987654321', 'Los Angeles', '0-1 years', 'Part-time', 'Scooter', 'Looking for flexible hours', 'pending'),
    ('Mike Johnson', 'mike.j@example.com', '1122334455', 'Chicago', '3+ years', 'Full-time', 'Van', 'Professional delivery driver with own vehicle', 'approved');
*/

-- =====================================================
-- View: Applications Summary
-- =====================================================
CREATE OR REPLACE VIEW applications_summary AS
SELECT 
    status,
    COUNT(*) as total_count,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count
FROM delivery_applications
GROUP BY status;

-- =====================================================
-- End of SQL file
-- =====================================================

