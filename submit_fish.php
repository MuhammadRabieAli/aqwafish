<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to submit fish information', 'error');
}

$page = 'submit';
$page_title = 'Submit Fish';
$extra_css = ['styles/submit-form.css'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $scientific_name = sanitize($_POST['scientific_name']);
    $family = sanitize($_POST['family']);
    $environment = sanitize($_POST['environment']);
    $size_category = sanitize($_POST['size_category']);
    $description = sanitize($_POST['description']);
    $user_id = $_SESSION['user_id'];
    
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
    
    // Get collection fields
    $collection_country = sanitize($_POST['collection_country'] ?? '');
    $collection_region = sanitize($_POST['collection_region'] ?? '');
    $collection_location = sanitize($_POST['collection_location'] ?? '');
    $collection_date = !empty($_POST['collection_date']) ? $_POST['collection_date'] : null;
    $collection_latitude = !empty($_POST['collection_latitude']) ? floatval($_POST['collection_latitude']) : null;
    $collection_longitude = !empty($_POST['collection_longitude']) ? floatval($_POST['collection_longitude']) : null;
    $collector_name = sanitize($_POST['collector_name'] ?? '');
    
    $errors = [];
    
    // Validate input
    if (empty($name)) {
        $errors['name'] = "Fish name is required";
    }
    
    if (empty($family)) {
        $errors['family'] = "Family is required";
    }
    
    if (empty($environment)) {
        $errors['environment'] = "Environment is required";
    }
    
    if (empty($size_category)) {
        $errors['size_category'] = "Size category is required";
    }
    
    if (empty($description)) {
        $errors['description'] = "Description is required";
    }
    
    // Check if files were uploaded in any category
    $has_main = isset($_FILES['main_image']) && !empty($_FILES['main_image']['name'][0]);
                $has_fish = isset($_FILES['fish_images']) && !empty($_FILES['fish_images']['name'][0]);
    $has_skeleton = isset($_FILES['skeleton_images']) && !empty($_FILES['skeleton_images']['name'][0]);
    $has_disease = isset($_FILES['disease_images']) && !empty($_FILES['disease_images']['name'][0]);
    $has_map = isset($_FILES['map_image']) && !empty($_FILES['map_image']['name']);
    
                $has_files = $has_main || $has_fish || $has_skeleton || $has_disease || $has_map;
    
    // Validate that main images are uploaded
    if (!$has_main) {
        $errors['main_image'] = "At least one main image is required";
    }
    
    // If no errors, insert fish data
    if (empty($errors)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert fish data
            $sql = "INSERT INTO fish (name, scientific_name, family, environment, size_category, description, submitted_by, status,
                          process_id, sample_id, museum_id, collection_code, field_id, deposited_in, specimen_linkout,
                          sequence_type, sequence_id, genbank_accession, sequence_updated_at, genome_type, locus, nucleotides_count, dna_sequence,
                          collection_country, collection_region, collection_location, collection_date, collection_latitude, collection_longitude, collector_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            // Verify that the user exists
            $user_check_sql = "SELECT id FROM users WHERE id = ?";
            $user_check_stmt = mysqli_prepare($conn, $user_check_sql);
            if (!$user_check_stmt) {
                throw new Exception("Error preparing user check statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($user_check_stmt, 'i', $user_id);
            mysqli_stmt_execute($user_check_stmt);
            mysqli_stmt_store_result($user_check_stmt);
            
            if (mysqli_stmt_num_rows($user_check_stmt) === 0) {
                throw new Exception("Error: User ID $user_id does not exist. Cannot submit fish.");
            }
            mysqli_stmt_close($user_check_stmt);
            
            // Prepare the statement
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . mysqli_error($conn));
            }
            
            // Bind parameters - we have 29 parameters (7 basic + 7 identifier + 8 sequence + 7 collection)
            if (!mysqli_stmt_bind_param($stmt, 'ssssssissssssssssssssissssdds', 
                $name, $scientific_name, $family, $environment, $size_category, $description, $user_id,
                $process_id, $sample_id, $museum_id, $collection_code, $field_id, $deposited_in, $specimen_linkout,
                $sequence_type, $sequence_id, $genbank_accession, $sequence_updated_at, $genome_type, $locus, $nucleotides_count, $dna_sequence,
                $collection_country, $collection_region, $collection_location, $collection_date, $collection_latitude, $collection_longitude, $collector_name)) {
                throw new Exception("Error binding parameters: " . mysqli_stmt_error($stmt));
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting fish data: " . mysqli_error($conn));
            }
            
            $fish_id = mysqli_insert_id($conn);
            
            // Process datasets
            if (isset($_POST['dataset_code']) && is_array($_POST['dataset_code'])) {
                $dataset_codes = $_POST['dataset_code'];
                $dataset_names = $_POST['dataset_name'] ?? [];
                $dataset_urls = $_POST['dataset_url'] ?? [];
                
                for ($i = 0; $i < count($dataset_codes); $i++) {
                    if (!empty($dataset_codes[$i]) && !empty($dataset_names[$i])) {
                        $code = sanitize($dataset_codes[$i]);
                        $name = sanitize($dataset_names[$i]);
                        $url = !empty($dataset_urls[$i]) ? sanitize($dataset_urls[$i]) : null;
                        
                        $dataset_sql = "INSERT INTO fish_datasets (fish_id, dataset_code, dataset_name, dataset_url) VALUES (?, ?, ?, ?)";
                        $dataset_stmt = mysqli_prepare($conn, $dataset_sql);
                        mysqli_stmt_bind_param($dataset_stmt, 'isss', $fish_id, $code, $name, $url);
                        mysqli_stmt_execute($dataset_stmt);
                    }
                }
            }
            
            // Handle categorized file uploads
            if ($has_files) {
                $upload_dir = UPLOAD_PATH;
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Function to process file upload
                function processFileUpload($file, $category, $fish_id, $upload_dir, $allowed_types, $max_size, $conn) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $file_name = $file['name'];
                        $file_size = $file['size'];
                        $file_type = $file['type'];
                        $file_tmp = $file['tmp_name'];
                        
                        // Validate file type
                        if (!in_array($file_type, $allowed_types)) {
                            throw new Exception("Invalid file type for {$file_name}. Only JPG, PNG, and GIF are allowed.");
                        }
                        
                        // Validate file size
                        if ($file_size > $max_size) {
                            throw new Exception("File {$file_name} is too large. Maximum size is 5MB.");
                        }
                        
                        // Generate unique file name
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_file_name = uniqid('fish_') . '.' . $ext;
                        $upload_path = $upload_dir . $new_file_name;
                        
                        // Move uploaded file to destination
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            // Insert image info into database with category
                            $is_primary = ($category == 'main') ? 1 : 0;
                            $img_sql = "INSERT INTO fish_images (fish_id, image_path, category, is_primary) VALUES (?, ?, ?, ?)";
                            $img_stmt = mysqli_prepare($conn, $img_sql);
                            mysqli_stmt_bind_param($img_stmt, 'issi', $fish_id, $upload_path, $category, $is_primary);
                            
                            if (!mysqli_stmt_execute($img_stmt)) {
                                throw new Exception("Error inserting image data: " . mysqli_error($conn));
                            }
                        } else {
                            throw new Exception("Failed to upload {$file_name}");
                        }
                    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception("Error uploading file: " . $file['error']);
                    }
                }
                
                // Upload main images
                if ($has_main) {
                    foreach ($_FILES['main_image']['tmp_name'] as $key => $tmp_name) {
                        $file = [
                            'name' => $_FILES['main_image']['name'][$key],
                            'type' => $_FILES['main_image']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['main_image']['error'][$key],
                            'size' => $_FILES['main_image']['size'][$key]
                        ];
                        processFileUpload($file, 'main', $fish_id, $upload_dir, $allowed_types, $max_size, $conn);
                    }
                }
                
                // Upload fish images
                if ($has_fish) {
                    foreach ($_FILES['fish_images']['tmp_name'] as $key => $tmp_name) {
                        $file = [
                            'name' => $_FILES['fish_images']['name'][$key],
                            'type' => $_FILES['fish_images']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['fish_images']['error'][$key],
                            'size' => $_FILES['fish_images']['size'][$key]
                        ];
                        processFileUpload($file, 'fish', $fish_id, $upload_dir, $allowed_types, $max_size, $conn);
                    }
                }
                
                // Upload skeleton images
                if ($has_skeleton) {
                    foreach ($_FILES['skeleton_images']['tmp_name'] as $key => $tmp_name) {
                        $file = [
                            'name' => $_FILES['skeleton_images']['name'][$key],
                            'type' => $_FILES['skeleton_images']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['skeleton_images']['error'][$key],
                            'size' => $_FILES['skeleton_images']['size'][$key]
                        ];
                        processFileUpload($file, 'skeleton', $fish_id, $upload_dir, $allowed_types, $max_size, $conn);
                    }
                }
                
                // Upload disease images
                if ($has_disease) {
                    foreach ($_FILES['disease_images']['tmp_name'] as $key => $tmp_name) {
                        $file = [
                            'name' => $_FILES['disease_images']['name'][$key],
                            'type' => $_FILES['disease_images']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['disease_images']['error'][$key],
                            'size' => $_FILES['disease_images']['size'][$key]
                        ];
                        processFileUpload($file, 'disease', $fish_id, $upload_dir, $allowed_types, $max_size, $conn);
                    }
                }
                
                // Upload map image
                if ($has_map) {
                    processFileUpload($_FILES['map_image'], 'map', $fish_id, $upload_dir, $allowed_types, $max_size, $conn);
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Redirect to user dashboard
            redirect('user/dashboard.php', 'Fish information submitted successfully! It will be reviewed by an admin.', 'success');
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $general_error = $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Submit Fish Form -->
<main class="main-content">
    <div class="container">
        <div class="form-container">
            <h1>Submit Fish Information</h1>
            <p class="form-intro">Share information about a fish species. Your submission will be reviewed by an administrator before being published.</p>
            
            <?php if (isset($general_error)): ?>
                <div class="alert alert-error"><?php echo $general_error; ?></div>
            <?php endif; ?>
            
            <form id="fishForm" method="post" action="submit_fish.php" enctype="multipart/form-data">
                <div class="form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Fish Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-input <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="field-error"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="scientific_name">Scientific Name</label>
                        <input type="text" id="scientific_name" name="scientific_name" class="form-input" 
                               value="<?php echo isset($scientific_name) ? htmlspecialchars($scientific_name) : ''; ?>">
                        <div class="form-hint">Format: <em>Genus species</em> (e.g., <em>Carassius auratus</em>)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="family">Family <span class="required">*</span></label>
                        <input type="text" id="family" name="family" class="form-input <?php echo isset($errors['family']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo isset($family) ? htmlspecialchars($family) : ''; ?>" required>
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
                            <option value="freshwater" <?php echo (isset($environment) && $environment == 'freshwater') ? 'selected' : ''; ?>>Freshwater</option>
                            <option value="saltwater" <?php echo (isset($environment) && $environment == 'saltwater') ? 'selected' : ''; ?>>Saltwater</option>
                            <option value="brackish" <?php echo (isset($environment) && $environment == 'brackish') ? 'selected' : ''; ?>>Brackish</option>
                        </select>
                        <?php if (isset($errors['environment'])): ?>
                            <div class="field-error"><?php echo $errors['environment']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="size_category">Size Category <span class="required">*</span></label>
                        <select id="size_category" name="size_category" class="form-select <?php echo isset($errors['size_category']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select size category</option>
                            <option value="small" <?php echo (isset($size_category) && $size_category == 'small') ? 'selected' : ''; ?>>Small (< 10cm)</option>
                            <option value="medium" <?php echo (isset($size_category) && $size_category == 'medium') ? 'selected' : ''; ?>>Medium (10-30cm)</option>
                            <option value="large" <?php echo (isset($size_category) && $size_category == 'large') ? 'selected' : ''; ?>>Large (> 30cm)</option>
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
                                rows="6" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
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
                                   value="<?php echo isset($process_id) ? htmlspecialchars($process_id) : ''; ?>">
                            <div class="form-hint">e.g., ABFJ238-07</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sample_id">Sample ID</label>
                            <input type="text" id="sample_id" name="sample_id" class="form-input" 
                                   value="<?php echo isset($sample_id) ? htmlspecialchars($sample_id) : ''; ?>">
                            <div class="form-hint">e.g., DAT6</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="museum_id">Museum ID</label>
                            <input type="text" id="museum_id" name="museum_id" class="form-input" 
                                   value="<?php echo isset($museum_id) ? htmlspecialchars($museum_id) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_code">Collection Code</label>
                            <input type="text" id="collection_code" name="collection_code" class="form-input" 
                                   value="<?php echo isset($collection_code) ? htmlspecialchars($collection_code) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="field_id">Field ID</label>
                            <input type="text" id="field_id" name="field_id" class="form-input" 
                                   value="<?php echo isset($field_id) ? htmlspecialchars($field_id) : ''; ?>">
                            <div class="form-hint">e.g., DAT6</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="deposited_in">Deposited In</label>
                            <input type="text" id="deposited_in" name="deposited_in" class="form-input" 
                                   value="<?php echo isset($deposited_in) ? htmlspecialchars($deposited_in) : ''; ?>">
                            <div class="form-hint">e.g., National Research Institute of Fisheries Science, Japan</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="specimen_linkout">Specimen Linkout (URL)</label>
                        <input type="url" id="specimen_linkout" name="specimen_linkout" class="form-input" 
                               value="<?php echo isset($specimen_linkout) ? htmlspecialchars($specimen_linkout) : ''; ?>">
                        <div class="form-hint">External link to specimen information, if available</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Associated Datasets</label>
                        <div class="dataset-container">
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
                               value="<?php echo isset($sequence_type) ? htmlspecialchars($sequence_type) : 'COI-5P'; ?>">
                        <div class="form-hint">e.g., COI-5P</div>
                    </div>
                    
                    <div class="identifiers-grid">
                        <div class="form-group">
                            <label for="sequence_id">Sequence ID</label>
                            <input type="text" id="sequence_id" name="sequence_id" class="form-input" 
                                   value="<?php echo isset($sequence_id) ? htmlspecialchars($sequence_id) : ''; ?>">
                            <div class="form-hint">e.g., GBMNB4650-20.COI-5P</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="genbank_accession">GenBank Accession</label>
                            <input type="text" id="genbank_accession" name="genbank_accession" class="form-input" 
                                   value="<?php echo isset($genbank_accession) ? htmlspecialchars($genbank_accession) : ''; ?>">
                            <div class="form-hint">e.g., MH377856</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sequence_updated_at">Last Updated</label>
                            <input type="date" id="sequence_updated_at" name="sequence_updated_at" class="form-input" 
                                   value="<?php echo isset($sequence_updated_at) ? date('Y-m-d', strtotime($sequence_updated_at)) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="genome_type">Genome</label>
                            <input type="text" id="genome_type" name="genome_type" class="form-input" 
                                   value="<?php echo isset($genome_type) ? htmlspecialchars($genome_type) : ''; ?>">
                            <div class="form-hint">e.g., Mitochondrial</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="locus">Locus</label>
                            <input type="text" id="locus" name="locus" class="form-input" 
                                   value="<?php echo isset($locus) ? htmlspecialchars($locus) : ''; ?>">
                            <div class="form-hint">e.g., Cytochrome Oxidase Subunit 1 5' Region</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nucleotides_count">Nucleotides Count</label>
                            <input type="number" id="nucleotides_count" name="nucleotides_count" class="form-input" 
                                   value="<?php echo isset($nucleotides_count) ? htmlspecialchars($nucleotides_count) : ''; ?>">
                            <div class="form-hint">e.g., 568</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dna_sequence">DNA Sequence</label>
                        <textarea id="dna_sequence" name="dna_sequence" class="form-textarea" rows="6"><?php echo isset($dna_sequence) ? htmlspecialchars($dna_sequence) : ''; ?></textarea>
                        <div class="form-hint">Enter the DNA sequence (A, T, G, C bases)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Collection Information</h2>
                    <p class="form-intro">Details about where and when the specimen was collected</p>
                    
                    <div class="identifiers-grid">
                        <div class="form-group">
                            <label for="collection_country">Country/Ocean</label>
                            <input type="text" id="collection_country" name="collection_country" class="form-input" 
                                   value="<?php echo isset($collection_country) ? htmlspecialchars($collection_country) : ''; ?>">
                            <div class="form-hint">e.g., Japan, Pacific Ocean</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_region">Province/Region</label>
                            <input type="text" id="collection_region" name="collection_region" class="form-input" 
                                   value="<?php echo isset($collection_region) ? htmlspecialchars($collection_region) : ''; ?>">
                            <div class="form-hint">e.g., Kanagawa, Yokohama</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_location">Specific Location</label>
                            <input type="text" id="collection_location" name="collection_location" class="form-input" 
                                   value="<?php echo isset($collection_location) ? htmlspecialchars($collection_location) : ''; ?>">
                            <div class="form-hint">e.g., Yokosuka, Arasaki</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_date">Collection Date</label>
                            <input type="date" id="collection_date" name="collection_date" class="form-input" 
                                   value="<?php echo isset($collection_date) ? date('Y-m-d', strtotime($collection_date)) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_latitude">Latitude</label>
                            <input type="number" id="collection_latitude" name="collection_latitude" class="form-input" 
                                   step="0.00000001" min="-90" max="90"
                                   value="<?php echo isset($collection_latitude) ? htmlspecialchars($collection_latitude) : ''; ?>">
                            <div class="form-hint">e.g., 35.2833333</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_longitude">Longitude</label>
                            <input type="number" id="collection_longitude" name="collection_longitude" class="form-input" 
                                   step="0.00000001" min="-180" max="180"
                                   value="<?php echo isset($collection_longitude) ? htmlspecialchars($collection_longitude) : ''; ?>">
                            <div class="form-hint">e.g., 139.6666667</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collector_name">Collector Name</label>
                            <input type="text" id="collector_name" name="collector_name" class="form-input" 
                                   value="<?php echo isset($collector_name) ? htmlspecialchars($collector_name) : ''; ?>">
                            <div class="form-hint">e.g., Satoshi Katayama</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Images</h2>
                    <p class="form-intro">Upload images in different categories to better organize your fish photos</p>
                    
                    <div class="image-upload-categories">
                        <div class="form-group">
                            <label for="main_image">Main Images <span class="required">*</span></label>
                            <input type="file" id="main_image" name="main_image[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Primary images that will be displayed as the main photos (max 5MB each)</div>
                            <div id="main-preview" class="image-preview-multiple"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="fish_images">Fish Photos</label>
                            <input type="file" id="fish_images" name="fish_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">General photos of the fish (max 5MB each)</div>
                            <div id="fish-preview" class="image-preview-multiple"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="skeleton_images">Skeleton/Bone Structure</label>
                            <input type="file" id="skeleton_images" name="skeleton_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Photos showing skeletal structure, bones, or anatomical details</div>
                            <div id="skeleton-preview" class="image-preview-multiple"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="disease_images">Disease/Pathology</label>
                            <input type="file" id="disease_images" name="disease_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Photos showing diseases, parasites, or pathological conditions</div>
                            <div id="disease-preview" class="image-preview-multiple"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="map_image">Distribution Map</label>
                            <input type="file" id="map_image" name="map_image" class="form-file" accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Map showing geographic distribution or habitat location</div>
                            <div id="map-preview" class="image-preview-single"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Image preview functionality for categorized uploads
    function setupImagePreview(inputId, previewId, isMultiple = false) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        
        input.addEventListener('change', function(e) {
            preview.innerHTML = ''; // Clear previous previews
            
            if (this.files && this.files.length > 0) {
                Array.from(this.files).forEach((file, index) => {
                    if (!file.type.match('image.*')) {
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview_item = document.createElement('div');
                        preview_item.className = 'image-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.title = file.name;
                        
                        const caption = document.createElement('div');
                        caption.className = 'image-caption';
                        caption.textContent = isMultiple ? `Image ${index + 1}` : 'Preview';
                        
                        preview_item.appendChild(img);
                        preview_item.appendChild(caption);
                        preview.appendChild(preview_item);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        });
    }
    
    // Setup previews for all image categories
    setupImagePreview('main_image', 'main-preview', true);
    setupImagePreview('fish_images', 'fish-preview', true);
    setupImagePreview('skeleton_images', 'skeleton-preview', true);
    setupImagePreview('disease_images', 'disease-preview', true);
    setupImagePreview('map_image', 'map-preview', false);
    
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