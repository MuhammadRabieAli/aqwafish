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

// Get fish ID
$fish_id = isset($_POST['fish_id']) ? (int)$_POST['fish_id'] : 0;

if (empty($fish_id)) {
    redirect('dashboard.php', 'Invalid fish ID', 'error');
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get fish name and image paths for feedback and file deletion
    $fish_sql = "SELECT name FROM fish WHERE id = ?";
    $fish_stmt = mysqli_prepare($conn, $fish_sql);
    mysqli_stmt_bind_param($fish_stmt, 'i', $fish_id);
    mysqli_stmt_execute($fish_stmt);
    $fish_result = mysqli_stmt_get_result($fish_stmt);
    
    if (mysqli_num_rows($fish_result) == 0) {
        throw new Exception("Fish not found");
    }
    
    $fish = mysqli_fetch_assoc($fish_result);
    $fish_name = $fish['name'];
    
    // Get image paths
    $images_sql = "SELECT image_path FROM fish_images WHERE fish_id = ?";
    $images_stmt = mysqli_prepare($conn, $images_sql);
    mysqli_stmt_bind_param($images_stmt, 'i', $fish_id);
    mysqli_stmt_execute($images_stmt);
    $images_result = mysqli_stmt_get_result($images_stmt);
    
    $image_paths = [];
    while ($image = mysqli_fetch_assoc($images_result)) {
        $image_paths[] = $image['image_path'];
    }
    
    // Delete images first (foreign key constraint will prevent fish deletion if images exist)
    $delete_images_sql = "DELETE FROM fish_images WHERE fish_id = ?";
    $delete_images_stmt = mysqli_prepare($conn, $delete_images_sql);
    mysqli_stmt_bind_param($delete_images_stmt, 'i', $fish_id);
    
    if (!mysqli_stmt_execute($delete_images_stmt)) {
        throw new Exception("Error deleting fish images: " . mysqli_error($conn));
    }
    
    // Delete fish entry
    $delete_fish_sql = "DELETE FROM fish WHERE id = ?";
    $delete_fish_stmt = mysqli_prepare($conn, $delete_fish_sql);
    mysqli_stmt_bind_param($delete_fish_stmt, 'i', $fish_id);
    
    if (!mysqli_stmt_execute($delete_fish_stmt)) {
        throw new Exception("Error deleting fish entry: " . mysqli_error($conn));
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Delete image files from the server
    foreach ($image_paths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    redirect('dashboard.php', "Fish '" . htmlspecialchars($fish_name) . "' has been deleted", 'success');
} catch (Exception $e) {
    // Rollback the transaction
    mysqli_rollback($conn);
    redirect('dashboard.php', $e->getMessage(), 'error');
} 