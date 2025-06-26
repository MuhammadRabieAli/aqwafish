<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view fish gallery', 'error');
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
    // Regular users can only view their own submissions or approved fishes
    $sql .= " AND (f.submitted_by = ? OR f.status = 'approved')";
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

$page = 'gallery';
$page_title = $fish['name'] . ' Gallery';
$extra_css = ['/styles/fish-gallery.css', '/styles/dashboard.css'];

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
        <a href="fish_details.php?id=<?php echo $fish_id; ?>">Fish Details</a>
        <span class="breadcrumb-separator">›</span>
        <span>Image Gallery</span>
    </div>
</div>

<!-- Main Content -->
<main class="gallery-page">
    <div class="container">
        <div class="gallery-header">
            <h1><?php echo htmlspecialchars($fish['name']); ?> - Image Gallery</h1>
            <a href="fish_details.php?id=<?php echo $fish_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Fish Details</a>
        </div>
        
        <?php if (mysqli_num_rows($images_result) > 0): ?>
            <div class="gallery-grid">
                <?php while ($image = mysqli_fetch_assoc($images_result)): ?>
                    <div class="gallery-item">
                        <div class="gallery-image">
                            <img src="<?php echo $image['image_path']; ?>" alt="<?php echo htmlspecialchars($fish['name']); ?>" 
                                 onclick="openModal('<?php echo $image['image_path']; ?>', '<?php echo htmlspecialchars($fish['name']); ?>')">
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-images">
                <p>No images available for this fish.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="">
    </div>
</div>

<script>
    // Modal functionality
    function openModal(imageSrc, altText) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        
        modal.style.display = "flex";
        modalImg.src = imageSrc;
        modalImg.alt = altText;
        
        // Disable scroll on body when modal is open
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('imageModal').style.display = "none";
        // Re-enable scroll
        document.body.style.overflow = 'auto';
    }
    
    // Close modal when clicking outside the image
    window.onclick = function(event) {
        const modal = document.getElementById('imageModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    
    // Handle keyboard events
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 