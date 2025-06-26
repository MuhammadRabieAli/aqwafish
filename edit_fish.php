<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to edit fish information', 'error');
}

// Get fish ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', 'Invalid fish ID', 'error');
}

$fish_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get fish details
$sql = "SELECT * FROM fish WHERE id = ?";
        
// Admin can edit any fish, regular users can only edit their own pending submissions
if (!$is_admin) {
    $sql .= " AND submitted_by = ?";
    if (!isset($_GET['bypass_status'])) {
        $sql .= " AND status = 'pending'";
    }
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
    $error_msg = $is_admin ? 'Fish not found' : 'Fish not found or cannot be edited at this time';
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php', $error_msg, 'error');
}

$fish = mysqli_fetch_assoc($result);

// Get fish images
$images_sql = "SELECT * FROM fish_images WHERE fish_id = ? ORDER BY is_primary DESC";
$images_stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($images_stmt, 'i', $fish_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

    // Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First, verify that the user exists
    $user_check_sql = "SELECT id FROM users WHERE id = ?";
    $user_check_stmt = mysqli_prepare($conn, $user_check_sql);
    if (!$user_check_stmt) {
        $general_error = "Error preparing user check statement: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($user_check_stmt, 'i', $user_id);
        mysqli_stmt_execute($user_check_stmt);
        mysqli_stmt_store_result($user_check_stmt);
        
        if (mysqli_stmt_num_rows($user_check_stmt) === 0) {
            $general_error = "Error: User ID $user_id does not exist. Cannot edit fish.";
        }
        mysqli_stmt_close($user_check_stmt);
    }
    
    // Only proceed if no error occurred
    if (!isset($general_error)) {
        // Validate and sanitize inputs
        $name = sanitize($_POST['name']);
        $scientific_name = sanitize($_POST['scientific_name']);
        $family = sanitize($_POST['family']);
        $environment = sanitize($_POST['environment']);
        $size_category = sanitize($_POST['size_category']);
        $description = sanitize($_POST['description']);
    
    // Get identifier fields
    $process_id = sanitize($_POST['process_id'] ?? '');
    $sample_id = sanitize($_POST['sample_id'] ?? '');
    $museum_id = sanitize($_POST['museum_id'] ?? '');
    $collection_code = sanitize($_POST['collection_code'] ?? '');
    $field_id = sanitize($_POST['field_id'] ?? '');
    $deposited_in = sanitize($_POST['deposited_in'] ?? '');
    $specimen_linkout = sanitize($_POST['specimen_linkout'] ?? '');
    
    // Get genetic sequence fields
    $sequence_type = sanitize($_POST['sequence_type'] ?? '');
    $sequence_id = sanitize($_POST['sequence_id'] ?? '');
    $genbank_accession = sanitize($_POST['genbank_accession'] ?? '');
    $sequence_updated_at = !empty($_POST['sequence_updated_at']) ? $_POST['sequence_updated_at'] : null;
    $genome_type = sanitize($_POST['genome_type'] ?? '');
    $locus = sanitize($_POST['locus'] ?? '');
    $nucleotides_count = !empty($_POST['nucleotides_count']) ? intval($_POST['nucleotides_count']) : null;
    $dna_sequence = sanitize($_POST['dna_sequence'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = "Fish name is required";
    }
    
    if (empty($family)) {
        $errors['family'] = "Family is required";
    }
    
    if (empty($environment) || !in_array($environment, ['freshwater', 'saltwater', 'brackish'])) {
        $errors['environment'] = "Valid environment is required";
    }
    
    if (empty($size_category) || !in_array($size_category, ['small', 'medium', 'large'])) {
        $errors['size_category'] = "Valid size category is required";
    }
    
    if (empty($description)) {
        $errors['description'] = "Description is required";
    }
    
    // If no errors, update the fish
    if (empty($errors)) {
        $update_sql = "UPDATE fish SET 
                      name = ?,
                      scientific_name = ?,
                      family = ?,
                      environment = ?,
                      size_category = ?,
                      description = ?,
                      process_id = ?,
                      sample_id = ?,
                      museum_id = ?,
                      collection_code = ?,
                      field_id = ?,
                      deposited_in = ?,
                      specimen_linkout = ?,
                      sequence_type = ?,
                      sequence_id = ?,
                      genbank_accession = ?,
                      sequence_updated_at = ?,
                      genome_type = ?,
                      locus = ?,
                      nucleotides_count = ?,
                      dna_sequence = ?,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?";
                      
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if (!$update_stmt) {
            $general_error = "Error preparing statement: " . mysqli_error($conn);
        } else {
            // Only try to bind parameters if prepare was successful
            $bind_result = mysqli_stmt_bind_param(
                $update_stmt, 
                'sssssssssssssssssssisi',
                $name, 
                $scientific_name, 
                $family, 
                $environment, 
                $size_category, 
                $description,
                $process_id,
                $sample_id,
                $museum_id,
                $collection_code,
                $field_id,
                $deposited_in,
                $specimen_linkout,
                $sequence_type,
                $sequence_id,
                $genbank_accession,
                $sequence_updated_at,
                $genome_type,
                $locus,
                $nucleotides_count,
                $dna_sequence,
                $fish_id
            );
            
            if (!$bind_result) {
                $general_error = "Error binding parameters: " . mysqli_stmt_error($update_stmt);
            }
        }
        
        if (!isset($general_error) && mysqli_stmt_execute($update_stmt)) {
            // Handle datasets
            // First, delete all existing datasets for this fish
            $delete_datasets_sql = "DELETE FROM fish_datasets WHERE fish_id = ?";
            $delete_datasets_stmt = mysqli_prepare($conn, $delete_datasets_sql);
            mysqli_stmt_bind_param($delete_datasets_stmt, 'i', $fish_id);
            mysqli_stmt_execute($delete_datasets_stmt);
            
            // Now insert the updated datasets
            if (isset($_POST['dataset_code']) && is_array($_POST['dataset_code'])) {
                $dataset_codes = $_POST['dataset_code'];
                $dataset_names = $_POST['dataset_name'] ?? [];
                $dataset_urls = $_POST['dataset_url'] ?? [];
                
                $insert_dataset_sql = "INSERT INTO fish_datasets (fish_id, dataset_code, dataset_name, dataset_url) VALUES (?, ?, ?, ?)";
                $insert_dataset_stmt = mysqli_prepare($conn, $insert_dataset_sql);
                
                for ($i = 0; $i < count($dataset_codes); $i++) {
                    if (!empty($dataset_codes[$i]) && !empty($dataset_names[$i])) {
                        $code = sanitize($dataset_codes[$i]);
                        $name = sanitize($dataset_names[$i]);
                        $url = !empty($dataset_urls[$i]) ? sanitize($dataset_urls[$i]) : null;
                        
                        mysqli_stmt_bind_param($insert_dataset_stmt, 'isss', $fish_id, $code, $name, $url);
                        mysqli_stmt_execute($insert_dataset_stmt);
                    }
                }
            }
            
            // Handle image uploads if any
            if (!empty($_FILES['fish_images']['name'][0])) {
                $uploaded_files = $_FILES['fish_images'];
                $upload_dir = 'uploads/';
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Create upload directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 0; $i < count($uploaded_files['name']); $i++) {
                    if ($uploaded_files['error'][$i] === 0) {
                        $file_name = $uploaded_files['name'][$i];
                        $file_size = $uploaded_files['size'][$i];
                        $file_type = $uploaded_files['type'][$i];
                        $file_tmp = $uploaded_files['tmp_name'][$i];
                        
                        // Validate file type
                        if (!in_array($file_type, $allowed_types)) {
                            $errors[] = "Invalid file type for {$file_name}. Only JPG, PNG, and GIF are allowed.";
                            continue;
                        }
                        
                        // Validate file size
                        if ($file_size > $max_size) {
                            $errors[] = "File {$file_name} is too large. Maximum size is 5MB.";
                            continue;
                        }
                        
                        // Generate unique file name
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_file_name = uniqid('fish_') . '.' . $ext;
                        $file_path = $upload_dir . $new_file_name;
                        
                        // Upload file
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            // Save image in database
                            $img_sql = "INSERT INTO fish_images (fish_id, image_path) VALUES (?, ?)";
                            $img_stmt = mysqli_prepare($conn, $img_sql);
                            mysqli_stmt_bind_param($img_stmt, 'is', $fish_id, $file_path);
                            mysqli_stmt_execute($img_stmt);
                        }
                    }
                }
            }
            
            if (empty($errors)) {
                // Set success message and redirect
                $redirect_path = $is_admin ? "fish_details.php?id=$fish_id" : "user/dashboard.php";
                redirect($redirect_path, "Fish information updated successfully", 'success');
            }
        } else {
            $general_error = "Error updating fish information: " . mysqli_error($conn);
        }
    }
    }
}

