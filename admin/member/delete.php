<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

$memberId = isset($_GET['id']) ? trim($_GET['id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';

if (empty($memberId)) {
    $_SESSION['error'] = 'Invalid member ID.';
    $redirectUrl = '/admin/member/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

try {
    // First check if member exists
    $stmt = $pdo->prepare("SELECT member_id, full_name FROM member WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    
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
    
    // Check if member has orders (prevent deletion if they have orders)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_header WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $orderCount = $stmt->fetch()['count'];
    
    if ($orderCount > 0) {
        // Member has orders: anonymize member data instead of deleting to preserve order history
        $anonymizedEmail = 'deleted+' . $memberId . '@example.invalid';
        $stmt = $pdo->prepare("UPDATE member SET full_name = ?, email = ?, phone = NULL, address = NULL, password = NULL, is_verified = 0 WHERE member_id = ?");
        $stmt->execute(['[Deleted User]', $anonymizedEmail, $memberId]);
        $_SESSION['success'] = 'Member had orders and has been anonymized successfully.';
    } else {
        // Safe to delete member (no dependent orders)
        $stmt = $pdo->prepare("DELETE FROM member WHERE member_id = ?");
        $stmt->execute([$memberId]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Member "' . htmlspecialchars($member['full_name']) . '" deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete member.';
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete member: ' . $e->getMessage();
    error_log('Member delete error: ' . $e->getMessage());
}

$redirectUrl = '/admin/member/list.php';
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

