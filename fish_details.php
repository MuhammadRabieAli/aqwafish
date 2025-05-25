<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view fish details', 'error');
}

// Get fish ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', 'Invalid fish ID', 'error');
}

$fish_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get fish details
$sql = "SELECT f.*, u.name as submitter_name FROM fish f 
        LEFT JOIN users u ON f.submitted_by = u.id 
        WHERE f.id = ?";
        
if (!$is_admin) {
    // Regular users can only view their own submissions
    $sql .= " AND f.submitted_by = ?";
}

$stmt = mysqli_prepare($conn, $sql);

if ($is_admin) {
    mysqli_stmt_bind_param($stmt, 'i', $fish_id);
} else {
    mysqli_stmt_bind_param($stmt, 'ii', $fish_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', 'Fish not found or you do not have permission to view it', 'error');
}

$fish = mysqli_fetch_assoc($result);

// Get fish images
$images_sql = "SELECT * FROM fish_images WHERE fish_id = ? ORDER BY is_primary DESC";
$images_stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($images_stmt, 'i', $fish_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

$page = 'dashboard';
$page_title = $fish['name'] . ' Details';
$extra_css = ['/styles/fish-details.css', '/styles/dashboard.css'];

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <?php if ($is_admin): ?>
            <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
        <?php else: ?>
            <a href="user/dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <?php endif; ?>
        <span class="breadcrumb-separator">›</span>
        <span>Fish Details</span>
    </div>
</div>

<!-- Main Content -->
<main class="fish-details">
    <div class="container">
        <div class="fish-content loaded">
            <div class="fish-header">
                <div class="fish-gallery">
                    <?php if (mysqli_num_rows($images_result) > 0): ?>
                        <div class="gallery-main">
                            <?php 
                            mysqli_data_seek($images_result, 0);
                            $main_image = mysqli_fetch_assoc($images_result);
                            ?>
                            <img src="<?php echo $main_image['image_path']; ?>" alt="<?php echo $fish['name']; ?>" id="mainImage" class="main-image">
                        </div>
                        
                        <?php if (mysqli_num_rows($images_result) > 1): ?>
                            <div class="thumbnail-strip">
                                <?php mysqli_data_seek($images_result, 0); ?>
                                <?php while ($image = mysqli_fetch_assoc($images_result)): ?>
                                    <img 
                                        src="<?php echo $image['image_path']; ?>" 
                                        alt="<?php echo $fish['name']; ?>" 
                                        class="thumbnail <?php echo ($image === $main_image) ? 'active' : ''; ?>" 
                                        onclick="changeImage('<?php echo $image['image_path']; ?>', this)">
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="gallery-main no-image">
                            <div class="placeholder-image"><i class="fas fa-fish fa-3x"></i></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="fish-info">
                    <h1 class="fish-title"><?php echo htmlspecialchars($fish['name']); ?></h1>
                    <?php if (!empty($fish['scientific_name'])): ?>
                        <p class="fish-scientific"><em><?php echo htmlspecialchars($fish['scientific_name']); ?></em></p>
                    <?php endif; ?>
                    
                    <div class="fish-badges">
                        <span class="badge badge-info"><?php echo htmlspecialchars(ucfirst($fish['family'])); ?></span>
                        <span class="badge badge-<?php echo getEnvironmentClass($fish['environment']); ?>">
                            <?php echo htmlspecialchars(ucfirst($fish['environment'])); ?>
                        </span>
                        <span class="badge badge-secondary">
                            <?php echo getSizeLabel($fish['size_category']); ?>
                        </span>
                        <span class="badge badge-<?php echo getStatusClass($fish['status']); ?>">
                            <?php echo ucfirst($fish['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="fish-details-container">
                <div class="quick-facts">
                    <h3>Quick Facts</h3>
                    <div class="facts-grid">
                        <div class="fact-item">
                            <span class="fact-label">Family</span>
                            <span class="fact-value"><?php echo htmlspecialchars(ucfirst($fish['family'])); ?></span>
                        </div>
                        
                        <div class="fact-item">
                            <span class="fact-label">Environment</span>
                            <span class="fact-value"><?php echo htmlspecialchars(ucfirst($fish['environment'])); ?></span>
                        </div>
                        
                        <div class="fact-item">
                            <span class="fact-label">Size</span>
                            <span class="fact-value"><?php echo getSizeLabel($fish['size_category']); ?></span>
                        </div>
                        
                        <div class="fact-item">
                            <span class="fact-label">Submitted By</span>
                            <span class="fact-value"><?php echo htmlspecialchars($fish['submitter_name']); ?></span>
                        </div>
                        
                        <div class="fact-item">
                            <span class="fact-label">Submission Date</span>
                            <span class="fact-value"><?php echo date('F j, Y', strtotime($fish['created_at'])); ?></span>
                        </div>
                        
                        <div class="fact-item">
                            <span class="fact-label">Status</span>
                            <span class="fact-value"><?php echo ucfirst($fish['status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="detailed-info">
                    <div class="tab-content active">
                        <h4>Description</h4>
                        <div class="fish-description">
                            <?php echo nl2br(htmlspecialchars($fish['description'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="actions-section">
                    <?php if ($is_admin): ?>
                        <!-- Admin Actions -->
                        <a href="admin/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        
                        <?php if ($fish['status'] === 'pending'): ?>
                            <form method="post" action="admin/update_status.php" class="inline-form">
                                <input type="hidden" name="fish_id" value="<?php echo $fish_id; ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            
                            <form method="post" action="admin/update_status.php" class="inline-form">
                                <input type="hidden" name="fish_id" value="<?php echo $fish_id; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="post" action="admin/delete_fish.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this fish entry?');">
                            <input type="hidden" name="fish_id" value="<?php echo $fish_id; ?>">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    <?php else: ?>
                        <!-- User Actions -->
                        <a href="user/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        
                        <?php if ($fish['status'] === 'pending'): ?>
                            <a href="edit_fish.php?id=<?php echo $fish_id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function changeImage(src, thumbnail) {
        document.getElementById('mainImage').src = src;
        
        // Update active thumbnail
        if (thumbnail) {
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            thumbnail.classList.add('active');
        }
    }
</script>

<?php
// Helper functions
function getEnvironmentClass($environment) {
    switch ($environment) {
        case 'freshwater':
            return 'info';
        case 'saltwater':
            return 'success';
        case 'brackish':
            return 'warning';
        default:
            return 'secondary';
    }
}

function getSizeLabel($size) {
    switch ($size) {
        case 'small':
            return 'Small (< 10cm)';
        case 'medium':
            return 'Medium (10-30cm)';
        case 'large':
            return 'Large (> 30cm)';
        default:
            return 'Unknown';
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<?php include 'includes/footer.php'; ?> 