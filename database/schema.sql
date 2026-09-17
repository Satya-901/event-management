-- ==========================================================
-- Utsavam - Multi-Tenant Event Booking & QR Verification
-- Database Schema for MySQL 8.0+
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `utsavam` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `utsavam`;

-- 1. Clients Table (Tenants)
CREATE TABLE IF NOT EXISTS `clients` (
    `id` VARCHAR(64) NOT NULL,
    `code` VARCHAR(32) NOT NULL UNIQUE,
    `slug` VARCHAR(128) NOT NULL UNIQUE,
    `name` VARCHAR(191) NOT NULL,
    `company_name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `mobile` VARCHAR(32) NULL,
    `address` TEXT NULL,
    `logo` VARCHAR(500) NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_client_status` (`status`),
    INDEX `idx_client_code` (`code`),
    INDEX `idx_client_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table (Super Admins & Client Staff)
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NULL,
    `name` VARCHAR(191) NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'client', 'staff') NOT NULL DEFAULT 'client',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_client` (`client_id`),
    INDEX `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Festival & Cultural',
    `short_description` TEXT NULL,
    `full_description` LONGTEXT NULL,
    `banner` VARCHAR(500) NULL,
    `logo` VARCHAR(500) NULL,
    `gallery` JSON NULL,
    `start_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_date` DATE NOT NULL,
    `end_time` TIME NOT NULL,
    `venue_name` VARCHAR(255) NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NULL,
    `pincode` VARCHAR(20) NULL,
    `google_maps_url` VARCHAR(500) NULL,
    `booking_open` TINYINT(1) NOT NULL DEFAULT 1,
    `max_capacity` INT UNSIGNED NOT NULL DEFAULT 500,
    `available_seats` INT UNSIGNED NOT NULL DEFAULT 500,
    `confirmation_mode` ENUM('instant', 'manual') NOT NULL DEFAULT 'instant',
    `price_label` VARCHAR(100) NULL DEFAULT 'Free Entry',
    `price_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `contact_email` VARCHAR(191) NULL,
    `contact_phone` VARCHAR(32) NULL,
    `contact_whatsapp` VARCHAR(32) NULL,
    `social_links` JSON NULL,
    `highlights` JSON NULL,
    `faqs` JSON NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `og_image` VARCHAR(500) NULL,
    `keywords` VARCHAR(255) NULL,
    `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'published',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_client_event_slug` (`client_id`, `slug`),
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_event_dates` (`start_date`, `end_date`),
    INDEX `idx_event_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Event Form Fields (Dynamic Booking Form Builder)
CREATE TABLE IF NOT EXISTS `event_form_fields` (
    `id` VARCHAR(64) NOT NULL,
    `event_id` VARCHAR(64) NOT NULL,
    `field_key` VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(191) NOT NULL,
    `field_type` ENUM('text', 'email', 'phone', 'number', 'date', 'select', 'radio', 'checkbox', 'textarea') NOT NULL DEFAULT 'text',
    `placeholder` VARCHAR(191) NULL,
    `required` TINYINT(1) NOT NULL DEFAULT 0,
    `options` JSON NULL,
    `order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    INDEX `idx_form_event_order` (`event_id`, `order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `event_id` VARCHAR(64) NOT NULL,
    `booking_number` VARCHAR(64) NOT NULL UNIQUE,
    `customer_name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `pass_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `booking_date` DATE NOT NULL,
    `status` ENUM('pending', 'confirmed', 'cancelled', 'rejected', 'checked_in', 'expired') NOT NULL DEFAULT 'confirmed',
    `qr_token` VARCHAR(128) NOT NULL UNIQUE,
    `checked_in_at` DATETIME NULL,
    `checked_in_by` VARCHAR(191) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    INDEX `idx_booking_tenant` (`client_id`, `event_id`),
    INDEX `idx_booking_status` (`status`),
    INDEX `idx_booking_number` (`booking_number`),
    INDEX `idx_booking_qr` (`qr_token`),
    INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Booking Answers (Dynamic Custom Form Answers)
CREATE TABLE IF NOT EXISTS `booking_answers` (
    `id` VARCHAR(64) NOT NULL,
    `booking_id` VARCHAR(64) NOT NULL,
    `field_key` VARCHAR(100) NOT NULL,
    `field_value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    INDEX `idx_answer_booking` (`booking_id`, `field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. QR Verification Scans
CREATE TABLE IF NOT EXISTS `qr_scans` (
    `id` VARCHAR(64) NOT NULL,
    `booking_id` VARCHAR(64) NULL,
    `client_id` VARCHAR(64) NOT NULL,
    `event_id` VARCHAR(64) NOT NULL,
    `qr_token` VARCHAR(128) NOT NULL,
    `status` ENUM('valid', 'already_checked_in', 'wrong_event', 'invalid', 'unauthorized_tenant') NOT NULL,
    `scanned_by` VARCHAR(191) NOT NULL,
    `device_info` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_scan_client_event` (`client_id`, `event_id`),
    INDEX `idx_scan_time` (`scanned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Activity Logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` VARCHAR(64) NOT NULL,
    `actor_id` VARCHAR(64) NOT NULL,
    `actor_role` VARCHAR(64) NOT NULL,
    `client_id` VARCHAR(64) NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(64) NOT NULL,
    `entity_id` VARCHAR(64) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_log_client` (`client_id`),
    INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Notifications (Email Logs)
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` VARCHAR(64) NOT NULL,
    `to_email` VARCHAR(191) NOT NULL,
    `customer_name` VARCHAR(191) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `booking_id` VARCHAR(64) NULL,
    `status` ENUM('queued', 'sent', 'failed', 'saved_locally') NOT NULL DEFAULT 'queued',
    `html_body` LONGTEXT NULL,
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Platform Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` LONGTEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
