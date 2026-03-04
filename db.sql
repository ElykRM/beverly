CREATE DATABASE IF NOT EXISTS beverly;
USE beverly;

-- Households table (primary person / registrant)
CREATE TABLE households (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    home_status ENUM('Owner', 'Renter', 'Member') DEFAULT 'Owner',
    block VARCHAR(50),
    lot VARCHAR(50),
    street VARCHAR(150),
    subdivision VARCHAR(200) DEFAULT 'Beverly Homes Subd. Brgy Hugo Perez Trece Martires Cavite',
    birthday DATE,
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    contact_no VARCHAR(20),
    occupation VARCHAR(100),
    num_pets TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles (one household → many vehicles)
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    brand VARCHAR(100),
    type_model VARCHAR(100),
    color VARCHAR(50),
    plate_no VARCHAR(20),
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

-- Payments / Monthly Dues records
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    or_no VARCHAR(50),
    payment_period VARCHAR(50),      -- e.g. 'March 2026', 'Jan-Mar 2026'
    amount DECIMAL(10,2) NOT NULL,
    remarks TEXT,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

-- Household Members (additional members per household)
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

-- Speed up common searches
CREATE INDEX idx_name ON households(last_name, first_name);
CREATE INDEX idx_location ON households(block, lot);