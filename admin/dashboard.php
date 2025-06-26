<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../login.php', 'Please login to access the admin area', 'error');
}

if (!isAdmin()) {
    redirect('../user/dashboard.php', 'You do not have permission to access the admin area', 'error');
}

$page = 'admin';
$page_title = 'Admin Dashboard';
$extra_css = ['/styles/admin.css', '/styles/dashboard.css'];

// Get counts for stats
$stats_sql = "SELECT status, COUNT(*) as count FROM fish GROUP BY status";
$stats_result = mysqli_query($conn, $stats_sql);

$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];

while ($row = mysqli_fetch_assoc($stats_result)) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get total users count
$users_sql = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
$users_result = mysqli_query($conn, $users_sql);
$users_row = mysqli_fetch_assoc($users_result);
$stats['users'] = $users_row['count'];

// Filter submissions by status
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Get submissions with pagination
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page_num - 1) * $per_page;

$sql = "SELECT f.*, u.name as submitter_name, 
        (SELECT COUNT(*) FROM fish_images WHERE fish_id = f.id) AS image_count, 
        (SELECT image_path FROM fish_images WHERE fish_id = f.id ORDER BY is_primary DESC LIMIT 1) AS image_path
        FROM fish f 
        LEFT JOIN users u ON f.submitted_by = u.id";

$where = [];
$params = [];

if (!empty($status_filter)) {
    $where[] = "f.status = ?";
    $params[] = $status_filter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY f.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $types = '';
    if (count($params) > 2) {
        $types = str_repeat('s', count($params) - 2);
    }
    $types .= 'ii';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM fish f";
if (!empty($where)) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params) && count($params) > 2) {
    $types = str_repeat('s', count($params) - 2);
    $count_params = array_slice($params, 0, count($params) - 2);
    $count_stmt->bind_param($types, ...$count_params);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['count'];
$total_pages = ceil($total_records / $per_page);

include '../includes/header.php';
?>

<!-- Admin Dashboard Header -->
<div class="dashboard-header admin-header">
    <div class="container">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
</div>

<!-- Admin Navigation -->
<div class="admin-nav">
    <div class="container">
        <ul class="admin-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-fish"></i> Fish Submissions</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> User Management</a></li>
        </ul>
    </div>
</div>

<!-- Admin Content -->
<main class="dashboard-content">
    <div class="container">
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-data"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-data"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-data"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <div class="stat-data"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form action="dashboard.php" method="get" class="admin-filter-form">
                <div class="filter-group">
                    <label for="status"><i class="fas fa-filter"></i> Filter by Status:</label>
                    <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Submissions</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </form>
        </div>
        
        <!-- Submissions Table -->
        <div class="table-section">
            <h2><i class="fas fa-list"></i> Fish Submissions</h2>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Fish Name</th>
                                <th>Family</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
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
                                    <td><?php echo htmlspecialchars($fish['submitter_name']); ?></td>
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
                                            <form method="post" action="update_status.php" class="inline-form">
                                                <input type="hidden" name="fish_id" value="<?php echo $fish['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                            </form>
                                            
                                            <form method="post" action="update_status.php" class="inline-form">
                                                <input type="hidden" name="fish_id" value="<?php echo $fish['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="delete_fish.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this fish entry?');">
                                            <input type="hidden" name="fish_id" value="<?php echo $fish['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page_num > 1): ?>
                            <a href="?page=<?php echo $page_num - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="pagination-prev"><i class="fas fa-chevron-left"></i> Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                               class="pagination-number <?php echo $page_num == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page_num < $total_pages): ?>
                            <a href="?page=<?php echo $page_num + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="pagination-next">Next <i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-fish fa-3x"></i></div>
                    <h3>No Submissions Found</h3>
                    <?php if (!empty($status_filter)): ?>
                        <p>No <?php echo $status_filter; ?> submissions found.</p>
                        <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-sync-alt"></i> View All Submissions</a>
                    <?php else: ?>
                        <p>There are no fish submissions yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 