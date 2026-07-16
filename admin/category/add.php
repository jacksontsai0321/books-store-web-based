<?php
$pageTitle = 'Add Category';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$category = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category['category_name'] = sanitize($_POST['category_name'] ?? '');
    
    if (empty($category['category_name']) || !validateText($category['category_name'], 2)) {
        $errors[] = 'Category name must be at least 2 characters';
    }
    
    if (empty($errors)) {
        try {
            // Check if category name already exists
            $stmt = $pdo->prepare("SELECT category_id FROM product_category WHERE category_name = ?");
            $stmt->execute([$category['category_name']]);
            if ($stmt->fetch()) {
                $errors[] = 'Category name already exists';
            } else {
                $categoryId = generateSequentialId($pdo, 'C', 'product_category', 'category_id');
                $stmt = $pdo->prepare("INSERT INTO product_category (category_id, category_name) VALUES (?, ?)");
                $stmt->execute([$categoryId, $category['category_name']]);
                
                $_SESSION['success'] = 'Category added successfully!';
                redirect('/admin/category/list.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Failed to add category. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Add New Category</h1>
        
        <?php 
        $formAction = '/admin/category/add.php';
        $submitText = 'Add Category';
        $cancelUrl = '/admin/category/list.php';
        include __DIR__ . '/../../includes/forms/category_form.php'; 
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

