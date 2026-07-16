<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$productId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$product = getProductById($pdo, $productId);
$errors = [];

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect('/admin/product/list.php');
}

// Get categories
try {
    $stmt = $pdo->query("SELECT * FROM product_category ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log('Error fetching categories: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product['name'] = sanitize($_POST['product_name'] ?? '');
    $product['category_id'] = sanitize($_POST['category_id'] ?? '');
    $product['description'] = sanitize($_POST['description'] ?? '');
    $product['price'] = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $product['stock'] = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0;
    
    if (empty($product['name']) || !validateText($product['name'], 2)) {
        $errors[] = 'Product name must be at least 2 characters';
    }
    
    if (empty($product['category_id'])) {
        $errors[] = 'Category is required';
    }
    
    if ($product['price'] <= 0) {
        $errors[] = 'Price must be greater than 0';
    }
    
    if ($product['stock'] < 0) {
        $errors[] = 'Stock quantity cannot be negative';
    }
    
    if (empty($errors)) {
        try {
            // Hidden flag handling (add column if missing)
            $product['is_hidden'] = isset($_POST['is_hidden']) ? 1 : 0;
            try {
                $pdo->query("ALTER TABLE product ADD COLUMN IF NOT EXISTS is_hidden TINYINT(1) NOT NULL DEFAULT 0");
            } catch (PDOException $inner) {
                // Some MySQL versions don't support IF NOT EXISTS - try a safe workaround
                try {
                    $cols = $pdo->query("SHOW COLUMNS FROM product LIKE 'is_hidden'")->fetchAll();
                    if (empty($cols)) {
                        $pdo->query("ALTER TABLE product ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0");
                    }
                } catch (Exception $e) {
                    // ignore - we'll fallback without column
                }
            }

            $imagePath = $product['image_path'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $errors[] = 'Only JPEG, PNG, and GIF images are allowed';
                } elseif ($file['size'] > 5242880) { // 5MB
                    $errors[] = 'File size must be less than 5MB';
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/products/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Delete old image
                    if (!empty($imagePath) && file_exists(__DIR__ . '/../..' . $imagePath)) {
                        unlink(__DIR__ . '/../..' . $imagePath);
                    }
                    
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $imagePath = '/uploads/products/' . $filename;
                    } else {
                        $errors[] = 'Failed to upload image';
                    }
                }
            }
            
            if (empty($errors)) {
                // Get admin ID for updated_by
                $updatedBy = $_SESSION['admin_id'] ?? null;
                
                // Update product with updated_by
                // Include is_hidden column if present
                $updateSql = "UPDATE product SET category_id = ?, name = ?, description = ?, price = ?, stock = ?";
                $params = [
                    $product['category_id'],
                    $product['name'],
                    $product['description'],
                    $product['price'],
                    $product['stock']
                ];
                // Try to append is_hidden if column exists
                try {
                    $colCheck = $pdo->query("SHOW COLUMNS FROM product LIKE 'is_hidden'")->fetchAll();
                    if (!empty($colCheck)) {
                        $updateSql .= ", is_hidden = ?";
                        $params[] = $product['is_hidden'];
                    }
                } catch (Exception $e) {
                    // ignore
                }
                $updateSql .= ", updated_by = ? WHERE product_id = ?";
                $params[] = $updatedBy;
                $params[] = $productId;

                $stmt = $pdo->prepare($updateSql);
                $stmt->execute($params);
                
                // If new image was uploaded, add it to product_photo table
                if (!empty($imagePath) && strpos($imagePath, '/uploads/products/') !== false) {
                    $filename = basename($imagePath);
                    // Check if photo already exists for this product
                    $stmt = $pdo->prepare("SELECT photo_id FROM product_photo WHERE product_id = ? AND filename = ?");
                    $stmt->execute([$productId, $filename]);
                    if (!$stmt->fetch()) {
                        $photoId = generateSequentialId($pdo, 'PH', 'product_photo', 'photo_id');
                        $stmt = $pdo->prepare("INSERT INTO product_photo (photo_id, product_id, filename) VALUES (?, ?, ?)");
                        $stmt->execute([$photoId, $productId, $filename]);
                    }
                }
                
                $_SESSION['success'] = 'Product updated successfully!';
                
                // Preserve page number, sort, and search when redirecting back
                $params = [];
                if (isset($_GET['page'])) {
                    $params['page'] = (int)$_GET['page'];
                }
                if (isset($_GET['sort']) && isset($_GET['order'])) {
                    $params['sort'] = $_GET['sort'];
                    $params['order'] = $_GET['order'];
                }
                if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                    $params['search'] = trim($_GET['search']);
                }
                $redirectUrl = '/admin/product/list.php';
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
        <h1 class="admin-page-title">Edit Product</h1>
        
        <?php 
        $formAction = '/admin/product/edit.php?id=' . $productId;
        $submitText = 'Update Product';
        $cancelUrl = '/admin/product/list.php';
        include __DIR__ . '/../../includes/forms/product_form.php'; 
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

