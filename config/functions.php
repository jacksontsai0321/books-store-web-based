<?php
/**
 * Common Utility Functions
 * Reusable functions for validation, formatting, and database operations
 */

require_once __DIR__ . '/db.php';

// SMTP configuration (provided by user)
const SMTP_CONFIG = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'jacksontjs-wm23@student.tarc.edu.my',
    'password' => 'mdso nkzb cavj qzbb',
    'from_email' => 'jacksontjs-wm23@student.tarc.edu.my',
    'from_name' => 'Bookstore',
];

if (!defined('SUPER_ADMIN_ID')) {
    define('SUPER_ADMIN_ID', 'A0001');
}

// Google Maps support removed (API key and client-side Places autocomplete were removed per project configuration).

/**
 * Determine if current session belongs to the super admin (SUPER_ADMIN_ID)
 */
function isSuperAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    
    if (!empty($_SESSION['admin_role'])) {
        return $_SESSION['admin_role'] === 'admin';
    }
    
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] === SUPER_ADMIN_ID;
}

/**
 * Generate unique ID
 */
function generateId($prefix = '') {
    return $prefix . uniqid() . '_' . time();
}

/**
 * Generate sequential ID (e.g., M0001, M0002, A0001, A0002)
 */
function generateSequentialId($pdo, $prefix, $tableName, $idColumn) {
    try {
        // Get all IDs with this prefix - use BINARY for case-sensitive matching
        $stmt = $pdo->prepare("SELECT $idColumn FROM $tableName WHERE $idColumn LIKE ? ORDER BY $idColumn DESC");
        $likePattern = $prefix . '%';
        $stmt->execute([$likePattern]);
        $results = $stmt->fetchAll();
        
        $maxNumber = 0;
        $prefixLength = strlen($prefix);
        
        foreach ($results as $row) {
            $id = trim($row[$idColumn] ?? '');
            if (empty($id) || strlen($id) <= $prefixLength) {
                continue;
            }
            
            // Extract number part (remove prefix)
            $numberPart = substr($id, $prefixLength);
            // Remove any non-numeric characters and convert to int
            $numberPart = preg_replace('/[^0-9]/', '', $numberPart);
            if (!empty($numberPart)) {
                $number = (int)$numberPart;
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }
        
        // Increment for next ID
        $nextNumber = $maxNumber + 1;
        
        // Format with prefix and zero-padding (4 digits: 0001, 0002, etc.)
        $newId = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Double-check that this ID doesn't already exist (safety check)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM $tableName WHERE $idColumn = ?");
        $checkStmt->execute([$newId]);
        $exists = $checkStmt->fetch()['count'] > 0;
        
        if ($exists) {
            // If ID exists, try next number
            $nextNumber++;
            $newId = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }
        
        return $newId;
    } catch (PDOException $e) {
        // Fallback to random if query fails
        error_log('Sequential ID generation failed: ' . $e->getMessage());
        return $prefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate text (non-empty, min length)
 */
function validateText($text, $minLength = 1, $maxLength = 255) {
    $text = trim($text);
    return strlen($text) >= $minLength && strlen($text) <= $maxLength;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    // Ensure no output before redirect
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: $url");
    exit();
}

/**
 * Send email via SMTP (LOGIN auth, STARTTLS).
 * Returns true on success, false on failure.
 */
function sendEmail($to, $subject, $message) {
    $cfg = SMTP_CONFIG;
    $host = $cfg['host'];
    $port = $cfg['port'];
    $username = $cfg['username'];
    $password = $cfg['password'];
    $fromEmail = $cfg['from_email'];
    $fromName = $cfg['from_name'];

    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 10);

    $read = function() use ($socket) { return fgets($socket, 515); };
    $cmd = function($line) use ($socket) { fwrite($socket, $line . "\r\n"); };

    $read(); // banner
    $cmd("EHLO localhost");
    $readUntilTls = function() use ($read) {
        $line = '';
        while ($chunk = $read()) {
            $line .= $chunk;
            if (strpos($chunk, 'STARTTLS') !== false || substr($chunk, 3, 1) !== '-') {
                if (substr($chunk, 3, 1) !== '-') break;
            }
            if (substr($chunk, 3, 1) !== '-') break;
        }
        return $line;
    };
    $readUntilTls();

    $cmd("STARTTLS");
    $tlsResp = $read();
    if (strpos($tlsResp, '220') !== 0) {
        error_log("SMTP STARTTLS failed: $tlsResp");
        fclose($socket);
        return false;
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        error_log("SMTP TLS negotiation failed");
        fclose($socket);
        return false;
    }

    $cmd("EHLO localhost");
    // consume EHLO response
    while (($line = $read()) && substr($line, 3, 1) === '-') {}

    $cmd("AUTH LOGIN");
    $read();
    $cmd(base64_encode($username));
    $read();
    $cmd(base64_encode($password));
    $authResp = $read();
    if (strpos($authResp, '235') !== 0) {
        error_log("SMTP AUTH failed: $authResp");
        fclose($socket);
        return false;
    }

    $cmd("MAIL FROM:<{$fromEmail}>");
    $read();
    $cmd("RCPT TO:<{$to}>");
    $read();
    $cmd("DATA");
    $read();

    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";

    $cmd($headers . "\r\n" . $message . "\r\n.");
    $dataResp = $read();
    if (strpos($dataResp, '250') !== 0) {
        error_log("SMTP DATA failed: $dataResp");
        fclose($socket);
        return false;
    }

    $cmd("QUIT");
    fclose($socket);
    return true;
}

/**
 * Render error messages
 */
function renderErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="error-messages">';
        foreach ($errors as $error) {
            echo '<div class="error-message">' . htmlspecialchars($error) . '</div>';
        }
        echo '</div>';
    }
}

