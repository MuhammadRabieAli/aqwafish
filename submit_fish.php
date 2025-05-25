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
    
    // Check if files were uploaded
    $has_files = isset($_FILES['fish_images']) && !empty($_FILES['fish_images']['name'][0]);
    
    // If no errors, insert fish data
    if (empty($errors)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert fish data
            $sql = "INSERT INTO fish (name, scientific_name, family, environment, size_category, description, submitted_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $scientific_name, $family, $environment, $size_category, $description, $user_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting fish data: " . mysqli_error($conn));
            }
            
            $fish_id = mysqli_insert_id($conn);
            
            // Handle file uploads
            if ($has_files) {
                $upload_dir = UPLOAD_PATH;
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Loop through each uploaded file
                foreach ($_FILES['fish_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['fish_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['fish_images']['name'][$key];
                        $file_size = $_FILES['fish_images']['size'][$key];
                        $file_type = $_FILES['fish_images']['type'][$key];
                        $file_tmp = $_FILES['fish_images']['tmp_name'][$key];
                        
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
                            // Insert image info into database
                            $is_primary = ($key == 0) ? 1 : 0; // First image is primary
                            $img_sql = "INSERT INTO fish_images (fish_id, image_path, is_primary) VALUES (?, ?, ?)";
                            $img_stmt = mysqli_prepare($conn, $img_sql);
                            mysqli_stmt_bind_param($img_stmt, 'isi', $fish_id, $upload_path, $is_primary);
                            
                            if (!mysqli_stmt_execute($img_stmt)) {
                                throw new Exception("Error inserting image data: " . mysqli_error($conn));
                            }
                        } else {
                            throw new Exception("Failed to upload {$file_name}");
                        }
                    } elseif ($_FILES['fish_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception("Error uploading file: " . $_FILES['fish_images']['error'][$key]);
                    }
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
                    <h2>Images</h2>
                    
                    <div class="form-group">
                        <label for="fish_images">Upload Images</label>
                        <input type="file" id="fish_images" name="fish_images[]" class="form-file" multiple accept="image/jpeg,image/png,image/gif">
                        <div class="form-hint">You can upload multiple images (max 5MB each). The first image will be used as the primary image.</div>
                        <div id="image-preview" class="image-preview">
                            <div id="preview-container" class="preview-container"></div>
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
                    preview.className = 'image-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.title = file.name;
                    
                    const caption = document.createElement('div');
                    caption.className = 'image-caption';
                    caption.textContent = index === 0 ? 'Primary' : `Image ${index + 1}`;
                    
                    preview.appendChild(img);
                    preview.appendChild(caption);
                    previewContainer.appendChild(preview);
                };
                
                reader.readAsDataURL(file);
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 