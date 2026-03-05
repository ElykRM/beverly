-- =============================================================================
-- Full database schema for Beverly Homes HOA system
-- Updated with range payments and promo support
-- Run this in phpMyAdmin / MySQL Workbench after backing up your DB
-- =============================================================================

CREATE DATABASE IF NOT EXISTS beverly 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_general_ci;

USE beverly;

-- Households table
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
    num_pets TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_location (block, lot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Household members
CREATE TABLE IF NOT EXISTS household_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    relation VARCHAR(50) NOT NULL COMMENT 'e.g. Spouse, Child, Parent, Tenant, Sibling, Other',
    birthday DATE DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    contact_no VARCHAR(20) DEFAULT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    type_model VARCHAR(100) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    plate_no VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Payments (updated structure with range support + promo flag)
DROP TABLE IF EXISTS payments;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    or_no VARCHAR(50) NOT NULL,
    period_year INT NOT NULL,
    period_month TINYINT NOT NULL,          -- 1–12
    period_to_year INT DEFAULT NULL,
    period_to_month TINYINT DEFAULT NULL,   -- 1–12
    amount DECIMAL(10,2) NOT NULL,
    remarks TEXT DEFAULT NULL,
    is_promo TINYINT(1) DEFAULT 0 COMMENT '1 = yearly promo payment (e.g. 1000/year)',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    INDEX idx_period (period_year, period_month),
    INDEX idx_household_period (household_id, period_year, period_month),
    INDEX idx_promo (is_promo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional view: expands range payments into individual months for easier reporting
CREATE OR REPLACE VIEW payment_months AS
    SELECT 
        p.id AS payment_id,
        p.household_id,
        p.or_no,
        p.amount,
        p.is_promo,
        p.paid_at,
        p.remarks,
        y.year_num AS period_year,
        m.month_num AS period_month
    FROM payments p
    CROSS JOIN (
        SELECT 1 AS month_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 
        UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
    ) m
    CROSS JOIN (
        SELECT period_year AS year_num FROM payments WHERE period_to_year IS NULL
        UNION
        SELECT y FROM (
            SELECT period_year AS y FROM payments WHERE period_to_year IS NOT NULL
            UNION SELECT period_to_year FROM payments WHERE period_to_year IS NOT NULL
        ) years
    ) y
    WHERE 
        (p.period_to_year IS NULL AND m.month_num = p.period_month)
        OR
        (p.period_to_year IS NOT NULL 
         AND y.year_num BETWEEN p.period_year AND p.period_to_year
         AND (y.year_num > p.period_year OR m.month_num >= p.period_month)
         AND (y.year_num < p.period_to_year OR m.month_num <= p.period_to_month));