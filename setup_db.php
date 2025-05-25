<?php
require_once 'config.php';

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