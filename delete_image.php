<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to delete images', 'error');
}

// Get image ID and fish ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['fish_id']) || !is_numeric($_GET['fish_id'])) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', 'Invalid image ID or fish ID', 'error');
}

$image_id = (int)$_GET['id'];
$fish_id = (int)$_GET['fish_id'];
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Verify that the user has permission to delete this image
// Admin can delete any image, regular users can only delete their own pending submissions
$permission_sql = "SELECT f.id, f.submitted_by, f.status, i.image_path 
                  FROM fish_images i 
                  JOIN fish f ON i.fish_id = f.id 
                  WHERE i.id = ? AND i.fish_id = ?";
                  
$permission_stmt = mysqli_prepare($conn, $permission_sql);
mysqli_stmt_bind_param($permission_stmt, 'ii', $image_id, $fish_id);
mysqli_stmt_execute($permission_stmt);
$permission_result = mysqli_stmt_get_result($permission_stmt);

if (mysqli_num_rows($permission_result) == 0) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', 'Image not found', 'error');
}

$fish_data = mysqli_fetch_assoc($permission_result);

// Check if user has permission
if (!$is_admin && ($fish_data['submitted_by'] != $user_id || $fish_data['status'] != 'pending')) {
    redirect('user/dashboard.php', 'You do not have permission to delete this image', 'error');
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Get the image path before deletion
    $image_path = $fish_data['image_path'];
    
    // Delete the image from the database
    $delete_sql = "DELETE FROM fish_images WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, 'i', $image_id);
    
    if (!mysqli_stmt_execute($delete_stmt)) {
        throw new Exception("Error deleting image from database: " . mysqli_error($conn));
    }
    
    // Delete the image file from the server
    if (file_exists($image_path)) {
        if (!unlink($image_path)) {
            throw new Exception("Error deleting image file from server");
        }
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Redirect back to edit page
    redirect('edit_fish.php?id=' . $fish_id, 'Image deleted successfully', 'success');
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    redirect('edit_fish.php?id=' . $fish_id, $e->getMessage(), 'error');
}
?> 