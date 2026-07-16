<?php
$pageTitle = 'Refund Requests';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/_admin_head.php';
require_once __DIR__ . '/../../includes/_admin_header.php';
require_once __DIR__ . '/../../includes/_admin_sidebar.php';

// Ensure table exists (safe on first-run)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_refund (
        refund_id VARCHAR(64) PRIMARY KEY,
        order_id VARCHAR(64) NOT NULL,
        member_id VARCHAR(64) NOT NULL,
        reason TEXT,
        evidence VARCHAR(255),
        status VARCHAR(32) DEFAULT 'Pending',
        admin_note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        processed_at DATETIME,
        admin_id VARCHAR(64)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure 'evidence' column exists (for older installations)
    try {
        $col = $pdo->query("SHOW COLUMNS FROM order_refund LIKE 'evidence'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE order_refund ADD COLUMN evidence VARCHAR(255)");
        }
    } catch (PDOException $inner) {
        // ignore
    }
} catch (PDOException $e) {
    // ignore - display graceful error later
}

// Fetch refunds (allow optional filter by order_id)
$refunds = [];
try {
    $filterOrderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
    if (!empty($filterOrderId)) {
        $stmt = $pdo->prepare("SELECT r.*, o.status as order_status, m.full_name as member_name, o.total_amount
                        FROM order_refund r
                        LEFT JOIN order_header o ON r.order_id = o.order_id
                        LEFT JOIN member m ON r.member_id = m.member_id
                        WHERE r.order_id = ?
                        ORDER BY r.created_at DESC");
        $stmt->execute([$filterOrderId]);
    } else {
        $stmt = $pdo->query("SELECT r.*, o.status as order_status, m.full_name as member_name, o.total_amount
                        FROM order_refund r
                        LEFT JOIN order_header o ON r.order_id = o.order_id
                        LEFT JOIN member m ON r.member_id = m.member_id
                        ORDER BY r.created_at DESC");
    }
    $refunds = $stmt->fetchAll();
} catch (PDOException $e) {
    $refunds = [];
}
?>

<div class="admin-main">
    <h1 class="page-title">Refund Requests</h1>
    <?php if (empty($refunds)): ?>
        <div class="info-message">No refund requests at this time.</div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Refund ID</th>
                    <th>Order ID</th>
                    <th>Member</th>
                    <th>Order Status</th>
                    <th>Reason</th>
                    <th>Evidence</th>
                    <th>Request Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($refunds as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['refund_id']); ?></td>
                        <td><a href="/admin/order/view.php?id=<?php echo urlencode($r['order_id']); ?>"><?php echo htmlspecialchars($r['order_id']); ?></a></td>
                        <td><?php echo htmlspecialchars($r['member_name'] ?? $r['member_id']); ?></td>
                        <td><?php echo htmlspecialchars($r['order_status']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($r['reason'])); ?></td>
                        <td>
                            <?php if (!empty($r['evidence'])): ?>
                                <?php $path = '/uploads/refunds/' . $r['evidence']; ?>
                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($path); ?>" alt="evidence" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:4px;">
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td>
                            <?php if ($r['status'] === 'Pending'): ?>
                                <form method="post" action="/admin/report/refund_action.php" style="display:inline-block">
                                    <input type="hidden" name="refund_id" value="<?php echo htmlspecialchars($r['refund_id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-primary" type="submit">Approve</button>
                                </form>
                                <form method="post" action="/admin/report/refund_action.php" style="display:inline-block; margin-left:6px">
                                    <input type="hidden" name="refund_id" value="<?php echo htmlspecialchars($r['refund_id']); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn" type="submit">Reject</button>
                                </form>
                            <?php else: ?>
                                <em>Processed</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/_admin_footer.php'; ?>