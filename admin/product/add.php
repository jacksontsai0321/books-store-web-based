<?php
$pageTitle = 'Add Product';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

$product = [];
$errors = [];

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
            $uploadDir = __DIR__ . '/../../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate product ID first (same pattern as admin/member)
            $productId = generateSequentialId($pdo, 'P', 'product', 'product_id');
            
            // Double-check ID doesn't exist (extra safety - prevent duplicates)
            $maxAttempts = 10;
            $attempts = 0;
            while ($attempts < $maxAttempts) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM product WHERE product_id = ?");
                $checkStmt->execute([$productId]);
                $exists = $checkStmt->fetch()['count'] > 0;
                
                if (!$exists) {
                    break; // ID is available
                }
                
                // If ID exists, generate a new one
                $productId = generateSequentialId($pdo, 'P', 'product', 'product_id');
                $attempts++;
            }
            
            if ($attempts >= $maxAttempts) {
                throw new Exception('Unable to generate unique product ID after multiple attempts');
            }
            
            // Get admin ID for created_by
            $createdBy = $_SESSION['admin_id'] ?? null;
            
            // Insert product first (same pattern as admin/member - simple INSERT)
            $stmt = $pdo->prepare("INSERT INTO product (product_id, category_id, name, description, price, stock, created_by, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $productId,
                $product['category_id'],
                $product['name'],
                $product['description'],
                $product['price'],
                $product['stock'],
                $createdBy
            ]);
            
            // Handle multiple image uploads - process each image independently
            $uploadedCount = 0;
            $failedCount = 0;
            $uploadErrors = [];
            $uploadedFiles = [];
            
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $fileCount = count($files['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    $fileName = $files['name'][$i] ?? 'File ' . ($i + 1);
                    $fileError = false;
                    $errorMessage = '';
                    
                    // Skip if no file was uploaded for this index
                    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    
                    // Check for upload errors
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $errorMessage = $fileName . ': Upload error (code ' . $files['error'][$i] . ')';
                        $fileError = true;
                    }
                    
                    // Check file size (5MB) - only if no previous error
                    if (!$fileError && $files['size'][$i] > 5242880) {
                        $errorMessage = $fileName . ': File too large (max 5MB, your file: ' . round($files['size'][$i] / 1024 / 1024, 2) . 'MB)';
                        $fileError = true;
                    }
                    
                    // Check file extension
                    if (!$fileError) {
                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if (!in_array($extension, $allowedExtensions)) {
                            $errorMessage = $fileName . ': Invalid file type. Only JPEG, PNG, and GIF are allowed.';
                            $fileError = true;
                        }
                    }
                    
                    // Check file type using MIME type
                    if (!$fileError && isset($files['tmp_name'][$i]) && file_exists($files['tmp_name'][$i])) {
                        $fileType = mime_content_type($files['tmp_name'][$i]);
                        if (!in_array($fileType, $allowedTypes) && !in_array($files['type'][$i], $allowedTypes)) {
                            $errorMessage = $fileName . ': Invalid image format. Only JPEG, PNG, and GIF are allowed.';
                            $fileError = true;
                        }
                    }
                    
                    // If file has errors, skip it but continue with others
                    if ($fileError) {
                        $uploadErrors[] = $errorMessage;
                        $failedCount++;
                        continue; // Skip this file, continue with next
                    }
                    
                    // All validations passed, try to upload
                    try {
                        // Generate unique filename
                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $filename = 'product_' . $productId . '_' . time() . '_' . $i . '_' . rand(1000, 9999) . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                            // Add to product_photo table
                            $photoId = generateSequentialId($pdo, 'PH', 'product_photo', 'photo_id');
                            $stmt = $pdo->prepare("INSERT INTO product_photo (photo_id, product_id, filename) VALUES (?, ?, ?)");
                            $stmt->execute([$photoId, $productId, $filename]);
                            $uploadedCount++;
                            $uploadedFiles[] = $fileName;
                        } else {
                            $uploadErrors[] = $fileName . ': Failed to save file to server';
                            $failedCount++;
                        }
                    } catch (PDOException $e) {
                        // Delete file if database insert fails
                        if (isset($filepath) && file_exists($filepath)) {
                            unlink($filepath);
                        }
                        $uploadErrors[] = $fileName . ': Database error - ' . $e->getMessage();
                        $failedCount++;
                        error_log('Photo upload DB error: ' . $e->getMessage());
                    } catch (Exception $e) {
                        // Delete file if any other error occurs
                        if (isset($filepath) && file_exists($filepath)) {
                            unlink($filepath);
                        }
                        $uploadErrors[] = $fileName . ': Error - ' . $e->getMessage();
                        $failedCount++;
                        error_log('Photo upload error: ' . $e->getMessage());
                    }
                }
            }
            
            // Set success/error messages - product is always created, images are optional
            $message = 'Product added successfully!';
            if ($uploadedCount > 0) {
                $message .= ' ' . $uploadedCount . ' image(s) uploaded successfully.';
            }
            if ($failedCount > 0) {
                $message .= ' ' . $failedCount . ' image(s) failed to upload.';
            }
            
            $_SESSION['success'] = $message;
            
            // Show detailed errors if any
            if (!empty($uploadErrors)) {
                $_SESSION['upload_errors'] = $uploadErrors; // Store detailed errors separately
            }
            
            redirect('/admin/product/list.php');
        } catch (PDOException $e) {
            $errors[] = 'Failed to add product. Please try again.';
        }
    }
}
?>

<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Add New Product</h1>
        
        <?php 
        $formAction = '/admin/product/add.php';
        $submitText = 'Add Product';
        $cancelUrl = '/admin/product/list.php';
        include __DIR__ . '/../../includes/forms/product_form.php'; 
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>

