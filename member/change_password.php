<?php
$pageTitle = 'Change Password';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    }
    
    if (empty($newPassword) || strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT password FROM member WHERE member_id = ?");
            $stmt->execute([$_SESSION['member_id']]);
            $member = $stmt->fetch();
            
            if ($member && password_verify($currentPassword, $member['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE member SET password = ? WHERE member_id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['member_id']]);
                
                $_SESSION['success'] = 'Password changed successfully!';
                redirect('/member/profile.php');
            } else {
                $errors[] = 'Current password is incorrect';
            }
        } catch (PDOException $e) {
            $errors[] = 'Password change failed. Please try again.';
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Change Password</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/member/change_password.php" class="member-form">
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary member-btn-primary">Change Password</button>
                <a href="/member/profile.php" class="btn btn-secondary member-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script src="/assets/js/member.js"></script>
<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

