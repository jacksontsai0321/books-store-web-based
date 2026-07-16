<?php
$pageTitle = 'Product Detail';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

// Require login to view product details
if (!isset($_SESSION['member_id'])) {
    $_SESSION['error'] = 'Please login or register to view product details.';
    redirect('/login.php');
}

$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($productId)) {
    redirect('/product/list.php');
}

$product = getProductById($pdo, $productId);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect('/product/list.php');
}

// Get all photos for this product
$productPhotos = [];
try {
    $stmt = $pdo->prepare("SELECT filename FROM product_photo WHERE product_id = ? ORDER BY photo_id ASC");
    $stmt->execute([$productId]);
    $photos = $stmt->fetchAll();
    foreach ($photos as $photo) {
        $productPhotos[] = '/uploads/products/' . $photo['filename'];
    }
} catch (PDOException $e) {
    $productPhotos = [];
}

// If no photos, use the single image_path if available
if (empty($productPhotos) && !empty($product['image_path'])) {
    $productPhotos[] = $product['image_path'];
}
?>

<main>
    <div class="container">
        <?php 
        // Pass productPhotos to the component
        $productPhotos = $productPhotos ?? [];
        include __DIR__ . '/../includes/components/product_detail_block.php'; 
        ?>

        <?php
        // Load reviews for this product
        $reviews = getReviewsByProduct($pdo, $productId);
        // Determine if current member can review (has completed purchase and not yet reviewed)
        $canReview = false;
        if (isset($_SESSION['member_id'])) {
            $canReview = memberHasPurchasedProduct($pdo, $_SESSION['member_id'], $productId) && !hasMemberReviewedProduct($pdo, $_SESSION['member_id'], $productId);
        }
        ?>

        <div style="margin-top: 2rem;">
            <h2>Customer Reviews</h2>
            <?php if ($canReview): ?>
                <p><a href="/order/review.php?product_id=<?php echo urlencode($productId); ?>" class="btn btn-primary member-btn-primary">Write a Review</a></p>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p class="info-message">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-item" style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong><?php echo htmlspecialchars($r['member_name']); ?></strong>
                                <span><?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?></span>
                            </div>
                            <?php if (!empty($r['title'])): ?><div style="font-weight:600; margin-top:0.5rem;"><?php echo htmlspecialchars($r['title']); ?></div><?php endif; ?>
                            <div style="margin-top:0.5rem; color:#444;"><?php echo nl2br(htmlspecialchars($r['content'])); ?></div>
                            <div style="margin-top:0.5rem; color:#888; font-size:0.9rem;"><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></div>
                            <?php if (!empty($r['admin_reply'])): ?>
                                <div class="admin-reply" style="margin-top:0.75rem; padding:0.75rem; background:#f9f9f9; border-left:3px solid #007bff;">
                                    <div style="font-weight:600; margin-bottom:6px;">Reply from <?php echo htmlspecialchars($r['admin_name'] ?? 'Admin'); ?></div>
                                    <div style="color:#333;"><?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

