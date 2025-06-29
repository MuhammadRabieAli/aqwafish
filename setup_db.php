<?php
require_once 'config.php';

// Get database name from config
$db_name = DB_NAME;

// Drop the entire database if it exists
$drop_database = "DROP DATABASE IF EXISTS `$db_name`";
if (!mysqli_query($conn, $drop_database)) {
    die("Error dropping database: " . mysqli_error($conn));
}

// Create the database again
$create_database = "CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($conn, $create_database)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
if (!mysqli_select_db($conn, $db_name)) {
    die("Error selecting database: " . mysqli_error($conn));
}

echo "✓ Database '$db_name' recreated successfully\n";

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $users_table)) {
    die("Error creating users table: " . mysqli_error($conn));
}

echo "✓ Created users table\n";

// Create fish table
$fish_table = "CREATE TABLE IF NOT EXISTS fish (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    scientific_name VARCHAR(100),
    family VARCHAR(100),
    environment ENUM('freshwater', 'saltwater', 'brackish'),
    size_category ENUM('small', 'medium', 'large'),
    description TEXT,
    submitted_by INT(11),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    process_id VARCHAR(50) DEFAULT NULL,
    sample_id VARCHAR(50) DEFAULT NULL,
    museum_id VARCHAR(100) DEFAULT NULL,
    collection_code VARCHAR(100) DEFAULT NULL,
    field_id VARCHAR(50) DEFAULT NULL,
    deposited_in VARCHAR(255) DEFAULT NULL,
    specimen_linkout VARCHAR(255) DEFAULT NULL,
    sequence_type VARCHAR(50) DEFAULT NULL,
    sequence_id VARCHAR(100) DEFAULT NULL,
    genbank_accession VARCHAR(50) DEFAULT NULL,
    sequence_updated_at DATE DEFAULT NULL,
    genome_type VARCHAR(100) DEFAULT NULL,
    locus VARCHAR(255) DEFAULT NULL,
    nucleotides_count INT DEFAULT NULL,
    dna_sequence TEXT DEFAULT NULL,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
)";

if (!mysqli_query($conn, $fish_table)) {
    die("Error creating fish table: " . mysqli_error($conn));
}

echo "✓ Created fish table with scientific identifiers and sequence data\n";

// Create fish_images table with categories
$fish_images_table = "CREATE TABLE IF NOT EXISTS fish_images (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fish_id INT(11) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    category ENUM('main', 'fish', 'skeleton', 'disease', 'map') DEFAULT 'fish',
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fish_id) REFERENCES fish(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $fish_images_table)) {
    die("Error creating fish_images table: " . mysqli_error($conn));
}

echo "✓ Created fish_images table with category support\n";

// Create fish_datasets table
$fish_datasets_table = "CREATE TABLE IF NOT EXISTS fish_datasets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fish_id INT(11) NOT NULL,
    dataset_code VARCHAR(50) NOT NULL,
    dataset_name VARCHAR(255) NOT NULL,
    dataset_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fish_id) REFERENCES fish(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $fish_datasets_table)) {
    die("Error creating fish_datasets table: " . mysqli_error($conn));
}

echo "✓ Created fish_datasets table\n";

// Create a default admin user (password: admin123)
echo "Creating default admin user...\n";
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$create_admin = "INSERT INTO users (name, email, password, is_admin) VALUES ('Admin User', 'admin@aquabase.com', '$admin_password', 1)";

if (!mysqli_query($conn, $create_admin)) {
    die("Error creating admin user: " . mysqli_error($conn));
}

echo "✓ Created admin user (email: admin@aquabase.com, password: admin123)\n";
echo "\n🎉 Database setup completed successfully!\n";
echo "\n📋 Summary:\n";
echo "   - Database: $db_name (recreated)\n";
echo "   - Tables: users, fish, fish_images (with categories), fish_datasets\n";
echo "   - Admin account: admin@aquabase.com / admin123\n";
echo "   - Image categories: main, fish, skeleton, disease, map\n";
?> 