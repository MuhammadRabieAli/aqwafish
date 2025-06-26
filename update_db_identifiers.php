<?php
require_once 'config.php';

// For direct script execution, bypass the login check
$direct_execution = true;

if (!$direct_execution && (!isLoggedIn() || !isAdmin())) {
    echo "Unauthorized access. You must be logged in as admin.";
    exit;
}

// Add new columns to the fish table
$alter_sql = "ALTER TABLE fish 
              ADD COLUMN process_id VARCHAR(50) DEFAULT NULL,
              ADD COLUMN sample_id VARCHAR(50) DEFAULT NULL,
              ADD COLUMN museum_id VARCHAR(100) DEFAULT NULL,
              ADD COLUMN collection_code VARCHAR(100) DEFAULT NULL,
              ADD COLUMN field_id VARCHAR(50) DEFAULT NULL,
              ADD COLUMN deposited_in VARCHAR(255) DEFAULT NULL,
              ADD COLUMN specimen_linkout VARCHAR(255) DEFAULT NULL";

if (mysqli_query($conn, $alter_sql)) {
    echo "Fish table updated successfully with new identifier fields.<br>";
} else {
    echo "Error updating fish table: " . mysqli_error($conn) . "<br>";
}

// Create associated datasets table
$create_datasets_sql = "CREATE TABLE IF NOT EXISTS fish_datasets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fish_id INT(11) NOT NULL,
    dataset_code VARCHAR(50) NOT NULL,
    dataset_name VARCHAR(255) NOT NULL,
    dataset_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fish_id) REFERENCES fish(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_datasets_sql)) {
    echo "Fish datasets table created successfully.<br>";
} else {
    echo "Error creating fish datasets table: " . mysqli_error($conn) . "<br>";
}

echo "Database update completed.";
?> 