<?php
/**
 * Reusable Cart Item Component
 * Variables expected: $item (array)
 */

require_once __DIR__ . '/../../config/functions.php';
?>

<div class="cart-item">
    <div class="cart-item-selector">
        <input type="checkbox" name="selected_cart_ids[]" value="<?php echo $item['cart_id']; ?>" form="checkout-selection-form" checked>
    </div>
    <div class="cart-item-image">
        <?php if (!empty($item['image_path']) && file_exists(__DIR__ . '/../..' . $item['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
        <?php else: ?>
            <div class="product-image-placeholder" style="width: 100%; height: 100%;">
                <span>No Image</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="cart-item-info">
        <h3 class="cart-item-name">
            <a href="/product/detail.php?id=<?php echo $item['product_id']; ?>">
                <?php echo htmlspecialchars($item['product_name']); ?>
            </a>
        </h3>
        <p class="cart-item-price"><?php echo formatPrice($item['price']); ?> each</p>
        <div class="cart-item-quantity">
            <form method="POST" action="/cart/update.php" class="update-quantity-form" data-cart-id="<?php echo $item['cart_id']; ?>">
                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                <label for="quantity_<?php echo $item['cart_id']; ?>">Quantity:</label>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <button type="button" class="btn btn-sm qty-decrease" data-cart-id="<?php echo $item['cart_id']; ?>">-</button>
                    <input type="number" id="quantity_<?php echo $item['cart_id']; ?>" 
                           name="quantity" value="<?php echo $item['quantity']; ?>" 
                           min="0" max="<?php echo $item['stock_quantity']; ?>" required style="width:80px; text-align:center;">
                    <button type="button" class="btn btn-sm qty-increase" data-cart-id="<?php echo $item['cart_id']; ?>">+</button>
                    <noscript><button type="submit" class="btn btn-sm">Update</button></noscript>
                </div>
            </form>
        </div>
        <p class="cart-item-subtotal">Subtotal: <?php echo formatPrice($item['price'] * $item['quantity']); ?></p>
    </div>
    <div class="cart-item-actions">
        <form method="POST" action="/cart/delete.php" class="delete-cart-item-form">
            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item from cart?');">Remove</button>
        </form>
    </div>
</div>