$page_title = "Edit Fish Information";
$extra_css = ['styles/submit-form.css'];
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
        <span>Edit Fish</span>
    </div>
</div>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <div class="form-container">
            <h1>Edit Fish Information</h1>
            <p class="form-intro">Make changes to your fish submission. <?php echo !$is_admin && $fish['status'] === 'pending' ? 'You can edit your submission while it is pending review.' : ''; ?></p>
            
            <?php if (isset($general_error)): ?>
                <div class="alert alert-error"><?php echo $general_error; ?></div>
            <?php endif; ?>
            
            <form id="fishForm" method="post" action="edit_fish.php?id=<?php echo $fish_id; ?><?php echo $is_admin && $fish['status'] !== 'pending' ? '&bypass_status=1' : ''; ?>" enctype="multipart/form-data">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Fish Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-input <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($fish['name']); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="field-error"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="scientific_name">Scientific Name</label>
                        <input type="text" id="scientific_name" name="scientific_name" class="form-input" 
                               value="<?php echo htmlspecialchars($fish['scientific_name']); ?>">
                        <div class="form-hint">Format: <em>Genus species</em> (e.g., <em>Carassius auratus</em>)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="family">Family <span class="required">*</span></label>
                        <input type="text" id="family" name="family" class="form-input <?php echo isset($errors['family']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($fish['family']); ?>" required>
                        <?php if (isset($errors['family'])): ?>
                            <div class="field-error"><?php echo $errors['family']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Characteristics</h2>
                    
                    <div class="form-group">
                        <label for="environment">Environment <span class="required">*</span></label>
                        <select id="environment" name="environment" class="form-select <?php echo isset($errors['environment']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select environment</option>
                            <option value="freshwater" <?php echo $fish['environment'] === 'freshwater' ? 'selected' : ''; ?>>Freshwater</option>
                            <option value="saltwater" <?php echo $fish['environment'] === 'saltwater' ? 'selected' : ''; ?>>Saltwater</option>
                            <option value="brackish" <?php echo $fish['environment'] === 'brackish' ? 'selected' : ''; ?>>Brackish</option>
                        </select>
                        <?php if (isset($errors['environment'])): ?>
                            <div class="field-error"><?php echo $errors['environment']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="size_category">Size Category <span class="required">*</span></label>
                        <select id="size_category" name="size_category" class="form-select <?php echo isset($errors['size_category']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select size category</option>
                            <option value="small" <?php echo $fish['size_category'] === 'small' ? 'selected' : ''; ?>>Small (< 10cm)</option>
                            <option value="medium" <?php echo $fish['size_category'] === 'medium' ? 'selected' : ''; ?>>Medium (10-30cm)</option>
                            <option value="large" <?php echo $fish['size_category'] === 'large' ? 'selected' : ''; ?>>Large (> 30cm)</option>
                        </select>
                        <?php if (isset($errors['size_category'])): ?>
                            <div class="field-error"><?php echo $errors['size_category']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Description <span class="required">*</span></h2>
                    
                    <div class="form-group">
                        <textarea id="description" name="description" class="form-textarea <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                rows="6" required><?php echo htmlspecialchars($fish['description']); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="field-error"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                        <div class="form-hint">Provide detailed information about the fish, including habitat, behavior, diet, and any other relevant details.</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Identifiers</h2>
                    <p class="form-intro">Scientific identification and reference information</p>
                    
                    <div class="identifiers-grid">
                        <div class="form-group">
                            <label for="process_id">Process ID</label>
                            <input type="text" id="process_id" name="process_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['process_id'] ?? ''); ?>">
                            <div class="form-hint">e.g., ABFJ238-07</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sample_id">Sample ID</label>
                            <input type="text" id="sample_id" name="sample_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['sample_id'] ?? ''); ?>">
                            <div class="form-hint">e.g., DAT6</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="museum_id">Museum ID</label>
                            <input type="text" id="museum_id" name="museum_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['museum_id'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_code">Collection Code</label>
                            <input type="text" id="collection_code" name="collection_code" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['collection_code'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="field_id">Field ID</label>
                            <input type="text" id="field_id" name="field_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['field_id'] ?? ''); ?>">
                            <div class="form-hint">e.g., DAT6</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="deposited_in">Deposited In</label>
                            <input type="text" id="deposited_in" name="deposited_in" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['deposited_in'] ?? ''); ?>">
                            <div class="form-hint">e.g., National Research Institute of Fisheries Science, Japan</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="specimen_linkout">Specimen Linkout (URL)</label>
                        <input type="url" id="specimen_linkout" name="specimen_linkout" class="form-input" 
                               value="<?php echo htmlspecialchars($fish['specimen_linkout'] ?? ''); ?>">
                        <div class="form-hint">External link to specimen information, if available</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Associated Datasets</label>
                        <div class="dataset-container">
                            <?php 
                            // Get existing datasets
                            $datasets_sql = "SELECT * FROM fish_datasets WHERE fish_id = ? ORDER BY id";
                            $datasets_stmt = mysqli_prepare($conn, $datasets_sql);
                            mysqli_stmt_bind_param($datasets_stmt, 'i', $fish_id);
                            mysqli_stmt_execute($datasets_stmt);
                            $datasets_result = mysqli_stmt_get_result($datasets_stmt);
                            $has_datasets = mysqli_num_rows($datasets_result) > 0;
                            
                            if ($has_datasets) {
                                while ($dataset = mysqli_fetch_assoc($datasets_result)) {
                            ?>
                                <div class="dataset-entry">
                                    <div class="dataset-fields">
                                        <input type="text" name="dataset_code[]" value="<?php echo htmlspecialchars($dataset['dataset_code']); ?>" placeholder="Dataset Code (e.g., DS-AH2020)" class="form-input dataset-code">
                                        <input type="text" name="dataset_name[]" value="<?php echo htmlspecialchars($dataset['dataset_name']); ?>" placeholder="Dataset Name (e.g., Ablennes hians_2020)" class="form-input dataset-name">
                                        <input type="url" name="dataset_url[]" value="<?php echo htmlspecialchars($dataset['dataset_url'] ?? ''); ?>" placeholder="Dataset URL (optional)" class="form-input dataset-url">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger remove-dataset"><i class="fas fa-times"></i></button>
                                </div>
                            <?php
                                }
                            }
                            ?>
                            <div class="dataset-entry">
                                <div class="dataset-fields">
                                    <input type="text" name="dataset_code[]" placeholder="Dataset Code (e.g., DS-AH2020)" class="form-input dataset-code">
                                    <input type="text" name="dataset_name[]" placeholder="Dataset Name (e.g., Ablennes hians_2020)" class="form-input dataset-name">
                                    <input type="url" name="dataset_url[]" placeholder="Dataset URL (optional)" class="form-input dataset-url">
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary add-dataset"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Genetic Sequence</h2>
                    <p class="form-intro">DNA sequence information for scientific reference</p>
                    
                    <div class="form-group">
                        <label for="sequence_type">Sequence Type</label>
                        <input type="text" id="sequence_type" name="sequence_type" class="form-input" 
                               value="<?php echo htmlspecialchars($fish['sequence_type'] ?? 'COI-5P'); ?>">
                        <div class="form-hint">e.g., COI-5P</div>
                    </div>
                    
                    <div class="identifiers-grid">
                        <div class="form-group">
                            <label for="sequence_id">Sequence ID</label>
                            <input type="text" id="sequence_id" name="sequence_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['sequence_id'] ?? ''); ?>">
                            <div class="form-hint">e.g., GBMNB4650-20.COI-5P</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="genbank_accession">GenBank Accession</label>
                            <input type="text" id="genbank_accession" name="genbank_accession" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['genbank_accession'] ?? ''); ?>">
                            <div class="form-hint">e.g., MH377856</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sequence_updated_at">Last Updated</label>
                            <input type="date" id="sequence_updated_at" name="sequence_updated_at" class="form-input" 
                                   value="<?php echo !empty($fish['sequence_updated_at']) ? date('Y-m-d', strtotime($fish['sequence_updated_at'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="genome_type">Genome</label>
                            <input type="text" id="genome_type" name="genome_type" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['genome_type'] ?? ''); ?>">
                            <div class="form-hint">e.g., Mitochondrial</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="locus">Locus</label>
                            <input type="text" id="locus" name="locus" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['locus'] ?? ''); ?>">
                            <div class="form-hint">e.g., Cytochrome Oxidase Subunit 1 5' Region</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nucleotides_count">Nucleotides Count</label>
                            <input type="number" id="nucleotides_count" name="nucleotides_count" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['nucleotides_count'] ?? ''); ?>">
                            <div class="form-hint">e.g., 568</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dna_sequence">DNA Sequence</label>
                        <textarea id="dna_sequence" name="dna_sequence" class="form-textarea" rows="6"><?php echo htmlspecialchars($fish['dna_sequence'] ?? ''); ?></textarea>
                        <div class="form-hint">Enter the DNA sequence (A, T, G, C bases)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Images</h2>
                    
                    <div class="form-group">
                        <label for="fish_images">Upload Additional Images</label>
                        <input type="file" id="fish_images" name="fish_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                        <div class="form-hint">You can upload multiple images (max 5MB each). Current images will be preserved.</div>
                        <div id="image-preview" class="image-preview">
                            <div id="preview-container" class="preview-container"></div>
                        </div>
                    </div>
                    
                    <?php if (mysqli_num_rows($images_result) > 0): ?>
                        <div class="existing-images">
                            <h4>Current Images</h4>
                            <div class="image-preview-container">
                                <?php while ($image = mysqli_fetch_assoc($images_result)): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo $image['image_path']; ?>" alt="Fish Image">
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Fish Information</button>
                    <a href="<?php echo $is_admin ? 'fish_details.php?id=' . $fish_id : 'user/dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Image preview functionality
    document.getElementById('fish_images').addEventListener('change', function(e) {
        const previewContainer = document.getElementById('preview-container');
        previewContainer.innerHTML = ''; // Clear previous previews
        
        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                if (!file.type.match('image.*')) {
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Image Preview';
                    
                    preview.appendChild(img);
                    previewContainer.appendChild(preview);
                }
                
                reader.readAsDataURL(file);
            });
        }
    });
    
    // Dataset entry functionality
    document.addEventListener('click', function(e) {
        // Add new dataset entry
        if (e.target.classList.contains('add-dataset') || e.target.parentElement.classList.contains('add-dataset')) {
            const button = e.target.classList.contains('add-dataset') ? e.target : e.target.parentElement;
            const datasetEntry = button.closest('.dataset-entry');
            const datasetContainer = datasetEntry.parentElement;
            
            // Create new dataset entry
            const newEntry = datasetEntry.cloneNode(true);
            
            // Clear input values
            newEntry.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            
            // Change add button to remove button
            const addButton = newEntry.querySelector('.add-dataset');
            addButton.classList.remove('add-dataset', 'btn-secondary');
            addButton.classList.add('remove-dataset', 'btn-danger');
            
            // Change icon
            const icon = addButton.querySelector('i');
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-times');
            
            // Append new entry
            datasetContainer.appendChild(newEntry);
        }
        
        // Remove dataset entry
        if (e.target.classList.contains('remove-dataset') || e.target.parentElement.classList.contains('remove-dataset')) {
            const button = e.target.classList.contains('remove-dataset') ? e.target : e.target.parentElement;
            const datasetEntry = button.closest('.dataset-entry');
            datasetEntry.remove();
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 