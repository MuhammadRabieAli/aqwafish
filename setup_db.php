<?php
require_once 'config.php';

// Drop tables if they exist to avoid tablespace conflicts
$drop_fish_datasets = "DROP TABLE IF EXISTS fish_datasets";
if (!mysqli_query($conn, $drop_fish_datasets)) {
    die("Error dropping fish_datasets table: " . mysqli_error($conn));
}

$drop_fish_images = "DROP TABLE IF EXISTS fish_images";
if (!mysqli_query($conn, $drop_fish_images)) {
    die("Error dropping fish_images table: " . mysqli_error($conn));
}

$drop_fish = "DROP TABLE IF EXISTS fish";
if (!mysqli_query($conn, $drop_fish)) {
    die("Error dropping fish table: " . mysqli_error($conn));
}

$drop_users = "DROP TABLE IF EXISTS users";
if (!mysqli_query($conn, $drop_users)) {
    die("Error dropping users table: " . mysqli_error($conn));
}

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

// Create fish_images table
$fish_images_table = "CREATE TABLE IF NOT EXISTS fish_images (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fish_id INT(11) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fish_id) REFERENCES fish(id) ON DELETE CASCADE
)";

if (!mysqli_query($conn, $fish_images_table)) {
    die("Error creating fish_images table: " . mysqli_error($conn));
}

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

// Create a default admin user (password: admin123)
$check_admin = "SELECT * FROM users WHERE email = 'admin@aquabase.com'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $create_admin = "INSERT INTO users (name, email, password, is_admin) VALUES ('Admin User', 'admin@aquabase.com', '$admin_password', 1)";
    
    if (!mysqli_query($conn, $create_admin)) {
        die("Error creating admin user: " . mysqli_error($conn));
    }
}

echo "Database setup completed successfully.";
?> 