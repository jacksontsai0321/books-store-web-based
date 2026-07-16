<?php
/**
 * Reusable Member Form Component
 * Variables expected: $member (array or null), $errors (array)
 */

$member = $member ?? [];
$errors = $errors ?? [];
?>

<?php if (!empty($errors)): ?>
    <?php renderErrors($errors); ?>
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction ?? ''); ?>" class="member-form">
    <div class="form-group">
        <label for="member_name">Full Name *</label>
        <input type="text" id="member_name" name="member_name" 
               value="<?php echo htmlspecialchars($member['member_name'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group">
        <label for="phone">Phone *</label>
        <input type="tel" id="phone" name="phone" 
               value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" 
               required>
    </div>

    <div class="form-group">
        <label for="address">Address *</label>
        <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary member-btn-primary"><?php echo $submitText ?? 'Submit'; ?></button>
        <a href="<?php echo $cancelUrl ?? '/member/profile.php'; ?>" class="btn btn-secondary member-btn-secondary">Cancel</a>
    </div>
</form>

