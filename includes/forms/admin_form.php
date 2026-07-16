<?php
/**
 * Reusable Admin Form Component
 * Variables expected: $admin (array or null), $errors (array)
 */

$admin = $admin ?? [];
$errors = $errors ?? [];
?>

<?php if (!empty($errors)): ?>
    <?php renderErrors($errors); ?>
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction ?? ''); ?>" class="admin-form">
    <div class="form-group">
        <label for="admin_name">Staff Name *</label>
        <input type="text" id="admin_name" name="admin_name" 
               value="<?php echo htmlspecialchars($admin['admin_name'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" 
               required>
    </div>

    <?php if (!isset($admin['admin_id']) || empty($admin['admin_id'])): ?>
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required minlength="6">
            <small>Minimum 6 characters</small>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
    <?php else: ?>
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password" minlength="6">
            <small>Leave blank to keep current password. Minimum 6 characters if changing.</small>
        </div>
    <?php endif; ?>

    <div class="form-group" style="display: flex; gap: 1rem; align-items: center;">
        <button type="submit" class="btn btn-primary"><?php echo $submitText ?? 'Submit'; ?></button>
        <a href="<?php echo $cancelUrl ?? '/admin/admin/list.php'; ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

