<?php
require_once 'config.php';

$page = 'register';
$page_title = 'Register';
$extra_css = ['styles/auth.css'];

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['fullName']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $terms = isset($_POST['terms']) ? true : false;
    
    $errors = [];
    
    // Validate input
    if (empty($name)) {
        $errors['fullName'] = "Name is required";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $errors['email'] = "Email is already registered";
        }
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    }
    
    if ($password != $confirm_password) {
        $errors['confirmPassword'] = "Passwords do not match";
    }
    
    if (!$terms) {
        $errors['terms'] = "You must agree to the Terms of Service and Privacy Policy";
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = 0;
            
            // Redirect to user dashboard
            redirect('user/dashboard.php', 'Account created successfully!', 'success');
        } else {
            $general_error = "An error occurred during registration. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<!-- Register Content -->
<main class="auth-main">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join the AquaBase community</p>
            </div>
            
            <?php if (isset($general_error)): ?>
                <div class="alert alert-error"><?php echo $general_error; ?></div>
            <?php endif; ?>
            
            <form class="auth-form" id="registerForm" method="post" action="register.php">
                <div class="form-group">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" id="fullName" name="fullName" class="form-input <?php echo isset($errors['fullName']) ? 'is-invalid' : ''; ?>" 
                           placeholder="Enter your full name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    <?php if (isset($errors['fullName'])): ?>
                        <div class="field-error" id="fullNameError"><?php echo $errors['fullName']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                           placeholder="Enter your email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error" id="emailError"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                               placeholder="Create a password" required>
                        <button type="button" class="password-toggle" id="passwordToggle">👁️</button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error" id="passwordError"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input <?php echo isset($errors['confirmPassword']) ? 'is-invalid' : ''; ?>" 
                           placeholder="Confirm your password" required>
                    <?php if (isset($errors['confirmPassword'])): ?>
                        <div class="field-error" id="confirmPasswordError"><?php echo $errors['confirmPassword']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-wrapper <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>">
                        <input type="checkbox" id="terms" name="terms" <?php echo isset($terms) && $terms ? 'checked' : ''; ?> required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#" class="auth-link">Terms of Service</a> and <a href="#" class="auth-link">Privacy Policy</a>
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <div class="field-error"><?php echo $errors['terms']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary auth-submit">Create Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Sign in</a></p>
            </div>
        </div>
    </div>
</main>

<script>
    // Toggle password visibility
    document.getElementById('passwordToggle').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
    });
    
    // Password strength meter
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Calculate strength
        if (password.length >= 8) strength += 25;
        if (password.match(/[A-Z]/)) strength += 25;
        if (password.match(/[0-9]/)) strength += 25;
        if (password.match(/[^A-Za-z0-9]/)) strength += 25;
        
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        // Update UI
        strengthFill.style.width = strength + '%';
        
        if (strength < 25) {
            strengthFill.style.backgroundColor = '#ff4d4d';
            strengthText.textContent = 'Very Weak';
        } else if (strength < 50) {
            strengthFill.style.backgroundColor = '#ffa64d';
            strengthText.textContent = 'Weak';
        } else if (strength < 75) {
            strengthFill.style.backgroundColor = '#ffff4d';
            strengthText.textContent = 'Medium';
        } else {
            strengthFill.style.backgroundColor = '#4CAF50';
            strengthText.textContent = 'Strong';
        }
    });
    
    // Password confirmation check
    document.getElementById('confirmPassword').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmation = this.value;
        const confirmError = document.getElementById('confirmPasswordError');
        
        if (password !== confirmation) {
            if (!confirmError) {
                const error = document.createElement('div');
                error.id = 'confirmPasswordError';
                error.className = 'field-error';
                error.textContent = 'Passwords do not match';
                this.parentNode.appendChild(error);
            }
            this.classList.add('is-invalid');
        } else {
            if (confirmError) {
                confirmError.remove();
            }
            this.classList.remove('is-invalid');
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 