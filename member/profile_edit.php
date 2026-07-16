<?php
$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$member = getMemberById($pdo, $_SESSION['member_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member['member_name'] = sanitize($_POST['member_name'] ?? '');
    $member['email'] = sanitize($_POST['email'] ?? '');
    $member['phone'] = sanitize($_POST['phone'] ?? '');
    $member['address'] = sanitize($_POST['address'] ?? '');
    
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
            $stmt->execute([$member['email'], $_SESSION['member_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already taken by another account';
            } else {
                $stmt = $pdo->prepare("UPDATE member SET full_name = ?, email = ?, phone = ?, address = ? WHERE member_id = ?");
                $stmt->execute([
                    $member['member_name'],
                    $member['email'],
                    $member['phone'],
                    $member['address'],
                    $_SESSION['member_id']
                ]);
                
                $_SESSION['member_name'] = $member['member_name'];
                $_SESSION['success'] = 'Profile updated successfully!';
                redirect('/member/profile.php');
            }
        } catch (PDOException $e) {
            // Log the exception for debugging
            error_log('[profile_edit] Update failed for member_id=' . ($_SESSION['member_id'] ?? 'null') . ' error: ' . $e->getMessage());
            $errors[] = 'Update failed. Please try again.';
            // When explicitly requested, expose debug details on the page
            if (isset($_GET['debug_profile']) && $_GET['debug_profile'] === '1') {
                $errors[] = 'DEBUG: ' . $e->getMessage();
            }
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Edit Profile</h1>
        
        <?php 
        $formAction = '/member/profile_edit.php';
        $submitText = 'Update Profile';
        $cancelUrl = '/member/profile.php';
        include __DIR__ . '/../includes/forms/member_form.php'; 
        ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

