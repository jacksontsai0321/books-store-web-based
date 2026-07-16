<?php
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/db.php';

// List with search, sort and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowed = [
    'review_id' => 'r.review_id',
    'product' => 'p.name',
    'member' => 'm.full_name',
    'rating' => 'r.rating'
];
$sortColumn = $_GET['sort'] ?? 'review_id';
$sortOrder = strtoupper($_GET['order'] ?? 'ASC') === 'ASC' ? 'ASC' : 'DESC';
if (!array_key_exists($sortColumn, $allowed)) {
    $sortColumn = 'review_id';
}
$nextOrder = $sortOrder === 'ASC' ? 'DESC' : 'ASC';

try {
    $whereClause = '';
    $params = [];
    if (!empty($search)) {
        $whereClause = "WHERE (p.name LIKE ? OR m.full_name LIKE ? OR r.title LIKE ? OR r.content LIKE ? OR r.review_id LIKE ? )";
        $term = '%' . $search . '%';
        $params = [$term, $term, $term, $term, $term];
    }

    // Count for pagination
    $countSql = "SELECT COUNT(*) as total FROM review r JOIN product p ON r.product_id = p.product_id JOIN member m ON r.member_id = m.member_id $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetch()['total'];
    $totalPages = max(1, (int)ceil($totalItems / $perPage));

    $orderExpr = $allowed[$sortColumn] . ' ' . $sortOrder;
    $sql = "SELECT r.*, p.name as product_name, m.full_name as member_name, a.full_name as admin_name FROM review r JOIN product p ON r.product_id = p.product_id JOIN member m ON r.member_id = m.member_id LEFT JOIN admin a ON r.replied_by = a.admin_id $whereClause ORDER BY $orderExpr LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
    $_SESSION['error'] = 'Unable to load reviews: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $id = $_POST['delete_review_id'];
    deleteReview($pdo, $id);
    header('Location: /admin/review/list.php');
    exit;
}

include_once __DIR__ . '/../../includes/_admin_head.php';
include_once __DIR__ . '/../../includes/_admin_header.php';
include_once __DIR__ . '/../../includes/_admin_sidebar.php';
?>
<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Reviews</h1>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages"><div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div></div>
        <?php endif; ?>

        <?php
            $extraParams = '';
            if (!empty($search)) $extraParams .= '&search=' . urlencode($search);
            if ($page > 1) $extraParams .= '&page=' . $page;
        ?>
        <div style="margin-bottom: 1rem; display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
            <form method="GET" action="/admin/review/list.php" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by product, member, title, or id..." style="padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
                <?php if ($sortColumn !== 'created_at' || $sortOrder !== 'DESC'): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortColumn); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <?php endif; ?>
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <a class="btn btn-secondary" href="/admin/review/list.php">Clear</a>
                <?php endif; ?>
            </form>
            <div><?php echo renderPagination($page, $totalPages, '/admin/review/list.php', (!empty($search) ? ['search' => $search] : []), 'top'); ?></div>
        </div>

        <table class="admin-table review-table">
        <thead>
            <tr>
                <th style="cursor:pointer;">
                    <a href="?sort=review_id&order=<?php echo $sortColumn === 'review_id' ? $nextOrder : 'ASC'; ?><?php echo $extraParams; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:6px;">ID <?php if ($sortColumn === 'review_id'): ?><span style="color:#3498db;font-weight:700"><?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?></span><?php else: ?><span style="color:#95a5a6;opacity:0.5;">↕</span><?php endif; ?></a>
                </th>
                <th style="cursor:pointer;">
                    <a href="?sort=product&order=<?php echo $sortColumn === 'product' ? $nextOrder : 'ASC'; ?><?php echo $extraParams; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:6px;">Product <?php if ($sortColumn === 'product'): ?><span style="color:#3498db;font-weight:700"><?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?></span><?php else: ?><span style="color:#95a5a6;opacity:0.5;">↕</span><?php endif; ?></a>
                </th>
                <th style="cursor:pointer;">
                    <a href="?sort=member&order=<?php echo $sortColumn === 'member' ? $nextOrder : 'ASC'; ?><?php echo $extraParams; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:6px;">Member <?php if ($sortColumn === 'member'): ?><span style="color:#3498db;font-weight:700"><?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?></span><?php else: ?><span style="color:#95a5a6;opacity:0.5;">↕</span><?php endif; ?></a>
                </th>
                <th style="cursor:pointer;">
                    <a href="?sort=rating&order=<?php echo $sortColumn === 'rating' ? $nextOrder : 'DESC'; ?><?php echo $extraParams; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:6px;">Rating <?php if ($sortColumn === 'rating'): ?><span style="color:#3498db;font-weight:700"><?php echo $sortOrder === 'ASC' ? '↑' : '↓'; ?></span><?php else: ?><span style="color:#95a5a6;opacity:0.5;">↕</span><?php endif; ?></a>
                </th>
                <th>Title</th>
                <th>Content</th>
                <th>Reply</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['review_id']); ?></td>
                    <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['member_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['rating']); ?></td>
                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                    <td class="review-content"><?php echo nl2br(htmlspecialchars($r['content'])); ?></td>
                    <td class="reply-content">
                        <?php if (!empty($r['admin_reply'])): ?>
                            <?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?>
                            <div style="margin-top:6px; font-size:0.85rem; color:#666;">— <?php echo htmlspecialchars($r['admin_name'] ?? 'Admin'); ?></div>
                        <?php else: ?>
                            <span style="color:#999;">No reply</span>
                        <?php endif; ?>
                    </td>

                    <td class="action-buttons">
                        <?php
                            $pageParam = $page > 1 ? '&page=' . $page : '';
                            $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                            $sortParam = ($sortColumn !== 'review_id' || $sortOrder !== 'ASC') ? '&sort=' . urlencode($sortColumn) . '&order=' . urlencode($sortOrder) : '';
                            $returnParams = $pageParam . $searchParam . $sortParam;
                        ?>
                        <a class="btn btn-sm btn-secondary" href="/admin/review/reply.php?id=<?php echo urlencode($r['review_id']); ?><?php echo $returnParams; ?>">Reply</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this review?');">
                            <input type="hidden" name="delete_review_id" value="<?php echo htmlspecialchars($r['review_id']); ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        <?php echo renderPagination($page, $totalPages, '/admin/review/list.php', (!empty($search) ? ['search' => $search] : []), 'bottom'); ?>
    </div>

    </div>
</div>

<?php include_once __DIR__ . '/../../includes/_admin_footer.php';
