<?php
/**
 * Reusable Product Card Component
 * Variables expected: $product (array)
 */

require_once __DIR__ . '/../../config/functions.php';
?>

<div class="product-card">
    <?php if (isset($_SESSION['member_id'])): $isFav = !empty($product['is_favorite']) ? true : false; ?>
        <button class="favorite-btn<?php echo $isFav ? ' active' : ''; ?>" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>" title="Add to favourites">♥</button>
    <?php endif; ?>

    <div class="product-image">
        <?php if (isset($_SESSION['member_id'])): ?>
            <a href="/product/detail.php?id=<?php echo urlencode($product['product_id']); ?>">
        <?php else: ?>
            <a href="/login.php" onclick="return confirm('Please login or register to view product details.');">
        <?php endif; ?>
            <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/../..' . $product['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <div class="product-image-placeholder">
                    <span>No Image</span>
                </div>
            <?php endif; ?>
        </a>
    </div>
    <div class="product-info">
        <h3 class="product-name">
            <?php if (isset($_SESSION['member_id'])): ?>
                <a href="/product/detail.php?id=<?php echo urlencode($product['product_id']); ?>">
            <?php else: ?>
                <a href="/login.php" onclick="return confirm('Please login or register to view product details.');">
            <?php endif; ?>
                <?php echo htmlspecialchars($product['product_name']); ?>
            </a>
        </h3>
        <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
        <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
        <?php if (isset($_SESSION['member_id'])): ?>
            <a href="/product/detail.php?id=<?php echo urlencode($product['product_id']); ?>" class="btn btn-primary btn-sm">View Detail</a>
        <?php endif; ?>
    </div>
</div>

