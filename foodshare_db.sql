-- tourism_database schema
-- Save as: tourism_db.sql

-- Safety/setup
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop and recreate database (optional)
DROP DATABASE IF EXISTS `tourism_db`;
CREATE DATABASE `tourism_database_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tourism_db`;

-- Users table
CREATE TABLE `users` (
    `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL,
    `country_name` VARCHAR(100) NULL,
    `contact_no` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tourism user_table
CREATE TABLE `country_name` (
    `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `country_name` VARCHAR(100) NOT NULL,
    `city_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `travelling_days` INT NOT NULL,
    `end_date` DATE,
    `category` VARCHAR(50),
    `image_url` VARCHAR(255),
    `status` ENUM('available', 'user', 'admin') DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `user_id` INT(6) UNSIGNED,
    CONSTRAINT `tu_tourism_user_key`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reservations table
CREATE TABLE `reservations` (
    `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(6) UNSIGNED NOT NULL,
    `hotel_id` INT(6) UNSIGNED NOT NULL,
    `reserved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('reserved',  'cancelled') DEFAULT 'reserved',
    CONSTRAINT `fk_reservations_food`
        FOREIGN KEY (`country_id`) REFERENCES (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reservations_receiver`
        FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Optional: seed data (uncomment and adjust as needed)
-- INSERT INTO `users` (`email`, `password`, `role`) VALUES
-- ('admin@example.com', '$2y$10$replace_with_password_hash', 'admin');