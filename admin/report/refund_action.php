<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/report/refunds.php');
}

$refundId = $_POST['refund_id'] ?? '';
$action = sanitize($_POST['action'] ?? '');
$refundId = sanitize($refundId);

if (empty($refundId) || !in_array($action, ['approve', 'reject'])) {
    redirect('/admin/report/refunds.php');
}

try {
    $pdo->beginTransaction();

    // Fetch refund record
    $stmt = $pdo->prepare("SELECT * FROM order_refund WHERE refund_id = ? FOR UPDATE");
    $stmt->execute([$refundId]);
    $refund = $stmt->fetch();
    if (!$refund) {
        $pdo->rollBack();
        redirect('/admin/report/refunds.php');
    }

    if ($refund['status'] !== 'Pending') {
        // Already processed
        $pdo->rollBack();
        redirect('/admin/report/refunds.php');
    }

    $now = date('Y-m-d H:i:s');
    // Save evidence filename (if any) to delete after commit
    $evidenceFile = $refund['evidence'] ?? null;

    if ($action === 'approve') {
        // Mark refund approved
        $stmt = $pdo->prepare("UPDATE order_refund SET status = 'Approved', admin_id = ?, processed_at = ? WHERE refund_id = ?");
        $stmt->execute([$_SESSION['admin_id'], $now, $refundId]);
        // Update order status to Refunded
        $stmt = $pdo->prepare("UPDATE order_header SET status = 'Refunded' WHERE order_id = ?");
        $stmt->execute([$refund['order_id']]);
    } else {
        // Reject
        $stmt = $pdo->prepare("UPDATE order_refund SET status = 'Rejected', admin_id = ?, processed_at = ? WHERE refund_id = ?");
        $stmt->execute([$_SESSION['admin_id'], $now, $refundId]);
        // Keep order status as is or mark 'Refund Rejected'
        $stmt = $pdo->prepare("UPDATE order_header SET status = 'Refund Rejected' WHERE order_id = ?");
        $stmt->execute([$refund['order_id']]);
    }

    // Clear evidence column so UI no longer references deleted file
    if (!empty($evidenceFile)) {
        try {
            $stmt = $pdo->prepare("UPDATE order_refund SET evidence = NULL WHERE refund_id = ?");
            $stmt->execute([$refundId]);
        } catch (PDOException $inner) {
            // Non-fatal - continue, we'll still attempt to delete the file
            error_log('Failed to clear evidence column for refund ' . $refundId . ': ' . $inner->getMessage());
        }
    }

    $pdo->commit();

    // After commit, attempt to delete the evidence file (non-fatal)
    if (!empty($evidenceFile)) {
        $uploadsDir = realpath(__DIR__ . '/../../uploads/refunds');
        if ($uploadsDir) {
            $safeName = basename($evidenceFile);
            $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;
            if (file_exists($filePath)) {
                if (!@unlink($filePath)) {
                    error_log('Failed to remove refund evidence file: ' . $filePath);
                }
            }
        }
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // proceed back to list with generic failure
}

redirect('/admin/report/refunds.php');
