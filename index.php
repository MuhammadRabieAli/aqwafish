<?php
require_once 'config.php';

$page = 'home';
$page_title = 'Fish Information Management';
$extra_css = ['/styles/homepage.css', '/styles/fish-cards.css'];
$extra_js = ['/scripts/homepage.js'];

// Get fish data from database
$sql = "SELECT f.*, fi.image_path FROM fish f 
        LEFT JOIN (
            SELECT fish_id, image_path 
            FROM fish_images 
            WHERE is_primary = 1
            UNION
            SELECT fish_id, MIN(image_path) 
            FROM fish_images 
            GROUP BY fish_id
        ) fi ON f.id = fi.fish_id
        WHERE f.status = 'approved'";

// Add filters if provided
$where_clauses = [];
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $where_clauses[] = "(f.name LIKE ? OR f.scientific_name LIKE ? OR f.family LIKE ? OR f.environment LIKE ? OR f.description LIKE ? OR f.process_id LIKE ? OR f.sample_id LIKE ? OR f.museum_id LIKE ? OR f.collection_code LIKE ? OR f.field_id LIKE ? OR f.deposited_in LIKE ? OR f.specimen_linkout LIKE ? OR f.sequence_type LIKE ? OR f.sequence_id LIKE ? OR f.genbank_accession LIKE ? OR f.genome_type LIKE ? OR f.locus LIKE ? OR f.dna_sequence LIKE ?)";
    for ($i = 0; $i < 18; $i++) {
        $params[] = "%$search%";
    }
}

if (isset($_GET['family']) && !empty($_GET['family'])) {
    $family = sanitize($_GET['family']);
    $where_clauses[] = "f.family = ?";
    $params[] = $family;
}

if (isset($_GET['environment']) && !empty($_GET['environment'])) {
    $environment = sanitize($_GET['environment']);
    $where_clauses[] = "f.environment = ?";
    $params[] = $environment;
}

if (isset($_GET['size']) && !empty($_GET['size'])) {
    $size = sanitize($_GET['size']);
    $where_clauses[] = "f.size_category = ?";
    $params[] = $size;
}

// Construct the full query with filters
if (!empty($where_clauses)) {
    $sql .= " AND " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY f.name ASC";

// Prepare and execute the statement
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get distinct families for filter dropdown
$family_query = "SELECT DISTINCT family FROM fish WHERE status = 'approved' ORDER BY family";
$family_result = mysqli_query($conn, $family_query);

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Discover Marine Life</h1>
        <p class="hero-subtitle">Explore comprehensive information about fish species from around the world</p>
        
        <!-- Search Bar -->
        <div class="search-container">
            <form action="index.php" method="get" id="searchForm">
                <div class="search-input-wrapper">
                    <input type="text" id="searchInput" name="search" placeholder="Search fish species..." class="search-input" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn" id="searchBtn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Loading Indicator -->
<div id="loading" class="loading" style="display: none;">
    <div class="spinner"></div>
</div>

<!-- Filters Section -->
<section class="filters-section">
    <div class="container">
        <form action="index.php" method="get" id="filterForm">
            <?php if (isset($_GET['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>
            
            <div class="filters-container">
                <div class="filter-group">
                    <label for="familyFilter">Family</label>
                    <select id="familyFilter" name="family" class="filter-select">
                        <option value="">All Families</option>
                        <?php while ($family_row = mysqli_fetch_assoc($family_result)): ?>
                            <option value="<?php echo $family_row['family']; ?>" 
                                <?php echo (isset($_GET['family']) && $_GET['family'] == $family_row['family']) ? 'selected' : ''; ?>>
                                <?php echo $family_row['family']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="environmentFilter">Environment</label>
                    <select id="environmentFilter" name="environment" class="filter-select">
                        <option value="">All Environments</option>
                        <option value="freshwater" <?php echo (isset($_GET['environment']) && $_GET['environment'] == 'freshwater') ? 'selected' : ''; ?>>Freshwater</option>
                        <option value="saltwater" <?php echo (isset($_GET['environment']) && $_GET['environment'] == 'saltwater') ? 'selected' : ''; ?>>Saltwater</option>
                        <option value="brackish" <?php echo (isset($_GET['environment']) && $_GET['environment'] == 'brackish') ? 'selected' : ''; ?>>Brackish</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sizeFilter">Size</label>
                    <select id="sizeFilter" name="size" class="filter-select">
                        <option value="">All Sizes</option>
                        <option value="small" <?php echo (isset($_GET['size']) && $_GET['size'] == 'small') ? 'selected' : ''; ?>>Small (&lt; 10cm)</option>
                        <option value="medium" <?php echo (isset($_GET['size']) && $_GET['size'] == 'medium') ? 'selected' : ''; ?>>Medium (10-30cm)</option>
                        <option value="large" <?php echo (isset($_GET['size']) && $_GET['size'] == 'large') ? 'selected' : ''; ?>>Large (&gt; 30cm)</option>
                    </select>
                </div>
                
                <div class="view-toggle">
                    <button type="button" class="view-btn active" id="gridView"><i class="fas fa-th-large"></i></button>
                    <button type="button" class="view-btn" id="listView"><i class="fas fa-list"></i></button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Fish Cards Section -->
<main class="main-content">
    <div class="container">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="fish-grid" id="fishGrid">
                <?php while ($fish = mysqli_fetch_assoc($result)): ?>
                    <div class="fish-card">
                        <div class="fish-image">
                            <?php if ($fish['image_path']): ?>
                                <img src="<?php echo $fish['image_path']; ?>" alt="<?php echo $fish['name']; ?>">
                            <?php else: ?>
                                <div class="placeholder-image"><i class="fas fa-fish"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="fish-info">
                            <h3><?php echo $fish['name']; ?></h3>
                            <p class="fish-family"><?php echo $fish['family']; ?></p>
                            <p class="fish-environment">
                                <?php 
                                switch ($fish['environment']) {
                                    case 'freshwater':
                                        echo '<i class="fas fa-water"></i> Freshwater';
                                        break;
                                    case 'saltwater':
                                        echo '<i class="fas fa-water"></i> Saltwater';
                                        break;
                                    case 'brackish':
                                        echo '<i class="fas fa-water"></i> Brackish';
                                        break;
                                    default:
                                        echo '<i class="fas fa-water"></i> Unknown';
                                }
                                ?>
                            </p>
                            <a href="fish.php?id=<?php echo $fish['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results" id="noResults">
                <div class="no-results-icon"><i class="fas fa-fish fa-3x"></i></div>
                <h3>No fish found</h3>
                <p>Try adjusting your search criteria</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Auto-submit form when filters change
    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', () => {
            document.getElementById('filterForm').submit();
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 