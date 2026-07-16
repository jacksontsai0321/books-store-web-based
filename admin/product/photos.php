<?php
$pageTitle = 'Product Photos';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';
$returnSearch = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$product = getProductById($pdo, $productId);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
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
    $redirectUrl = '/admin/product/list.php';
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }
    redirect($redirectUrl);
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Product Photos - <?php echo htmlspecialchars($product['product_name']); ?></h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
            <div class="error-messages" style="margin-bottom: 1rem;">
                <div class="error-message" style="font-weight: bold; margin-bottom: 0.5rem;">Upload Errors:</div>
                <?php foreach ($_SESSION['upload_errors'] as $error): ?>
                    <div class="error-message" style="margin-left: 1rem; margin-bottom: 0.25rem;">• <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <?php unset($_SESSION['upload_errors']); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Get all photos for this product
        try {
            $stmt = $pdo->prepare("SELECT * FROM product_photo WHERE product_id = ? ORDER BY photo_id DESC");
            $stmt->execute([$productId]);
            $photos = $stmt->fetchAll();
        } catch (PDOException $e) {
            $photos = [];
        }
        ?>
        
        <div style="margin-bottom: 2rem;">
            <h3>Product Photos (<?php echo count($photos); ?>)</h3>
            <?php if (!empty($photos)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin: 1rem 0;">
                    <?php foreach ($photos as $photo): ?>
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%;">
                            <div style="width: 100%; height: 200px; overflow: hidden; border-radius: 4px; margin-bottom: 0.75rem; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                                <img src="/uploads/products/<?php echo htmlspecialchars($photo['filename']); ?>" 
                                     alt="Product Photo" 
                                     style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 4px;">
                            </div>
                            <div style="margin-top: auto;">
                                <?php
                                $deleteParams = 'photo_id=' . htmlspecialchars($photo['photo_id']) . '&product_id=' . htmlspecialchars($productId);
                                if ($returnPage) {
                                    $deleteParams .= '&page=' . $returnPage;
                                }
                                if ($returnSort) {
                                    $deleteParams .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
                                }
                                if (!empty($returnSearch)) {
                                    $deleteParams .= '&search=' . urlencode($returnSearch);
                                }
                                ?>
                                <a href="/admin/product/photos_delete.php?<?php echo $deleteParams; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this photo?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; padding: 2rem; text-align: center; background: #f9f9f9; border-radius: 4px;">No photos uploaded yet. Upload photos below.</p>
            <?php endif; ?>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3>Upload New Photos</h3>
            <form method="POST" action="/admin/product/photos_upload.php" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
                <?php if ($returnPage): ?>
                    <input type="hidden" name="return_page" value="<?php echo $returnPage; ?>">
                    <?php if ($returnSort): ?>
                        <input type="hidden" name="return_sort" value="<?php echo htmlspecialchars($returnSort); ?>">
                        <input type="hidden" name="return_order" value="<?php echo htmlspecialchars($returnOrder); ?>">
                    <?php endif; ?>
                    <?php if (!empty($returnSearch)): ?>
                        <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($returnSearch); ?>">
                    <?php endif; ?>
                <?php endif; ?>
                <div class="form-group">
                    <label for="images">Select Images (Max 5MB each) *</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                    <small>You can select multiple images at once. Allowed formats: JPEG, PNG, GIF</small>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Upload Images</button>
                    <?php
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
                    $backUrl = '/admin/product/list.php';
                    if (!empty($params)) {
                        $backUrl .= '?' . http_build_query($params);
                    }
                    ?>
                    <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">Back to Products</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

