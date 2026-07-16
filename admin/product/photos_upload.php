<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

$productId = isset($_POST['product_id']) ? sanitize($_POST['product_id']) : '';
$returnPage = isset($_POST['return_page']) ? (int)$_POST['return_page'] : null;
$returnSort = isset($_POST['return_sort']) ? sanitize($_POST['return_sort']) : '';
$returnOrder = isset($_POST['return_order']) ? sanitize($_POST['return_order']) : '';
$returnSearch = isset($_POST['return_search']) ? sanitize($_POST['return_search']) : '';

if (empty($productId)) {
    $_SESSION['error'] = 'Invalid product ID.';
    redirect('/admin/product/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $files = $_FILES['images'];
    $uploadDir = __DIR__ . '/../../uploads/products/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $uploadedCount = 0;
    $failedCount = 0;
    $errors = [];
    
    // Handle multiple file uploads
    $fileCount = count($files['name']);
    
    // Check if any files were selected
    if ($fileCount === 0 || ($fileCount === 1 && $files['error'][0] === UPLOAD_ERR_NO_FILE)) {
        $_SESSION['error'] = 'Please select at least one image to upload.';
        redirect('/admin/product/photos.php?id=' . $productId);
    }
    
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
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
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
            $errors[] = $errorMessage;
            $failedCount++;
            continue; // Skip this file, continue with next - don't affect other uploads
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
            } else {
                $errors[] = $fileName . ': Failed to save file to server';
                $failedCount++;
            }
        } catch (PDOException $e) {
            // Delete file if database insert fails
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            $errors[] = $fileName . ': Database error';
            $failedCount++;
            error_log('Photo upload DB error: ' . $e->getMessage());
        } catch (Exception $e) {
            // Delete file if any other error occurs
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            $errors[] = $fileName . ': Error - ' . $e->getMessage();
            $failedCount++;
            error_log('Photo upload error: ' . $e->getMessage());
        }
    }
    
    // Set success/error messages - show both success and errors separately
    if ($uploadedCount > 0) {
        $message = $uploadedCount . ' image(s) uploaded successfully!';
        if ($failedCount > 0) {
            $message .= ' (' . $failedCount . ' failed)';
        }
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = 'No images were uploaded.';
    }
    
    // Store detailed errors separately so they don't override success message
    if (!empty($errors)) {
        $_SESSION['upload_errors'] = $errors;
    }
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

