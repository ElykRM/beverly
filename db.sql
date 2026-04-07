-- =============================================================================
-- Full Beverly Homes HOA Database Schema
-- Last updated: April 6, 2026
-- Includes: households (with move-in/move-out tracking), members, vehicles, 
--           payments (with range + promo support + duplicate prevention)
-- Features: Move-in/move-out dates, payment history, duplicate payment validation
-- Run this in phpMyAdmin / MySQL Workbench after backing up existing data
-- =============================================================================
-- NOTE: For Infinity Free, just paste this entire SQL into phpMyAdmin's SQL tab and run it.
-- =============================================================================

USE if0_41510481_beverly;

-- 1. Households (main table)
CREATE TABLE IF NOT EXISTS households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    home_status ENUM('Owner', 'Renter', 'Member') NOT NULL DEFAULT 'Owner',
    block VARCHAR(50) DEFAULT NULL,
    lot VARCHAR(50) DEFAULT NULL,
    street VARCHAR(150) DEFAULT NULL,
    subdivision VARCHAR(200) DEFAULT 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite',
    birthday DATE DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    contact_no VARCHAR(20) DEFAULT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    num_pets TINYINT UNSIGNED DEFAULT 0,
    move_in_date DATE DEFAULT NULL COMMENT 'Date household member moved in (e.g., April 6, 2026)',
    move_out_date DATE DEFAULT NULL COMMENT 'Date moved out; NULL = "Currently living here" / "Up to present"',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_full_name (last_name, first_name),
    INDEX idx_location (block, lot),
    INDEX idx_status (home_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Household Members (additional people in household)
CREATE TABLE IF NOT EXISTS household_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    relation VARCHAR(50) NOT NULL COMMENT 'Spouse, Child, Parent, Sibling, Tenant, Other',
    birthday DATE DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    contact_no VARCHAR(20) DEFAULT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    INDEX idx_household (household_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    type_model VARCHAR(100) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    plate_no VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    INDEX idx_household (household_id),
    INDEX idx_plate (plate_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Payments (with range support + promo flag)
DROP TABLE IF EXISTS payments;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    or_no VARCHAR(50) NOT NULL,
    period_year INT NOT NULL,
    period_month TINYINT UNSIGNED NOT NULL COMMENT '1-12',
    period_to_year INT DEFAULT NULL,
    period_to_month TINYINT UNSIGNED DEFAULT NULL COMMENT '1-12',
    amount DECIMAL(10,2) NOT NULL,
    remarks TEXT DEFAULT NULL,
    is_promo TINYINT(1) DEFAULT 0 COMMENT '1 = yearly promo (e.g. 1000/year)',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'For soft delete (optional)',
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    INDEX idx_household (household_id),
    INDEX idx_period (period_year, period_month),
    INDEX idx_range (period_year, period_to_year),
    INDEX idx_promo (is_promo),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: VIEW REMOVED - Infinity Free doesn't allow CREATE VIEW permissions
-- If needed, you can replicate this query logic in your PHP code instead
-- Original view would expand range payments into individual months for reporting

-- Optional: Add some test data (uncomment if needed for development)
-- INSERT INTO households (last_name, first_name, home_status, block, lot, street) 
-- VALUES ('Dela Cruz', 'Juan', 'Owner', '5', '12', 'Main St');

-- INSERT INTO payments (household_id, or_no, period_year, period_month, amount, is_promo) 
-- VALUES (1, 'OR-001', 2025, 1, 1000.00, 1);

-- 5. Users (login accounts)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    is_setup_complete TINYINT(1) DEFAULT 0 COMMENT '1 = first admin created, setup disabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;