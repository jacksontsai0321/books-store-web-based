<?php
$pageTitle = 'Request Refund';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$orderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
if (empty($orderId)) {
    $_SESSION['error'] = 'Order required.';
    redirect('/order/history.php');
}

// Verify ownership and status
$stmt = $pdo->prepare("SELECT * FROM order_header WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order || $order['member_id'] != $_SESSION['member_id']) {
    $_SESSION['error'] = 'Order not found.';
    redirect('/order/history.php');
}

// Allow refunds only for Shipped orders (member-initiated refund window)
if ($order['status'] !== 'Shipped') {
    $_SESSION['error'] = 'Refunds can only be requested while the order is marked as Shipped (please mark as received first).';
    redirect('/order/detail.php?id=' . urlencode($orderId));
}

// Prevent duplicate/overlapping refund requests for the same order
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_refund WHERE order_id = ? AND status != 'Rejected'");
    $stmt->execute([$orderId]);
    $cnt = $stmt->fetch()['cnt'] ?? 0;
    if ($cnt > 0) {
        $_SESSION['error'] = 'A refund request already exists for this order.';
        redirect('/order/detail.php?id=' . urlencode($orderId));
    }
} catch (PDOException $e) {
    // ignore - we'll still proceed, duplicate handling will be re-checked on POST
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = sanitize($_POST['reason'] ?? '');
    if (empty($reason) || strlen($reason) < 10) {
        $errors[] = 'Please provide a reason for refund (at least 10 characters).';
    }

    // Validate optional evidence upload
    $evidenceFilename = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['evidence'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Failed to upload evidence image.';
        } else {
            // Basic checks: size <= 3MB and image mime
            $maxSize = 3 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $errors[] = 'Evidence image must be 3MB or smaller.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only JPG, PNG, GIF or WEBP images are allowed for evidence.';
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Create refunds table if not present (includes evidence column)
            $pdo->exec("CREATE TABLE IF NOT EXISTS order_refund (
                refund_id VARCHAR(32) PRIMARY KEY,
                order_id VARCHAR(32) NOT NULL,
                member_id VARCHAR(32) NOT NULL,
                reason TEXT,
                evidence VARCHAR(255),
                status VARCHAR(32) DEFAULT 'Pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Ensure evidence column exists (safe alter)
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM order_refund LIKE 'evidence'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE order_refund ADD COLUMN evidence VARCHAR(255)");
                }
            } catch (PDOException $inner) {
                // ignore - not critical
            }

            $refundId = generateSequentialId($pdo, 'R', 'order_refund', 'refund_id');

            // If file present, move to uploads/refunds
            if (isset($file) && isset($allowed[$mime]) && $file['error'] === UPLOAD_ERR_OK) {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/../uploads/refunds/';
                @mkdir($uploadDir, 0755, true);
                $basename = $refundId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $uploadDir . $basename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $evidenceFilename = $basename;
                }
            }

            if ($evidenceFilename) {
                $stmt = $pdo->prepare("INSERT INTO order_refund (refund_id, order_id, member_id, reason, evidence) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$refundId, $orderId, $_SESSION['member_id'], $reason, $evidenceFilename]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO order_refund (refund_id, order_id, member_id, reason) VALUES (?, ?, ?, ?)");
                $stmt->execute([$refundId, $orderId, $_SESSION['member_id'], $reason]);
            }

            // Update order status to Refund Requested
            $stmt = $pdo->prepare("UPDATE order_header SET status = 'Refund Requested' WHERE order_id = ?");
            $stmt->execute([$orderId]);

            $_SESSION['success'] = 'Refund requested successfully. Our staff will review your request.';
            redirect('/order/detail.php?id=' . urlencode($orderId));
        } catch (PDOException $e) {
            $errors[] = 'Failed to submit refund request. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';
?>

<main>
    <div class="container">
        <h1 class="page-title">Request Refund</h1>

        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>

        <form method="POST" action="/order/request_refund.php?order_id=<?php echo urlencode($orderId); ?>" class="member-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="reason">Reason for Refund *</label>
                <textarea id="reason" name="reason" rows="6" required></textarea>
                <small>Please explain why you want a refund. Provide details to help with processing.</small>
            </div>

            <div class="form-group">
                <label for="evidence">Evidence</label>
                <input type="file" id="evidence" name="evidence" accept="image/*" required>
                <small class="help-text">Attach a photo (receipt, defect, etc.) to help speed up processing (Max 3MB, optional).</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary member-btn-primary">Submit Refund Request</button>
                <a href="/order/detail.php?id=<?php echo urlencode($orderId); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>