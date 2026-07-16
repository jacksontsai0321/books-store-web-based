<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

$photoId = isset($_GET['photo_id']) ? sanitize($_GET['photo_id']) : '';
$productId = isset($_GET['product_id']) ? sanitize($_GET['product_id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';
$returnSearch = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if (empty($photoId) || empty($productId)) {
    $_SESSION['error'] = 'Invalid photo or product ID.';
    $params = [];
    if ($returnPage) {
        $params['page'] = $returnPage;
    }
    if ($returnSort) {
        $params['sort'] = $returnSort;
        $params['order'] = $returnOrder;
    }
    if (!empty($returnSearch)) {
        $params['search'] = $returnSearch;
    }
    $redirectUrl = '/admin/product/photos.php?id=' . $productId;
    if (!empty($params)) {
        $redirectUrl .= '&' . http_build_query($params);
    }
    redirect($redirectUrl);
}

try {
    // Get photo info
    $stmt = $pdo->prepare("SELECT filename FROM product_photo WHERE photo_id = ? AND product_id = ?");
    $stmt->execute([$photoId, $productId]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        // Delete file
        $filePath = __DIR__ . '/../../uploads/products/' . $photo['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM product_photo WHERE photo_id = ? AND product_id = ?");
        $stmt->execute([$photoId, $productId]);
        
        $_SESSION['success'] = 'Image deleted successfully!';
    } else {
        $_SESSION['error'] = 'Photo not found.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete image: ' . $e->getMessage();
    error_log('Delete photo error: ' . $e->getMessage());
}

$redirectUrl = '/admin/product/photos.php?id=' . $productId;
$params = [];
if ($returnPage) {
    $params['page'] = $returnPage;
}
if ($returnSort) {
    $params['sort'] = $returnSort;
    $params['order'] = $returnOrder;
}
if (!empty($returnSearch)) {
    $params['search'] = $returnSearch;
}
if (!empty($params)) {
    $redirectUrl .= '&' . http_build_query($params);
}
redirect($redirectUrl);
?>

