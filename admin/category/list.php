<?php
$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$sortColumn = $_GET['sort'] ?? 'category_id';
$sortOrder = $_GET['order'] ?? 'ASC';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedColumns = ['category_id', 'category_name'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'category_id';
}
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
$nextOrder = $sortOrder === 'ASC' ? 'DESC' : 'ASC';

try {
    // Build WHERE clause for search
    $whereClause = '';
    $searchParams = [];
    if (!empty($search)) {
        $whereClause = "WHERE (category_id LIKE ? OR category_name LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM product_category $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    // LIMIT and OFFSET must be integers, not placeholders
    $stmt = $pdo->prepare("SELECT * FROM product_category $whereClause ORDER BY $sortColumn $sortOrder LIMIT $perPage OFFSET $offset");
    $stmt->execute($searchParams);
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = 'Unable to load categories.';
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Manage Categories</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <a href="/admin/category/add.php" class="btn btn-primary">Add New Category</a>
                <?php
                // Build pagination URL with sort and search parameters
                $paginationParams = [];
                if ($sortColumn !== 'category_id' || $sortOrder !== 'ASC') {
                    $paginationParams['sort'] = $sortColumn;
                    $paginationParams['order'] = $sortOrder;
                }
                if (!empty($search)) {
                    $paginationParams['search'] = $search;
                }
                echo renderPagination($page, $totalPages, '/admin/category/list.php', $paginationParams, 'top');
                ?>
            </div>
            
            <!-- Search Form -->
            <form method="GET" action="/admin/category/list.php" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by ID or category name..." 
                       style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                <?php if ($sortColumn !== 'category_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/category/list.php<?php echo ($sortColumn !== 'category_id' || $sortOrder !== 'ASC') ? '?sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($categories)): ?>
            <p>No categories found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;">
                            <a href="?sort=category_id&order=<?php echo $sortColumn === 'category_id' ? $nextOrder : 'ASC'; ?>"
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                ID
                                <?php if ($sortColumn === 'category_id'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=category_name&order=<?php echo $sortColumn === 'category_name' ? $nextOrder : 'ASC'; ?>"
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Name
                                <?php if ($sortColumn === 'category_name'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['category_id']; ?></td>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td class="action-buttons">
                                <?php
                                $pageParam = $page > 1 ? '&page=' . $page : '';
                                $sortParams = '';
                                if ($sortColumn !== 'category_id' || $sortOrder !== 'ASC') {
                                    $sortParams = '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder);
                                }
                                $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                                $returnParams = $pageParam . $sortParams . $searchParam;
                                ?>
                                <a href="/admin/category/edit.php?id=<?php echo $category['category_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Build pagination URL with sort parameters
            $paginationParams = [];
            if ($sortColumn !== 'category_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            // Build pagination URL with sort and search parameters
            $paginationParams = [];
            if ($sortColumn !== 'category_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            if (!empty($search)) {
                $paginationParams['search'] = $search;
            }
            echo renderPagination($page, $totalPages, '/admin/category/list.php', $paginationParams, 'bottom');
            ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

