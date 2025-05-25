<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php', 'Unauthorized access', 'error');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php', 'Invalid request', 'error');
}

// Get form data
$fish_id = isset($_POST['fish_id']) ? (int)$_POST['fish_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

// Validate data
if (empty($fish_id) || !in_array($status, ['approved', 'rejected'])) {
    redirect('dashboard.php', 'Invalid data', 'error');
}

// Update fish status
$sql = "UPDATE fish SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'si', $status, $fish_id);

if (mysqli_stmt_execute($stmt)) {
    // Get the fish name for the message
    $name_sql = "SELECT name FROM fish WHERE id = ?";
    $name_stmt = mysqli_prepare($conn, $name_sql);
    mysqli_stmt_bind_param($name_stmt, 'i', $fish_id);
    mysqli_stmt_execute($name_stmt);
    $name_result = mysqli_stmt_get_result($name_stmt);
    $fish = mysqli_fetch_assoc($name_result);
    
    $message = "Fish '" . htmlspecialchars($fish['name']) . "' has been " . $status;
    redirect('dashboard.php', $message, 'success');
} else {
    redirect('dashboard.php', 'Error updating fish status: ' . mysqli_error($conn), 'error');
} 