<?php
require_once 'config.php';

$page = 'fish';
$extra_css = ['/styles/fish-details.css'];

// Get fish ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php', 'Invalid fish ID', 'error');
}

$fish_id = (int)$_GET['id'];

// Get fish details
$sql = "SELECT f.* FROM fish f WHERE f.id = ? AND f.status = 'approved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $fish_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $fish_exists = false;
    $page_title = 'Fish Not Found';
} else {
    $fish = mysqli_fetch_assoc($result);
    $fish_exists = true;
    $page_title = $fish['name'];
    
    // Get fish images
    $images_sql = "SELECT * FROM fish_images WHERE fish_id = ? ORDER BY is_primary DESC";
    $stmt = mysqli_prepare($conn, $images_sql);
    mysqli_stmt_bind_param($stmt, 'i', $fish_id);
    mysqli_stmt_execute($stmt);
    $images_result = mysqli_stmt_get_result($stmt);
    
    // Get related fish (same family)
    $related_sql = "SELECT f.id, f.name, f.family, fi.image_path 
                   FROM fish f 
                   LEFT JOIN (
                       SELECT fish_id, MIN(image_path) as image_path
                       FROM fish_images
                       GROUP BY fish_id
                   ) fi ON f.id = fi.fish_id
                   WHERE f.family = ? AND f.id != ? AND f.status = 'approved'
                   LIMIT 4";
    $stmt = mysqli_prepare($conn, $related_sql);
    mysqli_stmt_bind_param($stmt, 'si', $fish['family'], $fish_id);
    mysqli_stmt_execute($stmt);
    $related_result = mysqli_stmt_get_result($stmt);
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Home</a>
        <span class="breadcrumb-separator">›</span>
        <span><?php echo $fish_exists ? htmlspecialchars($fish['name']) : 'Fish Not Found'; ?></span>
    </div>
</div>

<!-- Main Content -->
<main class="fish-details">
    <div class="container">
        <?php if ($fish_exists): ?>
            <div class="fish-content loaded">
                <div class="fish-header">
                    <div class="fish-gallery">
                        <?php if (mysqli_num_rows($images_result) > 0): ?>
                            <div class="gallery-main">
                                <?php 
                                mysqli_data_seek($images_result, 0);
                                $first_image = mysqli_fetch_assoc($images_result);
                                ?>
                                <img src="<?php echo $first_image['image_path']; ?>" alt="<?php echo $fish['name']; ?>" id="mainImage" class="main-image">
                            </div>
                            
                            <?php if (mysqli_num_rows($images_result) > 1): ?>
                                <div class="thumbnail-strip">
                                    <?php 
                                    mysqli_data_seek($images_result, 0);
                                    while ($image = mysqli_fetch_assoc($images_result)): 
                                    ?>
                                        <img 
                                            src="<?php echo $image['image_path']; ?>" 
                                            alt="<?php echo $fish['name']; ?>" 
                                            class="thumbnail <?php echo ($image === $first_image) ? 'active' : ''; ?>"
                                            onclick="changeMainImage('<?php echo $image['image_path']; ?>', this)">
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
                                <span class="fact-value">
                                    <?php echo htmlspecialchars(ucfirst($fish['environment'])); ?>
                                </span>
                            </div>
                            <div class="fact-item">
                                <span class="fact-label">Size</span>
                                <span class="fact-value">
                                    <?php echo getSizeLabel($fish['size_category']); ?>
                                </span>
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
                </div>
            </div>
            
            <!-- Related Fish Section -->
            <?php if (mysqli_num_rows($related_result) > 0): ?>
                <section class="related-fish">
                    <h2>Related Fish</h2>
                    <div class="related-grid">
                        <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                            <div class="related-card">
                                <a href="fish.php?id=<?php echo $related['id']; ?>">
                                    <?php if (!empty($related['image_path'])): ?>
                                        <img src="<?php echo $related['image_path']; ?>" alt="<?php echo $related['name']; ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image"><i class="fas fa-fish"></i></div>
                                    <?php endif; ?>
                                    <div class="related-card-content">
                                        <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                                        <p><?php echo htmlspecialchars(ucfirst($related['family'])); ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="error-state">
                <div class="error-icon"><i class="fas fa-fish fa-3x"></i></div>
                <h2>Fish Not Found</h2>
                <p>The fish you're looking for doesn't exist or has been removed.</p>
                <a href="index.php" class="btn btn-primary">Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    function changeMainImage(src, thumbnail) {
        document.getElementById('mainImage').src = src;
        
        // Update active thumbnail
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => thumb.classList.remove('active'));
        if (thumbnail) {
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
?>

<?php include 'includes/footer.php'; ?> 