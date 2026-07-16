<?php
$pageTitle = 'Manage Products';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Get sort parameters (like admin/member lists)
$sortColumn = $_GET['sort'] ?? 'product_id';
$sortOrder = $_GET['order'] ?? 'ASC'; // Default to ASC (P0001 first)

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Validate sort column (prevent SQL injection)
$allowedColumns = ['product_id', 'name', 'price', 'stock'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'product_id';
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
        $whereClause = "WHERE (p.product_id LIKE ? OR p.name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT p.product_id) as total FROM product p LEFT JOIN product_category c ON p.category_id = c.category_id $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($searchParams);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $perPage);
    
    // Simple query like admin/member lists - product_id is primary key, so no duplicates
    // Use GROUP BY to ensure one row per product even with JOINs
    // For product_id sorting, extract numeric part for proper numeric sorting
    if ($sortColumn === 'product_id') {
        // Sort by numeric part of product_id (P0001 -> 1, P0002 -> 2)
        // Use CAST to convert substring to number for proper numeric sorting
        $orderByClause = "CAST(SUBSTRING(p.product_id, 2) AS UNSIGNED) $sortOrder";
    } else {
        // For other columns, use direct column name (validated above)
        $orderByClause = "p.$sortColumn $sortOrder";
    }
    
    // LIMIT and OFFSET must be integers, not placeholders
    // Build select columns dynamically to avoid referencing missing columns (e.g., is_hidden)
    $selectColumns = "p.product_id, p.category_id, p.name, p.description, p.price, p.stock, p.created_by, p.created_at";
    if (!empty($hasIsHidden)) {
        $selectColumns .= ", p.is_hidden";
    }

    $stmt = $pdo->prepare("SELECT $selectColumns,
                           c.category_name,
                           (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                           FROM product p 
                           LEFT JOIN product_category c ON p.category_id = c.category_id
                           $whereClause
                           GROUP BY p.product_id
                           ORDER BY $orderByClause
                           LIMIT $perPage OFFSET $offset");
    $stmt->execute($searchParams);
    $products = $stmt->fetchAll();
    
    // Map column names for compatibility
    foreach ($products as &$product) {
        $product['product_name'] = $product['name'] ?? '';
        $product['stock_quantity'] = $product['stock'] ?? 0;
        // Flag low stock (less than 10) and out-of-stock (0 or less)
        $product['is_low_stock'] = ($product['stock_quantity'] < 10);
        $product['is_out_of_stock'] = ($product['stock_quantity'] <= 0);
        if (!empty($product['image_path'])) {
            $product['image_path'] = '/uploads/products/' . $product['image_path'];
        }
    }
    unset($product); // Break reference
} catch (PDOException $e) {
    $products = [];
    $error = 'Unable to load products: ' . $e->getMessage();
    error_log('Product list error: ' . $e->getMessage());
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Manage Products</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
            <div class="error-messages" style="margin-bottom: 1rem;">
                <div class="error-message" style="font-weight: bold; margin-bottom: 0.5rem;">Upload Errors:</div>
                <?php foreach ($_SESSION['upload_errors'] as $error): ?>
                    <div class="error-message" style="margin-left: 1rem; margin-bottom: 0.25rem;">• <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <?php unset($_SESSION['upload_errors']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message" style="color: #b40000; font-weight: bold; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <a href="/admin/product/add.php" class="btn btn-primary">Add New Product</a>
                <?php
                // Build pagination URL with sort and search parameters
                $paginationParams = [];
                if ($sortColumn !== 'product_id' || $sortOrder !== 'ASC') {
                    $paginationParams['sort'] = $sortColumn;
                    $paginationParams['order'] = $sortOrder;
                }
                if (!empty($search)) {
                    $paginationParams['search'] = $search;
                }
                echo renderPagination($page, $totalPages, '/admin/product/list.php', $paginationParams, 'top');
                ?>
            </div>
            
            <!-- Search Form -->
            <form method="GET" action="/admin/product/list.php" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by ID, name, description, or category..." 
                       style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                <?php if ($sortColumn !== 'product_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="/admin/product/list.php<?php echo ($sortColumn !== 'product_id' || $sortOrder !== 'ASC') ? '?sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($products)): ?>
            <p>No products found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="cursor: pointer;">
                            <a href="?sort=product_id&order=<?php echo $sortColumn === 'product_id' ? $nextOrder : 'ASC'; ?>" 
                               style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px; width: 100%;">
                                ID
                                <?php if ($sortColumn === 'product_id'): ?>
                                    <span style="color: #3498db; font-weight: bold;">
                                        <?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6; opacity: 0.5;">↕</span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr class="product-row<?php echo $product['is_low_stock'] ? ' low-stock-row' : ''; ?><?php echo $product['is_out_of_stock'] ? ' out-of-stock' : ''; ?>" <?php if (!empty($product['image_path'])): ?>data-product-image="<?php echo htmlspecialchars($product['image_path']); ?>"<?php endif; ?>>
                            <td><?php echo $product['product_id']; ?></td>
                            <td class="product-name-cell"><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td>
                                <?php echo $product['stock_quantity']; ?>
                                <?php if ($product['is_low_stock']): ?>
                                    <span class="low-stock-badge<?php echo $product['is_out_of_stock'] ? ' out-of-stock' : ''; ?>" title="<?php echo $product['is_out_of_stock'] ? 'Out of stock' : 'Low stock'; ?>">
                                        <?php echo $product['is_out_of_stock'] ? 'Out' : 'Low'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php
                                $pageParam = $page > 1 ? '&page=' . $page : '';
                                $sortParams = '';
                                if ($sortColumn !== 'product_id' || $sortOrder !== 'ASC') {
                                    $sortParams = '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder);
                                }
                                $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                                $returnParams = $pageParam . $sortParams . $searchParam;
                                ?>
                                <a href="/admin/product/edit.php?id=<?php echo $product['product_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="/admin/product/photos.php?id=<?php echo $product['product_id']; ?><?php echo $returnParams; ?>" class="btn btn-sm btn-primary">Photos</a>
                                <?php if (empty($product['is_hidden'])): ?>
                                    <a href="/admin/product/delete.php?id=<?php echo $product['product_id']; ?><?php echo $returnParams; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-secondary" title="Product is hidden">Hidden</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Build pagination URL with sort and search parameters
            $paginationParams = [];
            if ($sortColumn !== 'product_id' || $sortOrder !== 'ASC') {
                $paginationParams['sort'] = $sortColumn;
                $paginationParams['order'] = $sortOrder;
            }
            if (!empty($search)) {
                $paginationParams['search'] = $search;
            }
            echo renderPagination($page, $totalPages, '/admin/product/list.php', $paginationParams, 'bottom');
            ?>
        <?php endif; ?>
    </div>
</div>

<style>
.product-row {
    position: relative;
    cursor: pointer;
}

.product-row:hover {
    background-color: #f5f5f5;
}

.product-name-cell {
    position: relative;
}

.product-row .product-tooltip {
    display: none;
    position: fixed;
    z-index: 1000;
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    pointer-events: none;
    width: 216px;
    height: 216px;
    overflow: hidden;
}

.product-row:hover .product-tooltip {
    display: block;
}

/* Low stock styles */
.low-stock-badge {
    display: inline-block;
    margin-left: 0.5rem;
    background: #ffe6a7; /* soft yellow */
    color: #8a5d00;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.85rem;
    vertical-align: middle;
    font-weight: 600;
}

.low-stock-row {
    background-color: #fff8e6; /* light yellow background to highlight low stock */
}

/* Out of stock styling */
.low-stock-badge.out-of-stock {
    background: #ffd6d6;
    color: #b40000;
}

.out-of-stock {
    background-color: #fff0f0 !important;
}

.product-row .product-tooltip img {
    width: 200px;
    height: 200px;
    object-fit: contain;
    display: block;
    border-radius: 4px;
    margin: 0;
    padding: 0;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productRows = document.querySelectorAll('.product-row[data-product-image]');
    
    productRows.forEach(function(row) {
        const imagePath = row.getAttribute('data-product-image');
        if (imagePath) {
            const productNameCell = row.querySelector('.product-name-cell');
            const tooltip = document.createElement('div');
            tooltip.className = 'product-tooltip';
            const img = document.createElement('img');
            img.src = imagePath;
            img.alt = 'Product Image';
            img.onerror = function() {
                tooltip.style.display = 'none';
            };
            tooltip.appendChild(img);
            productNameCell.appendChild(tooltip);
            
            // Position tooltip on mouseenter
            row.addEventListener('mouseenter', function(e) {
                const cellRect = productNameCell.getBoundingClientRect();
                const tooltipWidth = 216;
                const tooltipHeight = 216;
                const spacing = 15;
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;
                
                let left = cellRect.right + spacing;
                let top = cellRect.top;
                
                // Check if tooltip would go off right edge
                if (left + tooltipWidth > windowWidth - 20) {
                    left = cellRect.left - tooltipWidth - spacing;
                }
                
                // Check if tooltip would go off bottom edge
                if (top + tooltipHeight > windowHeight - 20) {
                    top = windowHeight - tooltipHeight - 20;
                }
                
                // Ensure tooltip doesn't go off top edge
                if (top < 20) {
                    top = 20;
                }
                
                // Ensure tooltip doesn't go off left edge
                if (left < 20) {
                    left = 20;
                }
                
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

