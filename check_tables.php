<?php
require_once 'config.php';

// Check fish table structure
$fish_check_sql = "SHOW COLUMNS FROM fish";
$fish_result = mysqli_query($conn, $fish_check_sql);

echo "<h2>Fish Table Structure:</h2>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($fish_result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if fish_datasets table exists
$tables_sql = "SHOW TABLES LIKE 'fish_datasets'";
$tables_result = mysqli_query($conn, $tables_sql);

echo "<h2>Fish Datasets Table:</h2>";
if (mysqli_num_rows($tables_result) > 0) {
    echo "<p>Table exists!</p>";
    
    // Check fish_datasets table structure
    $datasets_check_sql = "SHOW COLUMNS FROM fish_datasets";
    $datasets_result = mysqli_query($conn, $datasets_check_sql);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($datasets_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if any data exists
    $count_sql = "SELECT COUNT(*) as count FROM fish_datasets";
    $count_result = mysqli_query($conn, $count_sql);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    echo "<p>Records in fish_datasets table: $count</p>";
} else {
    echo "<p>Table does not exist! Creating it now...</p>";
    
    // Create datasets table
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
        echo "<p>Fish datasets table created successfully.</p>";
    } else {
        echo "<p>Error creating fish datasets table: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p>Database check completed.</p>";
?> 