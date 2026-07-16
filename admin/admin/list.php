<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
$pageTitle = 'Manage Admins';
$canManageStaff = isSuperAdmin();
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Get sort parameters
$sortColumn = $_GET['sort'] ?? 'admin_id';
$sortOrder = $_GET['order'] ?? 'ASC'; // Default to ASC (forward)

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort column (prevent SQL injection)
$allowedColumns = ['admin_id', 'full_name'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'admin_id';
}

// Validate sort order
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

// Determine next sort order for links
$nextOrder = $sortOrder === 'ASC' ? 'DESC' : 'ASC';

try {
    // Build WHERE clause for search
    $whereClause = '';
    $searchParams = [];
    if (!empty($search)) {
        $whereClause = "WHERE (admin_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM admin $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    // Simple query - admin_id should be unique (primary key)
    // LIMIT and OFFSET must be integers, not placeholders
    $stmt = $pdo->prepare("SELECT admin_id, full_name, email, created_at FROM admin $whereClause ORDER BY $sortColumn $sortOrder LIMIT $perPage OFFSET $offset");
    $stmt->execute($searchParams);
    $admins = $stmt->fetchAll();
    
    // Map column names for compatibility
    foreach ($admins as &$admin) {
        $admin['admin_name'] = $admin['full_name'] ?? '';
    }
    unset($admin); // Break reference
} catch (PDOException $e) {
    $admins = [];
    $error = 'Unable to load Staffs: ' . $e->getMessage();
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Manage Staff</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <?php if ($canManageStaff): ?>
                    <a href="/admin/admin/add.php" class="btn btn-primary">Add New Staff</a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <?php
                // Build pagination URL with sort and search parameters
                $paginationParams = [];
                if ($sortColumn !== 'admin_id' || $sortOrder !== 'ASC') {
                    $paginationParams['sort'] = $sortColumn;
                    $paginationParams['order'] = $sortOrder;
                }
                if (!empty($search)) {
                    $paginationParams['search'] = $search;
                }
                echo renderPagination($page, $totalPages, '/admin/admin/list.php', $paginationParams, 'top');
                ?>
            </div>
            
            <!-- Search Form -->
            <form method="GET" action="/admin/admin/list.php" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by ID, name, or email..." 
                       style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                <?php if ($sortColumn !== 'admin_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/admin/list.php<?php echo ($sortColumn !== 'admin_id' || $sortOrder !== 'ASC') ? '?sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($admins)): ?>
            <p>No Staffs found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;">
                            <a href="?sort=admin_id&order=<?php echo $sortColumn === 'admin_id' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                ID
                                <?php if ($sortColumn === 'admin_id'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=full_name&order=<?php echo $sortColumn === 'full_name' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Name
                                <?php if ($sortColumn === 'full_name'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['admin_id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['full_name'] ?? $admin['admin_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td class="action-buttons">
                                <?php
                                $pageParam = $page > 1 ? '&page=' . $page : '';
                                $sortParams = '';
                                if ($sortColumn !== 'admin_id' || $sortOrder !== 'ASC') {
                                    $sortParams = '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder);
                                }
                                $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                                $returnParams = $pageParam . $sortParams . $searchParam;
                                ?>
                                <?php if ($canManageStaff): ?>
                                    <a href="/admin/admin/edit.php?id=<?php echo $admin['admin_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                        <a href="/admin/admin/delete.php?id=<?php echo $admin['admin_id']; ?><?php echo $returnParams; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this Staff?');">Delete</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Build pagination URL with sort and search parameters
            $paginationParams = [];
            if ($sortColumn !== 'admin_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            if (!empty($search)) {
                $paginationParams['search'] = $search;
            }
            echo renderPagination($page, $totalPages, '/admin/admin/list.php', $paginationParams, 'bottom');
            ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