/**
 * Format price
 */
function formatPrice($price) {
    return 'RM ' . number_format($price, 2);
}

/**
 * Get member by ID
 */
function getMemberById($pdo, $memberId) {
    $stmt = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    // Map column names for compatibility
    if ($member) {
        $member['member_name'] = $member['full_name'] ?? '';
        $member['photo_path'] = $member['photo'] ?? '';
    }
    return $member;
}

/**
 * Get admin by ID
 */
function getAdminById($pdo, $adminId) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    // Map column names for compatibility
    if ($admin) {
        $admin['admin_name'] = $admin['full_name'] ?? '';
    }
    return $admin;
}

/**
 * Get product by ID (with first photo)
 */
function getProductById($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name,
                          (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                          FROM product p 
                          LEFT JOIN product_category c ON p.category_id = c.category_id 
                          WHERE p.product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    // Map column names for compatibility
    if ($product) {
        $product['product_name'] = $product['name'] ?? '';
        $product['stock_quantity'] = $product['stock'] ?? 0;
        if (!empty($product['image_path'])) {
            $product['image_path'] = '/uploads/products/' . $product['image_path'];
        }
    }
    return $product;
}

/**
 * Get category by ID
 */
function getCategoryById($pdo, $categoryId) {
    $stmt = $pdo->prepare("SELECT * FROM product_category WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    return $stmt->fetch();
}

/**
 * Update product stock
 */
function updateStock($pdo, $productId, $quantity) {
    $stmt = $pdo->prepare("UPDATE product SET stock = stock - ? WHERE product_id = ?");
    return $stmt->execute([$quantity, $productId]);
}

/**
 * Get cart items for member (with product photos)
 */
function getCartItems($pdo, $memberId) {
    $stmt = $pdo->prepare("SELECT c.*, p.name as product_name, p.price, p.stock,
                          (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                          FROM shopping_cart c 
                          JOIN product p ON c.product_id = p.product_id 
                          WHERE c.member_id = ? 
                          ORDER BY c.cart_id DESC");
    $stmt->execute([$memberId]);
    $items = $stmt->fetchAll();
    // Map column names for compatibility
    foreach ($items as &$item) {
        $item['stock_quantity'] = $item['stock'] ?? 0;
        if (!empty($item['image_path'])) {
            $item['image_path'] = '/uploads/products/' . $item['image_path'];
        }
    }
    return $items;
}

/**
 * Get order details
 */
function getOrderDetails($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT o.*, m.full_name as member_name, m.email, m.phone, m.address 
                          FROM order_header o 
                          JOIN member m ON o.member_id = m.member_id 
                          WHERE o.order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    // Map column names for compatibility
    if ($order) {
        $order['created_at'] = $order['order_date'] ?? '';
        // Use member address as shipping address if order_header doesn't have shipping_address
        if (empty($order['shipping_address'])) {
            $order['shipping_address'] = $order['address'] ?? '';
        }

        // Auto-transition: if order was marked as 'Shipped' and shipped_at >= 7 days ago, update to Completed
        try {
            if (isset($order['status']) && $order['status'] === 'Shipped') {
                // shipped_at may be NULL or missing; handle gracefully
                if (!empty($order['shipped_at'])) {
                    $shippedTs = strtotime($order['shipped_at']);
                    if ($shippedTs !== false && ($shippedTs + 7*24*60*60) <= time()) {
                        // perform an update to Completed
                        $stmt2 = $pdo->prepare("UPDATE order_header SET status = 'Completed' WHERE order_id = ?");
                        $stmt2->execute([$orderId]);
                        // reflect change in returned array
                        $order['status'] = 'Completed';
                    }
                }
            }
        } catch (Exception $e) {
            // Non-fatal - ignore
        }
    }
    return $order;
}

/**
 * Get order items (with product photos)
 */
function getOrderItems($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT od.*, p.name as product_name,
                          (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                          FROM order_detail od 
                          JOIN product p ON od.product_id = p.product_id 
                          WHERE od.order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    // Map column names for compatibility
    foreach ($items as &$item) {
        if (!empty($item['image_path'])) {
            $item['image_path'] = '/uploads/products/' . $item['image_path'];
        }
    }
    return $items;
}

/**
 * Add a product review by member
 */
function addReview($pdo, $memberId, $productId, $rating, $title, $content) {
    try {
        $reviewId = generateSequentialId($pdo, 'R', 'review', 'review_id');
        $stmt = $pdo->prepare("INSERT INTO review (review_id, product_id, member_id, rating, title, content, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$reviewId, $productId, $memberId, $rating, $title, $content]);
    } catch (PDOException $e) {
        // If table missing, attempt to create it and retry once
        if ($e->getCode() === '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS review (
                    review_id VARCHAR(20) PRIMARY KEY,
                    product_id VARCHAR(20) NOT NULL,
                    member_id VARCHAR(20) NOT NULL,
                    rating TINYINT NOT NULL,
                    title VARCHAR(255) DEFAULT NULL,
                    content TEXT NOT NULL,
                    admin_reply TEXT DEFAULT NULL,
                    replied_by VARCHAR(20) DEFAULT NULL,
                    replied_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_review_member_product (member_id, product_id),
                    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
                    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
                    FOREIGN KEY (replied_by) REFERENCES admin(admin_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $reviewId = generateSequentialId($pdo, 'R', 'review', 'review_id');
                $stmt = $pdo->prepare("INSERT INTO review (review_id, product_id, member_id, rating, title, content, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                return $stmt->execute([$reviewId, $productId, $memberId, $rating, $title, $content]);
            } catch (PDOException $e2) {
                throw $e2;
            }
        }
        throw $e;
    }
}

/**
 * Get all reviews (admin)
 */
function getAllReviews($pdo) {
    $stmt = $pdo->prepare("SELECT r.*, p.name as product_name, m.full_name as member_name, a.full_name as admin_name FROM review r LEFT JOIN product p ON r.product_id = p.product_id LEFT JOIN member m ON r.member_id = m.member_id LEFT JOIN admin a ON r.replied_by = a.admin_id ORDER BY r.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Reply to a review (admin)
 */
function replyToReview($pdo, $reviewId, $adminId, $reply) {
    $stmt = $pdo->prepare("UPDATE review SET admin_reply = ?, replied_by = ?, replied_at = NOW() WHERE review_id = ?");
    return $stmt->execute([$reply, $adminId, $reviewId]);
}

/**
 * Delete a review
 */
function deleteReview($pdo, $reviewId) {
    $stmt = $pdo->prepare("DELETE FROM review WHERE review_id = ?");
    return $stmt->execute([$reviewId]);
}

/**
 * Check if member already reviewed a product
 */
function hasMemberReviewedProduct($pdo, $memberId, $productId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM review WHERE member_id = ? AND product_id = ?");
    $stmt->execute([$memberId, $productId]);
    return (int)$stmt->fetch()['cnt'] > 0;
}

/**
 * Check if member has purchased the product in a Shipped or Completed order
 * (Used to determine review eligibility once an item is received)
 */
function memberHasPurchasedProduct($pdo, $memberId, $productId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_detail od JOIN order_header oh ON od.order_id = oh.order_id WHERE oh.member_id = ? AND od.product_id = ? AND oh.status IN ('Shipped','Completed')");
    $stmt->execute([$memberId, $productId]);
    return (int)$stmt->fetch()['cnt'] > 0;
}

/**
 * Get reviews for a product (with member name)
 */
function getReviewsByProduct($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT r.*, m.full_name as member_name, a.full_name as admin_name FROM review r JOIN member m ON r.member_id = m.member_id LEFT JOIN admin a ON r.replied_by = a.admin_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/**
 * Calculate cart total
 */
function calculateCartTotal($pdo, $memberId) {
    $items = getCartItems($pdo, $memberId);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Render pagination controls
 */
function renderPagination($currentPage, $totalPages, $baseUrl, $additionalParams = [], $position = 'bottom') {
    if ($totalPages <= 1) {
        return '';
    }
    
    // Build query string from additional params
    $queryParams = http_build_query($additionalParams);
    $separator = !empty($queryParams) ? '&' : '';
    
    // Different styles for top vs bottom
    if ($position === 'top') {
        $html = '<div class="pagination pagination-top" style="display: flex; justify-content: flex-end; align-items: center; gap: 0.25rem; margin-bottom: 1rem;">';
        $btnClass = 'btn btn-sm btn-secondary';
        $textStyle = 'padding: 0 0.5rem; font-size: 0.9rem;';
    } else {
        $html = '<div class="pagination pagination-bottom" style="margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">';
        $btnClass = 'btn btn-secondary';
        $textStyle = 'padding: 0 1rem;';
    }
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<a href="' . $baseUrl . '?page=' . $prevPage . $separator . $queryParams . '" class="' . $btnClass . '">Prev</a>';
    } else {
        $html .= '<span class="' . $btnClass . '" style="opacity: 0.5; cursor: not-allowed;">Prev</span>';
    }
    
    // Page numbers
    $html .= '<span style="' . $textStyle . '">Page ' . $currentPage . ' of ' . $totalPages . '</span>';
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<a href="' . $baseUrl . '?page=' . $nextPage . $separator . $queryParams . '" class="' . $btnClass . '">Next</a>';
    } else {
        $html .= '<span class="' . $btnClass . '" style="opacity: 0.5; cursor: not-allowed;">Next</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get current page number from URL or return default
 */
function getCurrentPage() {
    return isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
}

/**
 * Build URL with page parameter preserved
 */
function buildUrlWithPage($baseUrl, $page = null, $additionalParams = []) {
    if ($page === null) {
        $page = getCurrentPage();
    }
    $params = array_merge(['page' => $page], $additionalParams);
    return $baseUrl . '?' . http_build_query($params);
}
?>

