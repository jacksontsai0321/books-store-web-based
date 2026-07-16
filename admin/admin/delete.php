<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_super_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

$adminId = isset($_GET['id']) ? trim($_GET['id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';

if (empty($adminId)) {
    $_SESSION['error'] = 'Invalid Staff ID.';
    $redirectUrl = '/admin/admin/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

// Prevent deleting own account
if ($adminId == $_SESSION['admin_id']) {
    $_SESSION['error'] = 'Cannot delete your own account.';
    $redirectUrl = '/admin/admin/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

try {
    // First check if admin exists
    $stmt = $pdo->prepare("SELECT admin_id, full_name FROM admin WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
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
    
    // Delete admin
    $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Staff "' . htmlspecialchars($admin['full_name']) . '" deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete Staff.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete Staff: ' . $e->getMessage();
    error_log('Staff delete error: ' . $e->getMessage());
}

$redirectUrl = '/admin/admin/list.php';
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
if (!empty($params)) {
    $redirectUrl .= '?' . http_build_query($params);
}
redirect($redirectUrl);
?>

