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

// Get fish images grouped by category
$images_sql = "SELECT * FROM fish_images WHERE fish_id = ? ORDER BY 
              CASE category 
                  WHEN 'main' THEN 1
                  WHEN 'fish' THEN 2
                  WHEN 'skeleton' THEN 3
                  WHEN 'disease' THEN 4
                  WHEN 'map' THEN 5
                  ELSE 6
              END, is_primary DESC";
$images_stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($images_stmt, 'i', $fish_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

// Group existing images by category
$existing_images = [
    'main' => [],
    'fish' => [],
    'skeleton' => [],
    'disease' => [],
    'map' => []
];

while ($image = mysqli_fetch_assoc($images_result)) {
    $category = $image['category'] === 'regular' ? 'fish' : $image['category'];
    $existing_images[$category][] = $image;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First, verify that the user exists
    $user_check_sql = "SELECT id FROM users WHERE id = ?";
    $user_check_stmt = mysqli_prepare($conn, $user_check_sql);
    $general_error = null;
    
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
        
        // Process DNA sequence directly without using the sanitize function
        $raw_dna = $_POST['dna_sequence'] ?? '';
        if (!empty($raw_dna)) {
            // Strip any non-ACGT characters and convert to uppercase
            $dna_sequence = strtoupper(preg_replace('/[^ACGTacgt]/', '', $raw_dna));
            
            // Also update nucleotides count if we have a valid sequence
            if (strlen($dna_sequence) > 0) {
                $nucleotides_count = strlen($dna_sequence);
            }
        } else {
            $dna_sequence = '';
        }
        
        // Get collection fields
        $collection_country = sanitize($_POST['collection_country'] ?? '');
        $collection_region = sanitize($_POST['collection_region'] ?? '');
        $collection_location = sanitize($_POST['collection_location'] ?? '');
        $collection_date = !empty($_POST['collection_date']) ? $_POST['collection_date'] : null;
        $collection_latitude = !empty($_POST['collection_latitude']) ? floatval($_POST['collection_latitude']) : null;
        $collection_longitude = !empty($_POST['collection_longitude']) ? floatval($_POST['collection_longitude']) : null;
        $collector_name = sanitize($_POST['collector_name'] ?? '');
        
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
            // Begin transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Use direct SQL for all updates, like in update_fish_dna.php
                $update_sql = "UPDATE fish SET 
                              name = '" . mysqli_real_escape_string($conn, $name) . "',
                              scientific_name = '" . mysqli_real_escape_string($conn, $scientific_name) . "',
                              family = '" . mysqli_real_escape_string($conn, $family) . "',
                              environment = '" . mysqli_real_escape_string($conn, $environment) . "',
                              size_category = '" . mysqli_real_escape_string($conn, $size_category) . "',
                              description = '" . mysqli_real_escape_string($conn, $description) . "',
                              process_id = '" . mysqli_real_escape_string($conn, $process_id) . "',
                              sample_id = '" . mysqli_real_escape_string($conn, $sample_id) . "',
                              museum_id = '" . mysqli_real_escape_string($conn, $museum_id) . "',
                              collection_code = '" . mysqli_real_escape_string($conn, $collection_code) . "',
                              field_id = '" . mysqli_real_escape_string($conn, $field_id) . "',
                              deposited_in = '" . mysqli_real_escape_string($conn, $deposited_in) . "',
                              specimen_linkout = '" . mysqli_real_escape_string($conn, $specimen_linkout) . "',
                              sequence_type = '" . mysqli_real_escape_string($conn, $sequence_type) . "',
                              sequence_id = '" . mysqli_real_escape_string($conn, $sequence_id) . "',
                              genbank_accession = '" . mysqli_real_escape_string($conn, $genbank_accession) . "',";
                
                // Handle date fields properly
                if (!empty($sequence_updated_at)) {
                    $update_sql .= " sequence_updated_at = '" . mysqli_real_escape_string($conn, $sequence_updated_at) . "',";
                } else {
                    $update_sql .= " sequence_updated_at = NULL,";
                }
                
                $update_sql .= " genome_type = '" . mysqli_real_escape_string($conn, $genome_type) . "',
                              locus = '" . mysqli_real_escape_string($conn, $locus) . "',";
                
                // Handle numeric fields properly
                if ($nucleotides_count !== null) {
                    $update_sql .= " nucleotides_count = " . (int)$nucleotides_count . ",";
                } else {
                    $update_sql .= " nucleotides_count = NULL,";
                }
                
                // Add DNA sequence field directly in the SQL
                $update_sql .= " dna_sequence = '" . mysqli_real_escape_string($conn, $dna_sequence) . "',";
                              
                $update_sql .= " collection_country = '" . mysqli_real_escape_string($conn, $collection_country) . "',
                              collection_region = '" . mysqli_real_escape_string($conn, $collection_region) . "',
                              collection_location = '" . mysqli_real_escape_string($conn, $collection_location) . "',";
                
                // Handle date fields properly
                if (!empty($collection_date)) {
                    $update_sql .= " collection_date = '" . mysqli_real_escape_string($conn, $collection_date) . "',";
                } else {
                    $update_sql .= " collection_date = NULL,";
                }
                
                // Handle numeric fields properly
                if ($collection_latitude !== null) {
                    $update_sql .= " collection_latitude = " . (float)$collection_latitude . ",";
                } else {
                    $update_sql .= " collection_latitude = NULL,";
                }
                
                if ($collection_longitude !== null) {
                    $update_sql .= " collection_longitude = " . (float)$collection_longitude . ",";
                } else {
                    $update_sql .= " collection_longitude = NULL,";
                }
                
                $update_sql .= " collector_name = '" . mysqli_real_escape_string($conn, $collector_name) . "',
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = " . (int)$fish_id;
                
                if (!mysqli_query($conn, $update_sql)) {
                    throw new Exception("Error updating fish data: " . mysqli_error($conn));
                }
                
                // Handle datasets
                // First, delete all existing datasets for this fish
                $delete_datasets_sql = "DELETE FROM fish_datasets WHERE fish_id = " . (int)$fish_id;
                if (!mysqli_query($conn, $delete_datasets_sql)) {
                    throw new Exception("Error deleting datasets: " . mysqli_error($conn));
                }
                
                // Now insert the updated datasets
                if (isset($_POST['dataset_code']) && is_array($_POST['dataset_code'])) {
                    $dataset_codes = $_POST['dataset_code'];
                    $dataset_names = $_POST['dataset_name'] ?? [];
                    $dataset_urls = $_POST['dataset_url'] ?? [];
                    
                    for ($i = 0; $i < count($dataset_codes); $i++) {
                        if (!empty($dataset_codes[$i]) && !empty($dataset_names[$i])) {
                            $code = mysqli_real_escape_string($conn, sanitize($dataset_codes[$i]));
                            $name = mysqli_real_escape_string($conn, sanitize($dataset_names[$i]));
                            $url = !empty($dataset_urls[$i]) ? "'" . mysqli_real_escape_string($conn, sanitize($dataset_urls[$i])) . "'" : "NULL";
                            
                            $insert_dataset_sql = "INSERT INTO fish_datasets (fish_id, dataset_code, dataset_name, dataset_url) 
                                                VALUES (" . (int)$fish_id . ", '" . $code . "', '" . $name . "', " . $url . ")";
                            
                            if (!mysqli_query($conn, $insert_dataset_sql)) {
                                throw new Exception("Error inserting dataset: " . mysqli_error($conn));
                            }
                        }
                    }
                }
                
                // Handle categorized image uploads
                $upload_dir = 'uploads/';
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Function to process file upload
                function processFileUploadEdit($file, $category, $fish_id, $upload_dir, $allowed_types, $max_size, $conn, $replace_existing = false) {
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
                        
                        // If replacing existing images of this category, delete them first
                        if ($replace_existing || $category === 'main' || $category === 'map') {
                            $delete_sql = "SELECT image_path FROM fish_images WHERE fish_id = ? AND category = ?";
                            $delete_stmt = mysqli_prepare($conn, $delete_sql);
                            mysqli_stmt_bind_param($delete_stmt, 'is', $fish_id, $category);
                            mysqli_stmt_execute($delete_stmt);
                            $delete_result = mysqli_stmt_get_result($delete_stmt);
                            
                            while ($old_image = mysqli_fetch_assoc($delete_result)) {
                                if (file_exists($old_image['image_path'])) {
                                    unlink($old_image['image_path']);
                                }
                            }
                            
                            $remove_sql = "DELETE FROM fish_images WHERE fish_id = ? AND category = ?";
                            $remove_stmt = mysqli_prepare($conn, $remove_sql);
                            mysqli_stmt_bind_param($remove_stmt, 'is', $fish_id, $category);
                            mysqli_stmt_execute($remove_stmt);
                        }
                        
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
                
                // Check if files were uploaded in any category
                $has_main = isset($_FILES['main_image']) && !empty($_FILES['main_image']['name'][0]);
                $has_fish = isset($_FILES['fish_images']) && !empty($_FILES['fish_images']['name'][0]);
                $has_skeleton = isset($_FILES['skeleton_images']) && !empty($_FILES['skeleton_images']['name'][0]);
                $has_disease = isset($_FILES['disease_images']) && !empty($_FILES['disease_images']['name'][0]);
                $has_map = isset($_FILES['map_image']) && !empty($_FILES['map_image']['name']);
                
                $has_files = $has_main || $has_fish || $has_skeleton || $has_disease || $has_map;
                
                if ($has_files) {
                    // Upload main images (adds to existing)
                    if ($has_main) {
                        foreach ($_FILES['main_image']['tmp_name'] as $key => $tmp_name) {
                            $file = [
                                'name' => $_FILES['main_image']['name'][$key],
                                'type' => $_FILES['main_image']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['main_image']['error'][$key],
                                'size' => $_FILES['main_image']['size'][$key]
                            ];
                            processFileUploadEdit($file, 'main', $fish_id, $upload_dir, $allowed_types, $max_size, $conn, false);
                        }
                    }
                    
                    // Upload fish images (adds to existing)
                    if ($has_fish) {
                        foreach ($_FILES['fish_images']['tmp_name'] as $key => $tmp_name) {
                            $file = [
                                'name' => $_FILES['fish_images']['name'][$key],
                                'type' => $_FILES['fish_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['fish_images']['error'][$key],
                                'size' => $_FILES['fish_images']['size'][$key]
                            ];
                            processFileUploadEdit($file, 'fish', $fish_id, $upload_dir, $allowed_types, $max_size, $conn, false);
                        }
                    }
                    
                    // Upload skeleton images (adds to existing)
                    if ($has_skeleton) {
                        foreach ($_FILES['skeleton_images']['tmp_name'] as $key => $tmp_name) {
                            $file = [
                                'name' => $_FILES['skeleton_images']['name'][$key],
                                'type' => $_FILES['skeleton_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['skeleton_images']['error'][$key],
                                'size' => $_FILES['skeleton_images']['size'][$key]
                            ];
                            processFileUploadEdit($file, 'skeleton', $fish_id, $upload_dir, $allowed_types, $max_size, $conn, false);
                        }
                    }
                    
                    // Upload disease images (adds to existing)
                    if ($has_disease) {
                        foreach ($_FILES['disease_images']['tmp_name'] as $key => $tmp_name) {
                            $file = [
                                'name' => $_FILES['disease_images']['name'][$key],
                                'type' => $_FILES['disease_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['disease_images']['error'][$key],
                                'size' => $_FILES['disease_images']['size'][$key]
                            ];
                            processFileUploadEdit($file, 'disease', $fish_id, $upload_dir, $allowed_types, $max_size, $conn, false);
                        }
                    }
                    
                    // Upload map image (replaces any existing)
                    if ($has_map) {
                        $file = [
                            'name' => $_FILES['map_image']['name'],
                            'type' => $_FILES['map_image']['type'],
                            'tmp_name' => $_FILES['map_image']['tmp_name'],
                            'error' => $_FILES['map_image']['error'],
                            'size' => $_FILES['map_image']['size']
                        ];
                        processFileUploadEdit($file, 'map', $fish_id, $upload_dir, $allowed_types, $max_size, $conn, true);
                    }
                }
                
                // Commit the transaction
                mysqli_commit($conn);
                
                $_SESSION['success_message'] = "Fish information updated successfully!";
                header("Location: fish_details.php?id=" . $fish_id);
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                
                $general_error = "Error: " . $e->getMessage();
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
                        <textarea id="dna_sequence" name="dna_sequence" class="form-textarea" rows="6"><?php echo $fish['dna_sequence'] ?? ''; ?></textarea>
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
                                   value="<?php echo htmlspecialchars($fish['collection_country'] ?? ''); ?>">
                            <div class="form-hint">e.g., Japan, Pacific Ocean</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_region">Province/Region</label>
                            <input type="text" id="collection_region" name="collection_region" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['collection_region'] ?? ''); ?>">
                            <div class="form-hint">e.g., Kanagawa, Yokohama</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_location">Specific Location</label>
                            <input type="text" id="collection_location" name="collection_location" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['collection_location'] ?? ''); ?>">
                            <div class="form-hint">e.g., Yokosuka, Arasaki</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_date">Collection Date</label>
                            <input type="date" id="collection_date" name="collection_date" class="form-input" 
                                   value="<?php echo !empty($fish['collection_date']) ? date('Y-m-d', strtotime($fish['collection_date'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_latitude">Latitude</label>
                            <input type="number" id="collection_latitude" name="collection_latitude" class="form-input" 
                                   step="0.00000001" min="-90" max="90"
                                   value="<?php echo htmlspecialchars($fish['collection_latitude'] ?? ''); ?>">
                            <div class="form-hint">e.g., 35.2833333</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collection_longitude">Longitude</label>
                            <input type="number" id="collection_longitude" name="collection_longitude" class="form-input" 
                                   step="0.00000001" min="-180" max="180"
                                   value="<?php echo htmlspecialchars($fish['collection_longitude'] ?? ''); ?>">
                            <div class="form-hint">e.g., 139.6666667</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="collector_name">Collector Name</label>
                            <input type="text" id="collector_name" name="collector_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($fish['collector_name'] ?? ''); ?>">
                            <div class="form-hint">e.g., Satoshi Katayama</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Images</h2>
                    <p class="form-intro">Update images in different categories. Main and Map images will replace existing ones, while others will be added.</p>
                    
                    <div class="image-upload-categories">
                        <div class="form-group">
                            <label for="main_image">Main Images</label>
                            <input type="file" id="main_image" name="main_image[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Add more main images (will be added to existing ones)</div>
                            <div id="main-preview" class="image-preview-multiple"></div>
                            <?php if (!empty($existing_images['main'])): ?>
                                <div class="current-images">
                                    <h5>Current Main Images:</h5>
                                    <div class="existing-image-grid">
                                        <?php foreach ($existing_images['main'] as $image): ?>
                                            <div class="image-container">
                                                <img src="<?php echo $image['image_path']; ?>" alt="Current Main Image" class="existing-image">
                                                <a href="delete_image.php?id=<?php echo $image['id']; ?>&fish_id=<?php echo $fish_id; ?>" class="delete-image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="fish_images">Fish Photos</label>
                            <input type="file" id="fish_images" name="fish_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Add more fish photos (will be added to existing ones)</div>
                            <div id="fish-preview" class="image-preview-multiple"></div>
                            <?php if (!empty($existing_images['fish'])): ?>
                                <div class="current-images">
                                    <h5>Current Fish Images:</h5>
                                    <div class="existing-image-grid">
                                        <?php foreach ($existing_images['fish'] as $image): ?>
                                            <div class="image-container">
                                                <img src="<?php echo $image['image_path']; ?>" alt="Current Fish Image" class="existing-image">
                                                <a href="delete_image.php?id=<?php echo $image['id']; ?>&fish_id=<?php echo $fish_id; ?>" class="delete-image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="skeleton_images">Skeleton/Bone Structure</label>
                            <input type="file" id="skeleton_images" name="skeleton_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Add skeleton or bone structure images</div>
                            <div id="skeleton-preview" class="image-preview-multiple"></div>
                            <?php if (!empty($existing_images['skeleton'])): ?>
                                <div class="current-images">
                                    <h5>Current Skeleton Images:</h5>
                                    <div class="existing-image-grid">
                                        <?php foreach ($existing_images['skeleton'] as $image): ?>
                                            <div class="image-container">
                                                <img src="<?php echo $image['image_path']; ?>" alt="Current Skeleton Image" class="existing-image">
                                                <a href="delete_image.php?id=<?php echo $image['id']; ?>&fish_id=<?php echo $fish_id; ?>" class="delete-image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="disease_images">Disease/Pathology</label>
                            <input type="file" id="disease_images" name="disease_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Add disease or pathology images</div>
                            <div id="disease-preview" class="image-preview-multiple"></div>
                            <?php if (!empty($existing_images['disease'])): ?>
                                <div class="current-images">
                                    <h5>Current Disease Images:</h5>
                                    <div class="existing-image-grid">
                                        <?php foreach ($existing_images['disease'] as $image): ?>
                                            <div class="image-container">
                                                <img src="<?php echo $image['image_path']; ?>" alt="Current Disease Image" class="existing-image">
                                                <a href="delete_image.php?id=<?php echo $image['id']; ?>&fish_id=<?php echo $fish_id; ?>" class="delete-image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="map_image">Distribution Map</label>
                            <input type="file" id="map_image" name="map_image" class="form-file" accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Upload a new distribution map (will replace existing)</div>
                            <div id="map-preview" class="image-preview-single"></div>
                            <?php if (!empty($existing_images['map'])): ?>
                                <div class="current-images">
                                    <h5>Current Map Image:</h5>
                                    <div class="existing-image-grid">
                                        <?php foreach ($existing_images['map'] as $image): ?>
                                            <div class="image-container">
                                                <img src="<?php echo $image['image_path']; ?>" alt="Current Map Image" class="existing-image">
                                                <a href="delete_image.php?id=<?php echo $image['id']; ?>&fish_id=<?php echo $fish_id; ?>" class="delete-image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
    // Image preview functionality for categorized uploads
    function setupImagePreviewEdit(inputId, previewId, isMultiple = false) {
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
                        caption.textContent = isMultiple ? `New Image ${index + 1}` : 'New Image';
                        
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
    setupImagePreviewEdit('main_image', 'main-preview', true);
    setupImagePreviewEdit('fish_images', 'fish-preview', true);
    setupImagePreviewEdit('skeleton_images', 'skeleton-preview', true);
    setupImagePreviewEdit('disease_images', 'disease-preview', true);
    setupImagePreviewEdit('map_image', 'map-preview', false);
    
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