<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php', 'Unauthorized access', 'error');
}

$page = 'admin';
$page_title = 'User Management';
$extra_css = ['../styles/admin.css', '../styles/dashboard.css'];

// Get users with pagination
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page_num - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT * FROM users WHERE is_admin = 0";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
if (!empty($search)) {
    $count_sql .= " AND (name LIKE ? OR email LIKE ?)";
}

$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($search)) {
    $count_params = array_slice($params, 0, 2);
    $count_stmt->bind_param('ss', ...$count_params);
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
        <h1>User Management</h1>
        <p>Manage registered users</p>
    </div>
</div>

<!-- Admin Navigation -->
<div class="admin-nav">
    <div class="container">
        <ul class="admin-menu">
            <li><a href="dashboard.php">Fish Submissions</a></li>
            <li><a href="users.php" class="active">User Management</a></li>
        </ul>
    </div>
</div>

<!-- Admin Content -->
<main class="dashboard-content">
    <div class="container">
        <!-- Action Button -->
        <div class="actions-section">
            <button class="btn btn-primary" id="showAddUserBtn">Add New User</button>
        </div>
        
        <!-- Add User Form (Hidden by Default) -->
        <div class="form-container" id="addUserForm" style="display: none;">
            <h2>Add New User</h2>
            <form method="post" action="add_user.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Save User</button>
                    <button type="button" class="btn btn-secondary" id="cancelAddUser">Cancel</button>
                </div>
            </form>
        </div>
        
        <!-- Search Form -->
        <div class="filter-section">
            <form action="users.php" method="get" class="search-form">
                <div class="search-group">
                    <input type="text" name="search" class="form-input" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-section">
            <h2>Users (<?php echo $total_records; ?>)</h2>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <form method="post" action="delete_user.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user? All their fish submissions will be affected.');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
                            <a href="?page=<?php echo $page_num - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="pagination-prev">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="pagination-number <?php echo $page_num == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page_num < $total_pages): ?>
                            <a href="?page=<?php echo $page_num + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="pagination-next">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">👤</div>
                    <h3>No Users Found</h3>
                    <?php if (!empty($search)): ?>
                        <p>No users match your search criteria.</p>
                        <a href="users.php" class="btn btn-primary">View All Users</a>
                    <?php else: ?>
                        <p>There are no registered users yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    // Toggle Add User Form
    document.getElementById('showAddUserBtn').addEventListener('click', function() {
        document.getElementById('addUserForm').style.display = 'block';
        this.style.display = 'none';
    });
    
    document.getElementById('cancelAddUser').addEventListener('click', function() {
        document.getElementById('addUserForm').style.display = 'none';
        document.getElementById('showAddUserBtn').style.display = 'block';
    });
</script>

<?php include '../includes/footer.php'; ?> 