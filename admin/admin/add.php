<?php
$pageTitle = 'Add Admin';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_super_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$admin = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin['admin_name'] = sanitize($_POST['admin_name'] ?? '');
    $admin['email'] = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($admin['admin_name']) || !validateText($admin['admin_name'], 2)) {
        $errors[] = 'Staff name must be at least 2 characters';
    }
    
    if (empty($admin['email']) || !validateEmail($admin['email'])) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ?");
            $stmt->execute([$admin['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $adminId = generateSequentialId($pdo, 'A', 'admin', 'admin_id');
                $stmt = $pdo->prepare("INSERT INTO admin (admin_id, full_name, username, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$adminId, $admin['admin_name'], $admin['admin_name'], $admin['email'], $hashedPassword]);
                
                $_SESSION['success'] = 'Staff added successfully!';
                redirect('/admin/admin/list.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to add Staff. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Add New Staff</h1>
        
        <?php 
        $formAction = '/admin/admin/add.php';
        $submitText = 'Add Staff';
        $cancelUrl = '/admin/admin/list.php';
        include __DIR__ . '/../../includes/forms/admin_form.php'; 
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

