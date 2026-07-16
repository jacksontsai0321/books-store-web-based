<?php
$pageTitle = 'Edit Admin';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_super_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$adminId = isset($_GET['id']) ? $_GET['id'] : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';

$admin = getAdminById($pdo, $adminId);
$errors = [];

if (!$admin) {
    $_SESSION['error'] = 'Staff not found.';
    $redirectUrl = '/admin/admin/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

// Map column names for form
$admin['admin_name'] = $admin['full_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin['admin_name'] = sanitize($_POST['admin_name'] ?? '');
    $admin['email'] = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($admin['admin_name']) || !validateText($admin['admin_name'], 2)) {
        $errors[] = 'Staff name must be at least 2 characters';
    }
    
    if (empty($admin['email']) || !validateEmail($admin['email'])) {
        $errors[] = 'Valid email is required';
    }
    
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters if provided';
    }
    
    if (empty($errors)) {
        try {
            // Check if email is taken by another admin
            $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ? AND admin_id != ?");
            $stmt->execute([$admin['email'], $adminId]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already taken by another Staff';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $errors[] = 'Password must be at least 6 characters';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, username = ?, email = ?, password = ? WHERE admin_id = ?");
                        $stmt->execute([$admin['admin_name'], $admin['admin_name'], $admin['email'], $hashedPassword, $adminId]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, username = ?, email = ? WHERE admin_id = ?");
                    $stmt->execute([$admin['admin_name'], $admin['admin_name'], $admin['email'], $adminId]);
                }
                
                if (empty($errors)) {
                    $_SESSION['success'] = 'Staff updated successfully!';
                    
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
                    $redirectUrl = '/admin/admin/list.php';
                    if (!empty($params)) {
                        $redirectUrl .= '?' . http_build_query($params);
                    }
                    redirect($redirectUrl);
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Update failed. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Edit Staff</h1>
        
        <?php 
        $formAction = '/admin/admin/edit.php?id=' . $adminId;
        $submitText = 'Update Admin';
        $cancelUrl = '/admin/admin/list.php';
        include __DIR__ . '/../../includes/forms/admin_form.php'; 
        ?>
        
        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <p><strong>Note:</strong> Leave password blank to keep current password.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

