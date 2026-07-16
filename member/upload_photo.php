<?php
$pageTitle = 'Upload Photo';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
    } elseif ($file['size'] > 2097152) { // 2MB
        $errors[] = 'File size must be less than 2MB';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Only JPEG, PNG, and GIF images are allowed';
        } else {
            $uploadDir = __DIR__ . '/../uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'member_' . $_SESSION['member_id'] . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $relativePath = '/uploads/profile/' . $filename;
                
                // Delete old photo if exists
                $stmt = $pdo->prepare("SELECT photo FROM member WHERE member_id = ?");
                $stmt->execute([$_SESSION['member_id']]);
                $member = $stmt->fetch();
                if (!empty($member['photo']) && file_exists(__DIR__ . '/..' . $member['photo'])) {
                    unlink(__DIR__ . '/..' . $member['photo']);
                }
                
                $stmt = $pdo->prepare("UPDATE member SET photo = ? WHERE member_id = ?");
                $stmt->execute([$relativePath, $_SESSION['member_id']]);
                
                $_SESSION['success'] = 'Photo uploaded successfully!';
                redirect('/member/profile.php');
            } else {
                $errors[] = 'Failed to upload file';
            }
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Upload Profile Photo</h1>
        
        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>
        
        <form method="POST" action="/member/upload_photo.php" enctype="multipart/form-data" class="member-form">
            <div class="form-group">
                <label for="photo">Select Photo (Max 2MB) *</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
                <small>Allowed formats: JPEG, PNG, GIF</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary member-btn-primary">Upload Photo</button>
                <a href="/member/profile.php" class="btn btn-secondary member-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script src="/assets/js/member.js"></script>
<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

