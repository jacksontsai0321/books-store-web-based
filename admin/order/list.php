<?php
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Get sort parameters
$sortColumn = $_GET['sort'] ?? 'order_id';
$sortOrder = $_GET['order'] ?? 'ASC'; // Default to ASC (O0001 first)

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort column (prevent SQL injection)
$allowedColumns = ['order_id', 'member_name', 'total_amount', 'status', 'order_date'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'order_id';
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
        $whereClause = "WHERE (oh.order_id LIKE ? OR m.full_name LIKE ? OR oh.status LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM order_header oh LEFT JOIN member m ON oh.member_id = m.member_id $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    // Build ORDER BY clause
    // For order_id sorting, extract numeric part for proper numeric sorting (O0001 -> 1, O0002 -> 2)
    if ($sortColumn === 'order_id') {
        $orderByClause = "CAST(SUBSTRING(oh.order_id, 2) AS UNSIGNED) $sortOrder";
    } elseif ($sortColumn === 'member_name') {
        $orderByClause = "m.full_name $sortOrder";
    } elseif ($sortColumn === 'total_amount') {
        $orderByClause = "oh.total_amount $sortOrder";
    } elseif ($sortColumn === 'status') {
        $orderByClause = "oh.status $sortOrder";
    } elseif ($sortColumn === 'order_date') {
        $orderByClause = "oh.order_date $sortOrder";
    } else {
        $orderByClause = "CAST(SUBSTRING(oh.order_id, 2) AS UNSIGNED) ASC";
    }
    
    // Use correct table names from ERD
    // LIMIT and OFFSET must be integers, not placeholders
    $stmt = $pdo->prepare("SELECT oh.*, m.full_name as member_name 
                           FROM order_header oh 
                           LEFT JOIN member m ON oh.member_id = m.member_id 
                           $whereClause
                           ORDER BY $orderByClause
                           LIMIT $perPage OFFSET $offset");
    $stmt->execute($searchParams);
    $orders = $stmt->fetchAll();
    
    // Map column names for compatibility
    foreach ($orders as &$order) {
        $order['created_at'] = $order['order_date'] ?? '';
    }
    unset($order);
} catch (PDOException $e) {
    $orders = [];
    $error = 'Unable to load orders: ' . $e->getMessage();
    error_log('Order list error: ' . $e->getMessage());
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Manage Orders</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1rem;">
                <?php
                // Build pagination URL with sort and search parameters
                $paginationParams = [];
                if ($sortColumn !== 'order_id' || $sortOrder !== 'ASC') {
                    $paginationParams['sort'] = $sortColumn;
                    $paginationParams['order'] = $sortOrder;
                }
                if (!empty($search)) {
                    $paginationParams['search'] = $search;
                }
                echo renderPagination($page, $totalPages, '/admin/order/list.php', $paginationParams, 'top');
                ?>
            </div>
            
            <!-- Search Form -->
            <form method="GET" action="/admin/order/list.php" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by order ID, member name, or status..." 
                       style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                <?php if ($sortColumn !== 'order_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/order/list.php<?php echo ($sortColumn !== 'order_id' || $sortOrder !== 'ASC') ? '?sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;">
                            <a href="?sort=order_id&order=<?php echo $sortColumn === 'order_id' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Order ID
                                <?php if ($sortColumn === 'order_id'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=member_name&order=<?php echo $sortColumn === 'member_name' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Member
                                <?php if ($sortColumn === 'member_name'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=total_amount&order=<?php echo $sortColumn === 'total_amount' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Total Amount
                                <?php if ($sortColumn === 'total_amount'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=status&order=<?php echo $sortColumn === 'status' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Status
                                <?php if ($sortColumn === 'status'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th style="cursor: pointer;">
                            <a href="?sort=order_date&order=<?php echo $sortColumn === 'order_date' ? $nextOrder : 'DESC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                Date
                                <?php if ($sortColumn === 'order_date'): ?>
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
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['member_name']); ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td class="action-buttons">
                                <?php
                                $pageParam = $page > 1 ? '&page=' . $page : '';
                                $sortParams = '';
                                if ($sortColumn !== 'order_id' || $sortOrder !== 'ASC') {
                                    $sortParams = '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder);
                                }
                                $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                                $returnParams = $pageParam . $sortParams . $searchParam;
                                ?>
                                <a href="/admin/order/view.php?id=<?php echo $order['order_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Build pagination URL with sort and search parameters
            $paginationParams = [];
            if ($sortColumn !== 'order_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            if (!empty($search)) {
                $paginationParams['search'] = $search;
            }
            echo renderPagination($page, $totalPages, '/admin/order/list.php', $paginationParams, 'bottom');
            ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

