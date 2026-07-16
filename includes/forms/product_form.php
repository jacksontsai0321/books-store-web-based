<?php
/**
 * Reusable Product Form Component
 * Variables expected: $product (array or null), $categories (array), $errors (array)
 */

$product = $product ?? [];
$categories = $categories ?? [];
$errors = $errors ?? [];
?>

<?php if (!empty($errors)): ?>
    <?php renderErrors($errors); ?>
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction ?? ''); ?>" enctype="multipart/form-data" class="product-form">
    <div class="form-group">
        <label for="product_name">Product Name *</label>
        <input type="text" id="product_name" name="product_name" 
               value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group">
        <label for="category_id">Category *</label>
        <select id="category_id" name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" 
                        <?php echo (isset($product['category_id']) && $product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="price">Price (RM) *</label>
            <input type="number" id="price" name="price" step="0.01" min="0" 
                   value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" 
                   required>
        </div>

        <div class="form-group">
            <label for="stock_quantity">Stock Quantity *</label>
            <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                   value="<?php echo htmlspecialchars($product['stock_quantity'] ?? ''); ?>" 
                   required>
        </div>
    </div>

    <?php if (isset($product['product_id']) && !empty($product['product_id'])): ?>
        <div class="form-group-1">
            <label><input type="checkbox" name="is_hidden" value="1" <?php echo !empty($product['is_hidden']) ? 'checked' : ''; ?>> Hidden (hide from public site)</label>
        </div>
    <?php endif; ?>

    <?php if (!isset($product['product_id']) || empty($product['product_id'])): ?>
        <div class="form-group">
            <label for="images">Product Images</label>
            <input type="file" id="images" name="images[]" accept="image/*" multiple>
            <small>You can select multiple images at once. Allowed formats: JPEG, PNG, GIF (Max 5MB each)</small>
        </div>
    <?php else: ?>
        <div class="form-group">
            <label>Product Images</label>
            <p>Images are managed separately. Use the <a href="/admin/product/photos.php?id=<?php echo $product['product_id']; ?>">Photos</a> page to add or remove images.</p>
            <?php if (isset($product['image_path']) && !empty($product['image_path'])): ?>
                <div class="current-image" style="margin-top: 1rem;">
                    <p>Current Image:</p>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Current product image" style="max-width: 200px;">
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-group" style="display: flex; gap: 0.5rem; align-items: center;">
        <button type="submit" class="btn btn-primary"><?php echo $submitText ?? 'Submit'; ?></button>
        <a href="<?php echo $cancelUrl ?? '/admin/product/list.php'; ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

