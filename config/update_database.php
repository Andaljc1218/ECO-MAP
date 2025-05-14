<?php
require_once "database.php";

// Update users table
$sql = "ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS nickname VARCHAR(50) AFTER username,
    ADD COLUMN IF NOT EXISTS remember_token VARCHAR(64) UNIQUE,
    ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    MODIFY COLUMN role ENUM('admin', 'driver', 'user') NOT NULL DEFAULT 'user',
    MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD INDEX idx_username (username),
    ADD INDEX idx_email (email),
    ADD INDEX idx_role (role),
    ADD INDEX idx_status (status)";

if (!mysqli_query($conn, $sql)) {
    die("Error updating users table: " . mysqli_error($conn));
}

// Update pickup_points table
$sql = "ALTER TABLE pickup_points 
    ADD COLUMN IF NOT EXISTS address TEXT,
    ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20),
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX idx_status (status),
    ADD INDEX idx_location (latitude, longitude)";

if (!mysqli_query($conn, $sql)) {
    die("Error updating pickup_points table: " . mysqli_error($conn));
}

// Update schedules table
$sql = "ALTER TABLE schedules 
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS notes TEXT,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD INDEX idx_status (status),
    ADD INDEX idx_driver (driver_id),
    ADD INDEX idx_area (area)";

if (!mysqli_query($conn, $sql)) {
    die("Error updating schedules table: " . mysqli_error($conn));
}

echo "Database structure updated successfully!";
?> 