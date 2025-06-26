<?php
require_once 'config.php';

// Check if the users table exists
$tables_sql = "SHOW TABLES LIKE 'users'";
$tables_result = mysqli_query($conn, $tables_sql);
$users_table_exists = mysqli_num_rows($tables_result) > 0;

if (!$users_table_exists) {
    echo "Users table does not exist!";
    exit;
}

// Check users in the database
$users_sql = "SELECT id, name, email, is_admin FROM users";
$users_result = mysqli_query($conn, $users_sql);

echo "<h1>Users in Database</h1>";
echo "<p>Total users: " . mysqli_num_rows($users_result) . "</p>";

if (mysqli_num_rows($users_result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Is Admin</th></tr>";
    
    while ($user = mysqli_fetch_assoc($users_result)) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No users found in the database!</p>";
    echo "<p>Try running setup_db.php to create the default admin user.</p>";
}

// Check current session
echo "<h2>Current Session</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>Logged in as user ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Is admin: " . (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p>Not logged in</p>";
}
?> 