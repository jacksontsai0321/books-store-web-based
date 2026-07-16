<?php
$pageTitle = 'View Member';
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
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">View Member Details</h1>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Photo Section -->
                <div style="text-align: center;">
                    <div style="width: 200px; height: 200px; margin: 0 auto; border: 2px solid #e0e0e0; border-radius: 8px; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($member['photo'] ?? $member['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($member['photo'] ?? $member['photo_path']); ?>" 
                                 alt="Profile Photo" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="color: #95a5a6; font-size: 0.9rem; text-align: center; padding: 1rem;">
                                No Photo
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Member Information -->
                <div>
                    <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($member['full_name'] ?? $member['member_name'] ?? 'N/A'); ?>
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-weight: bold; color: #7f8c8d;">Member ID:</div>
                        <div><?php echo htmlspecialchars($member['member_id']); ?></div>
                        
                        <div style="font-weight: bold; color: #7f8c8d;">Email:</div>
                        <div><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></div>
                        
                        <div style="font-weight: bold; color: #7f8c8d;">Phone:</div>
                        <div><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></div>
                        
                        <div style="font-weight: bold; color: #7f8c8d;">Status:</div>
                        <div>
                            <?php if ($member['is_verified'] ?? false): ?>
                                <span style="background: #27ae60; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.9rem;">✓ Verified</span>
                            <?php else: ?>
                                <span style="background: #e74c3c; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.9rem;">✗ Not Verified</span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="font-weight: bold; color: #7f8c8d;">Registered:</div>
                        <div><?php echo date('Y-m-d H:i', strtotime($member['created_at'] ?? 'now')); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Address Section -->
            <div style="border-top: 1px solid #e0e0e0; padding-top: 1.5rem; margin-top: 1.5rem;">
                <h3 style="color: #2c3e50; margin-bottom: 0.5rem;">Address</h3>
                <p style="color: #555; line-height: 1.6; margin: 0;">
                    <?php echo !empty($member['address']) ? nl2br(htmlspecialchars($member['address'])) : '<span style="color: #95a5a6;">No address provided</span>'; ?>
                </p>
            </div>
        </div>
        
        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <?php
            $backUrl = '/admin/member/list.php';
            if ($returnPage) {
                $backUrl .= '?page=' . $returnPage;
                if ($returnSort) {
                    $backUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
                }
            }
            $editUrl = '/admin/member/edit.php?id=' . $member['member_id'];
            if ($returnPage) {
                $editUrl .= '&page=' . $returnPage;
                if ($returnSort) {
                    $editUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
                }
            }
            ?>
            <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">← Back to List</a>
            <a href="<?php echo $editUrl; ?>" class="btn btn-primary">Edit Member</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

