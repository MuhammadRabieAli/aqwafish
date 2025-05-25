    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-fish"></i> AquaBase</h3>
                    <p>Your comprehensive fish information database</p>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-link"></i> Quick Links</h4>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo $base_path; ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                    <?php elseif (isLoggedIn()): ?>
                        <a href="<?php echo $base_path; ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> User Dashboard</a>
                    <?php endif; ?>
                </div>
                <div class="footer-section">
                    <h4><i class="fas fa-book"></i> Resources</h4>
                    <a href="#"><i class="fas fa-fish"></i> Fish Guide</a>
                    <a href="#"><i class="fas fa-leaf"></i> Conservation</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AquaBase. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo $base_path; ?>/scripts/theme.js"></script>
    <script src="<?php echo $base_path; ?>/scripts/navigation.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="<?php echo strpos($js, '/') === 0 ? $base_path . $js : $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 