<?php
require_once 'config.php';

// Auto-login as admin
$_SESSION['user_id'] = 1; // Admin user ID
$_SESSION['name'] = 'Admin User';
$_SESSION['email'] = 'admin@aquabase.com';
$_SESSION['is_admin'] = 1;

echo "Logged in as Admin User (ID: 1)";
echo "<br><a href='index.php'>Go to Homepage</a>";
echo "<br><a href='submit_fish.php'>Submit Fish</a>";
echo "<br><a href='admin/dashboard.php'>Admin Dashboard</a>";
?> 