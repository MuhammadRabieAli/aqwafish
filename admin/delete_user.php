<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php', 'Unauthorized access', 'error');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php', 'Invalid request', 'error');
}

// Get user ID
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if (empty($user_id)) {
    redirect('users.php', 'Invalid user ID', 'error');
}

// Check if user is trying to delete an admin account
$check_admin_sql = "SELECT is_admin, name FROM users WHERE id = ?";
$check_admin_stmt = mysqli_prepare($conn, $check_admin_sql);
mysqli_stmt_bind_param($check_admin_stmt, 'i', $user_id);
mysqli_stmt_execute($check_admin_stmt);
$check_admin_result = mysqli_stmt_get_result($check_admin_stmt);

if (mysqli_num_rows($check_admin_result) == 0) {
    redirect('users.php', 'User not found', 'error');
}

$user = mysqli_fetch_assoc($check_admin_result);
if ($user['is_admin'] == 1) {
    redirect('users.php', 'Cannot delete admin accounts from this page', 'error');
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Option 1: Delete all user's fish submissions and related images
    // This will cascade delete images due to foreign key constraint
    $delete_fish_sql = "DELETE FROM fish WHERE submitted_by = ?";
    $delete_fish_stmt = mysqli_prepare($conn, $delete_fish_sql);
    mysqli_stmt_bind_param($delete_fish_stmt, 'i', $user_id);
    mysqli_stmt_execute($delete_fish_stmt);
    
    // Option 2 (alternative): Set the submitted_by field to NULL for existing fish
    // This keeps the fish entries but removes association with the deleted user
    // $update_fish_sql = "UPDATE fish SET submitted_by = NULL WHERE submitted_by = ?";
    // $update_fish_stmt = mysqli_prepare($conn, $update_fish_sql);
    // mysqli_stmt_bind_param($update_fish_stmt, 'i', $user_id);
    // mysqli_stmt_execute($update_fish_stmt);
    
    // Delete the user
    $delete_user_sql = "DELETE FROM users WHERE id = ?";
    $delete_user_stmt = mysqli_prepare($conn, $delete_user_sql);
    mysqli_stmt_bind_param($delete_user_stmt, 'i', $user_id);
    
    if (!mysqli_stmt_execute($delete_user_stmt)) {
        throw new Exception("Error deleting user: " . mysqli_error($conn));
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    redirect('users.php', "User '" . htmlspecialchars($user['name']) . "' has been deleted", 'success');
} catch (Exception $e) {
    // Rollback the transaction
    mysqli_rollback($conn);
    redirect('users.php', $e->getMessage(), 'error');
}