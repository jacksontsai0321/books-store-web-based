<?php
require_once __DIR__ . '/../../config/admin_guard.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/db.php';

$reviewId = $_GET['id'] ?? null;
if (!$reviewId) {
    header('Location: /admin/review/list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, p.name as product_name, m.full_name as member_name FROM review r LEFT JOIN product p ON r.product_id = p.product_id LEFT JOIN member m ON r.member_id = m.member_id WHERE r.review_id = ?");
$stmt->execute([$reviewId]);
$review = $stmt->fetch();
if (!$review) {
    header('Location: /admin/review/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = $_POST['admin_reply'] ?? '';
    replyToReview($pdo, $reviewId, $_SESSION['admin_id'], $reply);
    header('Location: /admin/review/list.php');
    exit;
}

include_once __DIR__ . '/../../includes/_admin_head.php';
include_once __DIR__ . '/../../includes/_admin_header.php';
include_once __DIR__ . '/../../includes/_admin_sidebar.php';
?>
<div class="admin-main">
    <div class="admin-content">
        <h1 class="admin-page-title">Reply to Review</h1>
        <div class="review-block">
            <h3><?php echo htmlspecialchars($review['title'] ?: 'No title'); ?></h3>
            <p><strong>Product:</strong> <?php echo htmlspecialchars($review['product_name']); ?></p>
            <p><strong>Member:</strong> <?php echo htmlspecialchars($review['member_name']); ?></p>
            <p><strong>Rating:</strong> <?php echo htmlspecialchars($review['rating']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
        </div>
        <form method="POST">
            <div class="form-group">
                <label for="admin_reply">Reply</label>
                <textarea id="admin_reply" name="admin_reply" rows="6" class="form-control"><?php echo htmlspecialchars($review['admin_reply'] ?? ''); ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Save Reply</button>
            <a class="btn btn-secondary" href="/admin/review/list.php">Cancel</a>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/_admin_footer.php';
