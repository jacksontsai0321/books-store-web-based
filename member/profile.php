<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$member = getMemberById($pdo, $_SESSION['member_id']);
?>

<main>
    <div class="container">
        <h1 class="page-title">My Profile</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="profile-section" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: grid; grid-template-columns: 200px 1fr; gap: 2rem; align-items: center;">
            <div class="profile-photo" style="text-align: center;">
                <?php if (!empty($member['photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($member['photo_path']); ?>" 
                         alt="Profile Photo" 
                         style="width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 4px solid #ecf0f1;">
                <?php else: ?>
                    <div style="width: 160px; height: 160px; border-radius: 50%; background: #ecf0f1; display: flex; align-items: center; justify-content: center; color: #7f8c8d; font-weight: 600; border: 4px solid #ecf0f1;">
                        No Photo
                    </div>
                <?php endif; ?>
                <div style="margin-top: 1rem;">
                    <a href="/member/upload_photo.php" class="btn btn-secondary btn-sm member-btn-secondary">Upload / Change Photo</a>
                </div>
            </div>
            
            <div>
                <div class="profile-info" style="display: grid; grid-template-columns: 1fr 2fr; row-gap: 0.6rem; column-gap: 1rem;">
                    <span><strong>Name:</strong></span><span><?php echo htmlspecialchars($member['member_name']); ?></span>
                    <span><strong>Email:</strong></span><span><?php echo htmlspecialchars($member['email']); ?></span>
                    <span><strong>Phone:</strong></span><span><?php echo htmlspecialchars($member['phone']); ?></span>
                    <span><strong>Address:</strong></span><span><?php echo htmlspecialchars($member['address']); ?></span>
                    <span><strong>Status:</strong></span>
                    <span>
                        <?php if (!empty($member['is_verified'])): ?>
                            <span class="in-stock">Verified</span>
                        <?php else: ?>
                            <span class="out-of-stock">Not Verified</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="profile-actions" style="margin-top: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="/member/profile_edit.php" class="btn btn-primary member-btn-primary">Edit Profile</a>
                    <a href="/member/change_password.php" class="btn btn-secondary member-btn-secondary">Change Password</a>
                    <?php if (empty($member['is_verified'])): ?>
                        <form method="POST" action="/member/send_verification.php" style="display: inline;">
                            <button type="submit" class="btn btn-secondary member-btn-secondary">Verify Email</button>
                        </form>
                    <?php endif; ?>
                    <a href="/member/delete_account.php" class="btn btn-danger">Delete Account</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

