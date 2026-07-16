<?php
$pageTitle = 'My Favorites';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$memberId = $_SESSION['member_id'];
$products = [];

try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name,
        (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
        FROM favorite f
        JOIN product p ON f.product_id = p.product_id
        LEFT JOIN product_category c ON p.category_id = c.category_id
        WHERE f.member_id = ? AND (p.is_hidden = 0 OR p.is_hidden IS NULL)");
    $stmt->execute([$memberId]);
    $products = $stmt->fetchAll();
    foreach ($products as $idx => $p) {
        $products[$idx]['product_name'] = $p['name'];
        $products[$idx]['stock_quantity'] = $p['stock'] ?? 0;
        $products[$idx]['is_favorite'] = true;
        if (!empty($p['image_path'])) $products[$idx]['image_path'] = '/uploads/products/' . $p['image_path'];
    }
} catch (PDOException $e) {
    $products = [];
}
?>

<main>
    <div class="container">
        <h1 class="page-title">My Favorites</h1>
        <?php if (empty($products)): ?>
            <div class="info-message">You have no favorite products yet. Browse products and click the heart to add them.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../includes/components/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>