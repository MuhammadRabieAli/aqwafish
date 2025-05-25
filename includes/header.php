<?php
require_once dirname(__DIR__) . '/config.php';

// Determine base path for assets
$base_path = '';
$current_dir = dirname($_SERVER['PHP_SELF']);
if (strpos($current_dir, '/admin') !== false || strpos($current_dir, '/user') !== false) {
    $base_path = '..';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>AquaBase</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/styles/main.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo strpos($css, '/') === 0 ? $base_path . $css : $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <!-- Font Awesome for modern icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo $base_path ? $base_path : '.'; ?>" style="text-decoration: none; color: inherit;">
                    <h1>🐠 AquaBase</h1>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo $base_path; ?>/index.php" class="nav-link <?php echo $page == 'home' ? 'active' : ''; ?>">Home</a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo $base_path; ?>/admin/dashboard.php" class="nav-link <?php echo $page == 'admin' ? 'active' : ''; ?>">Admin</a>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>/user/dashboard.php" class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                    <?php endif; ?>
                    <a href="<?php echo $base_path; ?>/submit_fish.php" class="nav-link <?php echo $page == 'submit' ? 'active' : ''; ?>">Submit Fish</a>
                    <a href="<?php echo $base_path; ?>/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>/login.php" class="nav-link <?php echo $page == 'login' ? 'active' : ''; ?>">Login</a>
                    <a href="<?php echo $base_path; ?>/register.php" class="nav-link <?php echo $page == 'register' ? 'active' : ''; ?>">Register</a>
                <?php endif; ?>
                
                <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
            </div>
            <div class="nav-hamburger" id="navHamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <?php displayMessage(); ?> 