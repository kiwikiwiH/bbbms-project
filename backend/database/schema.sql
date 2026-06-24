CREATE DATABASE IF NOT EXISTS bbbms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bbbms;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'donor', 'hospital', 'lab') NOT NULL DEFAULT 'donor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    phone VARCHAR(20),
    medical_notes TEXT,
    last_donation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blood_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id VARCHAR(64) NOT NULL UNIQUE,
    blood_group VARCHAR(5) NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM(
        'collected',
        'tested',
        'stored',
        'reserved',
        'issued',
        'expired'
    ) NOT NULL DEFAULT 'collected',
    facility_name VARCHAR(120) DEFAULT 'Main Blood Bank',
    qr_code_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_user_id INT NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    quantity_requested INT NOT NULL DEFAULT 1,
    urgency ENUM('normal', 'emergency') NOT NULL DEFAULT 'normal',
    status ENUM('pending', 'approved', 'rejected', 'fulfilled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS blockchain_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id VARCHAR(64),
    action VARCHAR(64) NOT NULL,
    transaction_hash VARCHAR(100) NOT NULL,
    block_number BIGINT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unit_id (unit_id),
    INDEX idx_tx_hash (transaction_hash)
);
