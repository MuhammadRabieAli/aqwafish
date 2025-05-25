<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php', 'Unauthorized access', 'error');
}

// Get user ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('users.php', 'Invalid user ID', 'error');
}

$user_id = (int)$_GET['id'];

// Check if user is trying to edit an admin account
$check_admin_sql = "SELECT is_admin FROM users WHERE id = ?";
$check_admin_stmt = mysqli_prepare($conn, $check_admin_sql);
mysqli_stmt_bind_param($check_admin_stmt, 'i', $user_id);
mysqli_stmt_execute($check_admin_stmt);
$check_admin_result = mysqli_stmt_get_result($check_admin_stmt);

if (mysqli_num_rows($check_admin_result) == 0) {
    redirect('users.php', 'User not found', 'error');
}

$admin_check = mysqli_fetch_assoc($check_admin_result);
if ($admin_check['is_admin'] == 1) {
    redirect('users.php', 'Cannot edit admin accounts from this page', 'error');
}

$page = 'admin';
$page_title = 'Edit User';
$extra_css = ['../styles/admin.css', '../styles/dashboard.css'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Only filled if changing password
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email already exists (except for this user)
        $check_sql = "SELECT * FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'si', $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Email is already registered to another user";
        }
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
    } else {
        // Update user
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $user_id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            redirect('users.php', "User information updated successfully", 'success');
        } else {
            $error_message = "Error updating user: " . mysqli_error($conn);
        }
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    redirect('users.php', 'User not found', 'error');
}

$user = mysqli_fetch_assoc($result);

include '../includes/header.php';
?>

<!-- Admin Dashboard Header -->
<div class="dashboard-header admin-header">
    <div class="container">
        <h1>Edit User</h1>
        <p>Modify user information</p>
    </div>
</div>

<!-- Admin Navigation -->
<div class="admin-nav">
    <div class="container">
        <ul class="admin-menu">
            <li><a href="dashboard.php">Fish Submissions</a></li>
            <li><a href="users.php" class="active">User Management</a></li>
        </ul>
    </div>
</div>

<!-- Admin Content -->
<main class="dashboard-content">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="users.php">User Management</a>
            <span class="breadcrumb-separator">›</span>
            <span>Edit User</span>
        </div>
        
        <div class="form-container">
            <h2>Edit User: <?php echo htmlspecialchars($user['name']); ?></h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="edit_user.php?id=<?php echo $user_id; ?>">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input">
                    <div class="form-hint">Leave blank to keep current password</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Update User</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 