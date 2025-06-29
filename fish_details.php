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

// Get main image and map image specifically
$main_image_sql = "SELECT * FROM fish_images WHERE fish_id = ? AND category = 'main' LIMIT 1";
$main_stmt = mysqli_prepare($conn, $main_image_sql);
mysqli_stmt_bind_param($main_stmt, 'i', $fish_id);
mysqli_stmt_execute($main_stmt);
$main_image_result = mysqli_stmt_get_result($main_stmt);
$main_image = mysqli_fetch_assoc($main_image_result);

$map_image_sql = "SELECT * FROM fish_images WHERE fish_id = ? AND category = 'map' LIMIT 1";
$map_stmt = mysqli_prepare($conn, $map_image_sql);
mysqli_stmt_bind_param($map_stmt, 'i', $fish_id);
mysqli_stmt_execute($map_stmt);
$map_image_result = mysqli_stmt_get_result($map_stmt);
$map_image = mysqli_fetch_assoc($map_image_result);

$page = 'dashboard';
$page_title = $fish['name'] . ' Details';
$extra_css = ['/styles/fish-details.css', '/styles/dashboard.css', '/styles/fish-details-identifiers.css', '/styles/fish-sequence.css'];

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
                        <?php if ($main_image): ?>
                            <div class="gallery-main">
                                <?php 
                                $total_images = mysqli_num_rows($images_result);
                                ?>
                                <a href="fish_gallery.php?id=<?php echo $fish_id; ?>" class="main-image-link" title="View all <?php echo $total_images; ?> images">
                                <img src="<?php echo $main_image['image_path']; ?>" alt="<?php echo $fish['name']; ?>" id="mainImage" class="main-image">
                                    <?php if ($total_images > 1): ?>
                                        <div class="image-overlay">
                                            <span class="image-count"><i class="fas fa-images"></i> <?php echo $total_images; ?> images</span>
                                            <span class="view-all">View Gallery</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>
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
                        
                        <?php if ($map_image): ?>
                        <h4 class="map-heading">Distribution Map</h4>
                        <div class="map-container">
                            <div class="map-image">
                                <img src="<?php echo $map_image['image_path']; ?>" alt="<?php echo htmlspecialchars($fish['name']); ?> distribution map" 
                                     onclick="openMapModal('<?php echo $map_image['image_path']; ?>', '<?php echo htmlspecialchars($fish['name']); ?> Distribution Map')">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Check if any identifier fields exist
                        $has_identifiers = !empty($fish['process_id']) || 
                                          !empty($fish['sample_id']) || 
                                          !empty($fish['museum_id']) || 
                                          !empty($fish['collection_code']) || 
                                          !empty($fish['field_id']) || 
                                          !empty($fish['deposited_in']) || 
                                          !empty($fish['specimen_linkout']);
                                          
                        // Get associated datasets
                        $datasets_sql = "SELECT * FROM fish_datasets WHERE fish_id = ? ORDER BY id";
                        $datasets_stmt = mysqli_prepare($conn, $datasets_sql);
                        mysqli_stmt_bind_param($datasets_stmt, 'i', $fish_id);
                        mysqli_stmt_execute($datasets_stmt);
                        $datasets_result = mysqli_stmt_get_result($datasets_stmt);
                        $has_datasets = mysqli_num_rows($datasets_result) > 0;
                        
                        if ($has_identifiers || $has_datasets):
                        ?>
                        <h4 class="identifiers-heading">Scientific Identifiers</h4>
                        <div class="identifiers-container">
                            <table class="identifiers-table">
                                <tbody>
                                    <?php if (!empty($fish['process_id'])): ?>
                                    <tr>
                                        <th>Process ID</th>
                                        <td><?php echo htmlspecialchars($fish['process_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['sample_id'])): ?>
                                    <tr>
                                        <th>Sample ID</th>
                                        <td><?php echo htmlspecialchars($fish['sample_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['museum_id'])): ?>
                                    <tr>
                                        <th>Museum ID</th>
                                        <td><?php echo htmlspecialchars($fish['museum_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collection_code'])): ?>
                                    <tr>
                                        <th>Collection Code</th>
                                        <td><?php echo htmlspecialchars($fish['collection_code']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['field_id'])): ?>
                                    <tr>
                                        <th>Field ID</th>
                                        <td><?php echo htmlspecialchars($fish['field_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['deposited_in'])): ?>
                                    <tr>
                                        <th>Deposited In</th>
                                        <td><?php echo htmlspecialchars($fish['deposited_in']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if ($has_datasets): ?>
                                    <tr>
                                        <th>Associated Datasets</th>
                                        <td class="datasets-list">
                                            <?php while ($dataset = mysqli_fetch_assoc($datasets_result)): ?>
                                                <div class="dataset-item">
                                                    <?php if (!empty($dataset['dataset_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($dataset['dataset_url']); ?>" target="_blank" rel="noopener">
                                                            <?php echo htmlspecialchars($dataset['dataset_code']); ?> | <?php echo htmlspecialchars($dataset['dataset_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($dataset['dataset_code']); ?> | <?php echo htmlspecialchars($dataset['dataset_name']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['specimen_linkout'])): ?>
                                    <tr>
                                        <th>Specimen Linkout</th>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($fish['specimen_linkout']); ?>" target="_blank" rel="noopener">
                                                External Reference <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Check if genetic sequence data exists
                        $has_sequence = !empty($fish['sequence_type']) || 
                                       !empty($fish['sequence_id']) || 
                                       !empty($fish['genbank_accession']) || 
                                       !empty($fish['locus']) || 
                                       !empty($fish['dna_sequence']);
                        
                        if ($has_sequence):
                        ?>
                        <h4 class="sequence-heading">SEQUENCE: <?php echo htmlspecialchars($fish['sequence_type'] ?? 'COI-5P'); ?></h4>
                        <div class="sequence-container">
                            <table class="identifiers-table">
                                <tbody>
                                    <?php if (!empty($fish['sequence_id'])): ?>
                                    <tr>
                                        <th>Sequence ID</th>
                                        <td><?php echo htmlspecialchars($fish['sequence_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['genbank_accession'])): ?>
                                    <tr>
                                        <th>GenBank Accession</th>
                                        <td><?php echo htmlspecialchars($fish['genbank_accession']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['sequence_updated_at'])): ?>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('Y-m-d', strtotime($fish['sequence_updated_at'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['genome_type'])): ?>
                                    <tr>
                                        <th>Genome</th>
                                        <td><?php echo htmlspecialchars($fish['genome_type']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['locus'])): ?>
                                    <tr>
                                        <th>Locus</th>
                                        <td><?php echo htmlspecialchars($fish['locus']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['nucleotides_count'])): ?>
                                    <tr>
                                        <th>Nucleotides</th>
                                        <td><?php echo htmlspecialchars($fish['nucleotides_count']); ?> bp</td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['dna_sequence'])): ?>
                                    <tr>
                                        <th>DNA Sequence</th>
                                        <td>
                                            <div class="dna-sequence">
                                                <div class="sequence-text" id="sequence-text">
                                                    <?php echo htmlspecialchars($fish['dna_sequence']); ?>
                                                </div>
                                                <div class="sequence-actions">
                                                    <button class="btn btn-sm btn-secondary copy-sequence" data-sequence="<?php echo htmlspecialchars($fish['dna_sequence']); ?>">
                                                        <i class="fas fa-copy"></i> Copy
                                                    </button>
                                                    <button class="btn btn-sm btn-info export-json" data-sequence="<?php echo htmlspecialchars($fish['dna_sequence']); ?>" 
                                                            data-name="<?php echo htmlspecialchars($fish['scientific_name'] ?: $fish['name']); ?>"
                                                            data-id="<?php echo htmlspecialchars($fish['id']); ?>"
                                                            data-type="<?php echo htmlspecialchars($fish['sequence_type'] ?? 'DNA'); ?>"
                                                            data-accession="<?php echo htmlspecialchars($fish['genbank_accession'] ?? ''); ?>">
                                                        <i class="fas fa-file-export"></i> Export JSON
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Barcode Visualization</th>
                                        <td>
                                            <div class="barcode-visualization" id="barcode-visualization">
                                                <!-- Barcode will be generated here by JavaScript -->
                                                <div class="barcode-loading">Generating barcode visualization...</div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Check if collection data exists
                        $has_collection = !empty($fish['collection_country']) || 
                                         !empty($fish['collection_region']) || 
                                         !empty($fish['collection_location']) || 
                                         !empty($fish['collection_date']) || 
                                         !empty($fish['collection_latitude']) || 
                                         !empty($fish['collection_longitude']) || 
                                         !empty($fish['collector_name']);
                        
                        if ($has_collection):
                        ?>
                        <h4 class="collection-heading">COLLECTION INFORMATION</h4>
                        <div class="collection-container">
                            <table class="identifiers-table">
                                <tbody>
                                    <?php if (!empty($fish['collection_country'])): ?>
                                    <tr>
                                        <th>Country/Ocean</th>
                                        <td><?php echo htmlspecialchars($fish['collection_country']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collection_region'])): ?>
                                    <tr>
                                        <th>Province/Region</th>
                                        <td><?php echo htmlspecialchars($fish['collection_region']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collection_location'])): ?>
                                    <tr>
                                        <th>Specific Location</th>
                                        <td><?php echo htmlspecialchars($fish['collection_location']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collection_date'])): ?>
                                    <tr>
                                        <th>Collection Date</th>
                                        <td><?php echo date('Y-m-d', strtotime($fish['collection_date'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collection_latitude']) && !empty($fish['collection_longitude'])): ?>
                                    <tr>
                                        <th>Coordinates</th>
                                        <td>
                                            <?php echo number_format($fish['collection_latitude'], 6); ?>°N, 
                                            <?php echo number_format($fish['collection_longitude'], 6); ?>°E
                                            <br>
                                            <small><a href="https://maps.google.com/?q=<?php echo $fish['collection_latitude']; ?>,<?php echo $fish['collection_longitude']; ?>" target="_blank">
                                                <i class="fas fa-map-marker-alt"></i> View on Google Maps
                                            </a></small>
                                        </td>
                                    </tr>
                                    <?php elseif (!empty($fish['collection_latitude'])): ?>
                                    <tr>
                                        <th>Latitude</th>
                                        <td><?php echo number_format($fish['collection_latitude'], 6); ?>°N</td>
                                    </tr>
                                    <?php elseif (!empty($fish['collection_longitude'])): ?>
                                    <tr>
                                        <th>Longitude</th>
                                        <td><?php echo number_format($fish['collection_longitude'], 6); ?>°E</td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fish['collector_name'])): ?>
                                    <tr>
                                        <th>Collector</th>
                                        <td><?php echo htmlspecialchars($fish['collector_name']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="actions-section">
                    <?php if ($is_admin): ?>
                        <!-- Admin Actions -->
                        <a href="admin/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                        
                        <!-- Admin can always edit fish information, even after approval -->
                        <a href="edit_fish.php?id=<?php echo $fish_id; ?>&bypass_status=1" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                        
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

<!-- JS for copy sequence functionality and barcode visualization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-sequence');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sequence = this.getAttribute('data-sequence');
            navigator.clipboard.writeText(sequence).then(() => {
                // Change button text temporarily
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });
    
    // Export JSON functionality
    const exportButtons = document.querySelectorAll('.export-json');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sequence = this.getAttribute('data-sequence');
            const name = this.getAttribute('data-name');
            const id = this.getAttribute('data-id');
            const sequenceType = this.getAttribute('data-type');
            const accession = this.getAttribute('data-accession');
            
            // Create JSON object with sequence data
            const sequenceData = {
                id: `fish_${id}`,
                name: name,
                sequence_type: sequenceType,
                accession: accession,
                length: sequence.length,
                date_exported: new Date().toISOString().split('T')[0],
                sequence: sequence
            };
            
            // Convert to formatted JSON string
            const jsonContent = JSON.stringify(sequenceData, null, 2);
            
            // Create a blob with the JSON content
            const blob = new Blob([jsonContent], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            // Create a temporary link to download the file
            const link = document.createElement('a');
            link.href = url;
            link.download = `fish_${id}_${name.replace(/\s+/g, '_')}.json`;
            document.body.appendChild(link);
            link.click();
            
            // Clean up
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            // Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Exported!';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
    
    // Color-code DNA sequence text
    const sequenceText = document.getElementById('sequence-text');
    if (sequenceText) {
        // Get the raw text content
        let content = sequenceText.textContent;
        let coloredContent = '';
        
        // Process each character individually
        for (let i = 0; i < content.length; i++) {
            const char = content[i];
            if (char.match(/[aA]/i)) {
                coloredContent += '<span class="a">' + char + '</span>';
            } else if (char.match(/[tT]/i)) {
                coloredContent += '<span class="t">' + char + '</span>';
            } else if (char.match(/[gG]/i)) {
                coloredContent += '<span class="g">' + char + '</span>';
            } else if (char.match(/[cC]/i)) {
                coloredContent += '<span class="c">' + char + '</span>';
            } else {
                coloredContent += char;
            }
        }
        
        sequenceText.innerHTML = coloredContent;
        
        // Generate barcode visualization
        const barcodeVisualization = document.getElementById('barcode-visualization');
        if (barcodeVisualization) {
            // Clean DNA sequence (remove any non-ATGC characters)
            const cleanSequence = content.toUpperCase().replace(/[^ATGC]/g, '');
            
            // Create title
            const title = document.createElement('div');
            title.className = 'barcode-title';
            title.textContent = 'Illustrative Barcode:';
            
            // Create rows container
            const rowsContainer = document.createElement('div');
            rowsContainer.className = 'barcode-rows';
            
            // Calculate how many nucleotides per row based on container width
            // We'll calculate this dynamically after rendering the first row
            let nucleotidesPerRow = 200; // Initial estimate
            const totalRows = Math.ceil(cleanSequence.length / nucleotidesPerRow);
            
            // Generate barcode rows
            for (let row = 0; row < totalRows; row++) {
                const startIndex = row * nucleotidesPerRow;
                const endIndex = Math.min((row + 1) * nucleotidesPerRow, cleanSequence.length);
                const rowSequence = cleanSequence.substring(startIndex, endIndex);
                
                // Create row container
                const rowContainer = document.createElement('div');
                rowContainer.className = 'barcode-row';
                
                // Add starting position
                const startPosition = document.createElement('div');
                startPosition.className = 'barcode-position';
                startPosition.textContent = startIndex.toString();
                rowContainer.appendChild(startPosition);
                
                // Create barcode container for this row
                const barcodeContainer = document.createElement('div');
                barcodeContainer.className = 'barcode-container';
                
                // Generate barcode lines for this row
                for (let i = 0; i < rowSequence.length; i++) {
                    const nucleotide = rowSequence[i];
                    const line = document.createElement('div');
                    line.className = 'barcode-line';
                    
                    // Set color based on nucleotide
                    switch (nucleotide) {
                        case 'A':
                            line.style.backgroundColor = '#1a9850'; // Green
                            break;
                        case 'T':
                            line.style.backgroundColor = '#d73027'; // Red
                            break;
                        case 'G':
                            line.style.backgroundColor = '#fdae61'; // Orange
                            break;
                        case 'C':
                            line.style.backgroundColor = '#4575b4'; // Blue
                            break;
                    }
                    
                    barcodeContainer.appendChild(line);
                }
                
                rowContainer.appendChild(barcodeContainer);
                
                // Add ending position
                const endPosition = document.createElement('div');
                endPosition.className = 'barcode-position end';
                endPosition.textContent = (endIndex - 1).toString();
                rowContainer.appendChild(endPosition);
                
                rowsContainer.appendChild(rowContainer);
            }
            
            // Replace loading message with barcode
            barcodeVisualization.innerHTML = '';
            barcodeVisualization.appendChild(title);
            barcodeVisualization.appendChild(rowsContainer);
            
            // Add window resize handler to adjust barcode layout
            const adjustBarcodeLayout = () => {
                // Get container width
                const containerWidth = barcodeVisualization.clientWidth - 100; // Account for position numbers
                
                // Calculate optimal number of bars per row
                // Assuming each bar is about 2px wide on average (including margin)
                const optimalBarsPerRow = Math.floor(containerWidth / 2);
                
                // Update rows if needed
                if (optimalBarsPerRow > 50 && Math.abs(optimalBarsPerRow - nucleotidesPerRow) > 50) {
                    nucleotidesPerRow = optimalBarsPerRow;
                    
                    // Clear existing rows
                    rowsContainer.innerHTML = '';
                    
                    // Regenerate rows with new calculation
                    const newTotalRows = Math.ceil(cleanSequence.length / nucleotidesPerRow);
                    
                    for (let row = 0; row < newTotalRows; row++) {
                        const startIndex = row * nucleotidesPerRow;
                        const endIndex = Math.min((row + 1) * nucleotidesPerRow, cleanSequence.length);
                        const rowSequence = cleanSequence.substring(startIndex, endIndex);
                        
                        // Create row container
                        const rowContainer = document.createElement('div');
                        rowContainer.className = 'barcode-row';
                        
                        // Add starting position
                        const startPosition = document.createElement('div');
                        startPosition.className = 'barcode-position';
                        startPosition.textContent = startIndex.toString();
                        rowContainer.appendChild(startPosition);
                        
                        // Create barcode container for this row
                        const barcodeContainer = document.createElement('div');
                        barcodeContainer.className = 'barcode-container';
                        
                        // Generate barcode lines for this row
                        for (let i = 0; i < rowSequence.length; i++) {
                            const nucleotide = rowSequence[i];
                            const line = document.createElement('div');
                            line.className = 'barcode-line';
                            
                            // Set color based on nucleotide
                            switch (nucleotide) {
                                case 'A':
                                    line.style.backgroundColor = '#1a9850'; // Green
                                    break;
                                case 'T':
                                    line.style.backgroundColor = '#d73027'; // Red
                                    break;
                                case 'G':
                                    line.style.backgroundColor = '#fdae61'; // Orange
                                    break;
                                case 'C':
                                    line.style.backgroundColor = '#4575b4'; // Blue
                                    break;
                            }
                            
                            barcodeContainer.appendChild(line);
                        }
                        
                        rowContainer.appendChild(barcodeContainer);
                        
                        // Add ending position
                        const endPosition = document.createElement('div');
                        endPosition.className = 'barcode-position end';
                        endPosition.textContent = (endIndex - 1).toString();
                        rowContainer.appendChild(endPosition);
                        
                        rowsContainer.appendChild(rowContainer);
                    }
                }
            };
            
            // Initial adjustment
            setTimeout(adjustBarcodeLayout, 0);
            
            // Add resize listener
            window.addEventListener('resize', adjustBarcodeLayout);
        }
    }
});

// Map modal functionality
function openMapModal(imageSrc, altText) {
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

<!-- Image Modal for Map -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="">
    </div>
</div>

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