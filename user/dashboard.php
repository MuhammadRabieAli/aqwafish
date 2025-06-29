<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php', 'Please login to access your dashboard', 'error');
}

// If user is admin, redirect to admin dashboard
if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$page = 'dashboard';
$page_title = 'My Dashboard';
$extra_css = ['/styles/dashboard.css'];

$user_id = $_SESSION['user_id'];

// Get user's fish submissions
$sql = "SELECT f.*, COUNT(fi.id) AS image_count, 
        (SELECT image_path FROM fish_images WHERE fish_id = f.id 
         ORDER BY 
         CASE category 
             WHEN 'main' THEN 1 
             WHEN 'fish' THEN 2 
             WHEN 'regular' THEN 2 
             WHEN 'skeleton' THEN 3 
             WHEN 'disease' THEN 4 
             WHEN 'map' THEN 5 
             ELSE 6 
         END, 
         is_primary DESC, 
         id ASC 
         LIMIT 1) AS image_path
        FROM fish f 
        LEFT JOIN fish_images fi ON f.id = fi.fish_id
        WHERE f.submitted_by = ?
        GROUP BY f.id
        ORDER BY f.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get counts by status
$count_sql = "SELECT status, COUNT(*) as count FROM fish WHERE submitted_by = ? GROUP BY status";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, 'i', $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);

$counts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];

while ($row = mysqli_fetch_assoc($count_result)) {
    $counts[$row['status']] = $row['count'];
    $counts['total'] += $row['count'];
}

include '../includes/header.php';
?>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="container">
        <h1><i class="fas fa-tachometer-alt"></i> My Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
</div>

<!-- Dashboard Content -->
<main class="dashboard-content">
    <div class="container">
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-data"><?php echo $counts['total']; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-data"><?php echo $counts['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-data"><?php echo $counts['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-data"><?php echo $counts['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <!-- Actions Section -->
        <div class="actions-section">
            <a href="../submit_fish.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Submit New Fish</a>
        </div>
        
        <!-- Submissions Table -->
        <div class="table-section">
            <h2><i class="fas fa-list"></i> My Submissions</h2>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Fish Name</th>
                                <th>Family</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fish = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="table-image">
                                        <?php if ($fish['image_path']): ?>
                                            <img src="../<?php echo $fish['image_path']; ?>" alt="<?php echo $fish['name']; ?>">
                                        <?php else: ?>
                                            <div class="placeholder-image"><i class="fas fa-fish"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($fish['name']); ?></td>
                                    <td><?php echo htmlspecialchars($fish['family']); ?></td>
                                    <td><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($fish['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $fish['status']; ?>">
                                            <?php 
                                            switch($fish['status']) {
                                                case 'pending':
                                                    echo '<i class="fas fa-clock"></i> ';
                                                    break;
                                                case 'approved':
                                                    echo '<i class="fas fa-check-circle"></i> ';
                                                    break;
                                                case 'rejected':
                                                    echo '<i class="fas fa-times-circle"></i> ';
                                                    break;
                                            }
                                            echo ucfirst($fish['status']); 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="../fish_details.php?id=<?php echo $fish['id']; ?>" class="btn btn-sm btn-outline" title="View Details"><i class="fas fa-eye"></i></a>
                                        <?php if ($fish['status'] === 'pending'): ?>
                                            <a href="../edit_fish.php?id=<?php echo $fish['id']; ?>" class="btn btn-sm btn-outline" title="Edit Submission"><i class="fas fa-edit"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-fish fa-3x"></i></div>
                    <h3>No Submissions Yet</h3>
                    <p>You haven't submitted any fish information yet.</p>
                    <a href="../submit_fish.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Submit New Fish</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 