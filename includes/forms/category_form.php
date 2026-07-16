<?php
/**
 * Reusable Category Form Component
 * Variables expected: $category (array or null), $errors (array)
 */

$category = $category ?? [];
$errors = $errors ?? [];
?>

<?php if (!empty($errors)): ?>
    <?php renderErrors($errors); ?>
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction ?? ''); ?>" class="category-form">
    <div class="form-group">
        <label for="category_name">Category Name *</label>
        <input type="text" id="category_name" name="category_name" 
               value="<?php echo htmlspecialchars($category['category_name'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group" style="display: flex; gap: 1rem; align-items: center;">
        <button type="submit" class="btn btn-primary"><?php echo $submitText ?? 'Submit'; ?></button>
        <a href="<?php echo $cancelUrl ?? '/admin/category/list.php'; ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

