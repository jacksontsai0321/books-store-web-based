<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';
$returnSearch = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if (empty($productId)) {
    $_SESSION['error'] = 'Invalid product ID.';
    redirect('/admin/product/list.php');
}

try {
    // Get product to delete images
    $product = getProductById($pdo, $productId);
    
    if ($product) {
        // Delete product photos from database and files
        $stmt = $pdo->prepare("SELECT filename FROM product_photo WHERE product_id = ?");
        $stmt->execute([$productId]);
        $photos = $stmt->fetchAll();
        
        foreach ($photos as $photo) {
            $filePath = __DIR__ . '/../../uploads/products/' . $photo['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete photos from database
        $stmt = $pdo->prepare("DELETE FROM product_photo WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Product deleted successfully!';
        } else {
            $_SESSION['error'] = 'Product not found.';
        }
    } else {
        $_SESSION['error'] = 'Product not found.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
    error_log('Delete product error: ' . $e->getMessage());
}

redirect('/admin/product/list.php');
?>

