<?php
$pageTitle = 'Edit Category';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$categoryId = $_GET['id'] ?? '';
$returnPage = isset($_GET['page']) ? (int)$_GET['page'] : null;
$returnSort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';
$returnOrder = isset($_GET['order']) ? sanitize($_GET['order']) : '';

$category = getCategoryById($pdo, $categoryId);
$errors = [];

if (!$category) {
    $_SESSION['error'] = 'Category not found.';
    $redirectUrl = '/admin/category/list.php';
    if ($returnPage) {
        $redirectUrl .= '?page=' . $returnPage;
        if ($returnSort) {
            $redirectUrl .= '&sort=' . urlencode($returnSort) . '&order=' . urlencode($returnOrder);
        }
    }
    redirect($redirectUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category['category_name'] = sanitize($_POST['category_name'] ?? '');
    
    if (empty($category['category_name']) || !validateText($category['category_name'], 2)) {
        $errors[] = 'Category name must be at least 2 characters';
    }
    
    if (empty($errors)) {
        try {
            // Check if category name is taken by another category
            $stmt = $pdo->prepare("SELECT category_id FROM product_category WHERE category_name = ? AND category_id != ?");
            $stmt->execute([$category['category_name'], $categoryId]);
            if ($stmt->fetch()) {
                $errors[] = 'Category name already taken';
            } else {
                $stmt = $pdo->prepare("UPDATE product_category SET category_name = ? WHERE category_id = ?");
                $stmt->execute([$category['category_name'], $categoryId]);
                
                $_SESSION['success'] = 'Category updated successfully!';
                
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
                $redirectUrl = '/admin/category/list.php';
                if (!empty($params)) {
                    $redirectUrl .= '?' . http_build_query($params);
                }
                redirect($redirectUrl);
            }
        } catch (PDOException $e) {
            $errors[] = 'Update failed. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Edit Category</h1>
        
        <?php 
        $formAction = '/admin/category/edit.php?id=' . $categoryId;
        $submitText = 'Update Category';
        $cancelUrl = '/admin/category/list.php';
        include __DIR__ . '/../../includes/forms/category_form.php'; 
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

