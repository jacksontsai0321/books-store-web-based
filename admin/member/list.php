<?php
$pageTitle = 'Manage Members';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Get sort parameters
$sortColumn = $_GET['sort'] ?? 'member_id';
$sortOrder = $_GET['order'] ?? 'ASC'; // Default to ASC (forward)

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort column (prevent SQL injection)
$allowedColumns = ['member_id', 'full_name']; // Removed email from sortable columns
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'member_id';
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
        $whereClause = "WHERE (member_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM member $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    // Simple query - member_id should be unique (primary key), no need for GROUP BY
    // This ensures all members are returned correctly without duplicates
    // LIMIT and OFFSET must be integers, not placeholders
    $stmt = $pdo->prepare("SELECT member_id, full_name, email, phone, address, is_verified, created_at, photo 
                           FROM member 
                           $whereClause
                           ORDER BY $sortColumn $sortOrder
                           LIMIT $perPage OFFSET $offset");
    $stmt->execute($searchParams);
    $members = $stmt->fetchAll();
    
    // Map column names for compatibility
    foreach ($members as &$member) {
        $member['member_name'] = $member['full_name'] ?? '';
        $member['photo_path'] = $member['photo'] ?? '';
    }
    unset($member); // Break reference
} catch (PDOException $e) {
    $members = [];
    $error = 'Unable to load members: ' . $e->getMessage();
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Manage Members</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1rem;">
                <?php
                // Build pagination URL with sort and search parameters
                $paginationParams = [];
                if ($sortColumn !== 'member_id' || $sortOrder !== 'ASC') {
                    $paginationParams['sort'] = $sortColumn;
                    $paginationParams['order'] = $sortOrder;
                }
                if (!empty($search)) {
                    $paginationParams['search'] = $search;
                }
                echo renderPagination($page, $totalPages, '/admin/member/list.php', $paginationParams, 'top');
                ?>
            </div>
            
            <!-- Search Form -->
            <form method="GET" action="/admin/member/list.php" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by ID, name, email, or phone..." 
                       style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                <?php if ($sortColumn !== 'member_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/member/list.php<?php echo ($sortColumn !== 'member_id' || $sortOrder !== 'ASC') ? '?sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($members)): ?>
            <p>No members found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;">
                            <a href="?sort=member_id&order=<?php echo $sortColumn === 'member_id' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                ID
                                <?php if ($sortColumn === 'member_id'): ?>
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
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo $member['member_id']; ?></td>
                            <td><?php echo htmlspecialchars($member['full_name'] ?? $member['member_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td>
                                <?php if ($member['is_verified']): ?>
                                    <span style="color: #27ae60;">Verified</span>
                                <?php else: ?>
                                    <span style="color: #e74c3c;">Not Verified</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php
                                $pageParam = $page > 1 ? '&page=' . $page : '';
                                $sortParams = '';
                                if ($sortColumn !== 'member_id' || $sortOrder !== 'ASC') {
                                    $sortParams = '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder);
                                }
                                $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                                $returnParams = $pageParam . $sortParams . $searchParam;
                                ?>
                                <a href="/admin/member/view.php?id=<?php echo $member['member_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-primary">View</a>
                                <a href="/admin/member/edit.php?id=<?php echo $member['member_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="/admin/member/delete.php?id=<?php echo $member['member_id']; ?><?php echo $returnParams; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this member?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Build pagination URL with sort and search parameters
            $paginationParams = [];
            if ($sortColumn !== 'member_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            if (!empty($search)) {
                $paginationParams['search'] = $search;
            }
            echo renderPagination($page, $totalPages, '/admin/member/list.php', $paginationParams, 'bottom');
            ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

