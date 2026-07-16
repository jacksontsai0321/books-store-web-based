<?php
$pageTitle = 'Edit Member';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$memberId = isset($_GET['id']) ? $_GET['id'] : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';

$member = getMemberById($pdo, $memberId);
$errors = [];

if (!$member) {
    $_SESSION['error'] = 'Member not found.';
    $redirectUrl = '/admin/member/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

// Map column names for form
$member['member_name'] = $member['full_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member['member_name'] = sanitize($_POST['member_name'] ?? '');
    $member['email'] = sanitize($_POST['email'] ?? '');
    $member['phone'] = sanitize($_POST['phone'] ?? '');
    $member['address'] = sanitize($_POST['address'] ?? '');
    $member['is_verified'] = isset($_POST['is_verified']) ? 1 : 0;
    
    if (empty($member['member_name']) || !validateText($member['member_name'], 2)) {
        $errors[] = 'Full name must be at least 2 characters';
    }
    
    if (empty($member['email']) || !validateEmail($member['email'])) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($member['phone'])) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($member['address'])) {
        $errors[] = 'Address is required';
    }
    
    if (empty($errors)) {
        try {
            // Check if email is taken by another member
            $stmt = $pdo->prepare("SELECT member_id FROM member WHERE email = ? AND member_id != ?");
            $stmt->execute([$member['email'], $memberId]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already taken by another account';
            } else {
                $stmt = $pdo->prepare("UPDATE member SET full_name = ?, email = ?, phone = ?, address = ?, is_verified = ? WHERE member_id = ?");
                $stmt->execute([
                    $member['member_name'],
                    $member['email'],
                    $member['phone'],
                    $member['address'],
                    $member['is_verified'],
                    $memberId
                ]);
                
                $_SESSION['success'] = 'Member updated successfully!';
                
                // Preserve page number, sort, and search when redirecting back
                $params = [];
                if ($returnPage) {
                    $params['page'] = $returnPage;
                }
                if ($returnSort) {
                    $params['sort'] = $returnSort;
                    $params['order'] = $returnOrder;
                }
                if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                    $params['search'] = trim($_GET['search']);
                }
                $redirectUrl = '/admin/member/list.php';
                if (!empty($params)) {
                    $redirectUrl .= '?' . http_build_query($params);
                }
                redirect($redirectUrl);
            }
        } catch (PDOException $e) {
            $errors[] = 'Update failed. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Edit Member</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/admin/member/edit.php?id=<?php echo $memberId; ?>" class="member-form">
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

            <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; border: 1px solid #e0e0e0;">
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 500; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="is_verified" value="1" 
                           style="width: 18px; height: 18px; cursor: pointer;"
                           <?php echo ($member['is_verified'] ?? 0) ? 'checked' : ''; ?>>
                    <span style="color: #2c3e50;">Verified Account</span>
                </label>
                <small style="color: #7f8c8d; display: block; margin-left: 2rem; font-size: 0.9rem;">
                    <?php if ($member['is_verified'] ?? 0): ?>
                        <span style="color: #27ae60;">✓ This member's account is currently verified</span>
                    <?php else: ?>
                        <span style="color: #e74c3c;">✗ This member's account is not verified</span>
                    <?php endif; ?>
                </small>
            </div>

            <div class="form-group" style="display: flex; gap: 1rem; align-items: center;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">Update Member</button>
                <a href="/admin/member/list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

