<?php
$pageTitle = 'Write Review';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$errors = [];
$success = null;
$memberId = $_SESSION['member_id'];

$orderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
$productId = isset($_GET['product_id']) ? sanitize($_GET['product_id']) : '';

// Determine context: order view (list products) or single product
$productsToReview = [];

try {
    if ($orderId) {
        // Verify order belongs to member and is eligible for reviews (Shipped or Completed)
        $stmt = $pdo->prepare("SELECT * FROM order_header WHERE order_id = ? AND member_id = ? AND status IN ('Shipped','Completed')");
        $stmt->execute([$orderId, $memberId]);
        $order = $stmt->fetch();
        if (!$order) {
            $_SESSION['error'] = 'Order not found or not eligible for reviews.';
            redirect('/order/history.php');
        }
        // Get order items
        $stmt = $pdo->prepare("SELECT od.*, p.name, p.product_id FROM order_detail od JOIN product p ON od.product_id = p.product_id WHERE od.order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        foreach ($items as $it) {
            // include only products not yet reviewed by this member
            if (!hasMemberReviewedProduct($pdo, $memberId, $it['product_id'])) {
                $productsToReview[] = $it;
            }
        }
    } elseif ($productId) {
        // Single product context - verify eligibility
        if (!memberHasPurchasedProduct($pdo, $memberId, $productId)) {
            $_SESSION['error'] = 'You are not eligible to review this product.';
            redirect('/product/detail.php?id=' . $productId);
        }
        // If already reviewed, redirect
        if (hasMemberReviewedProduct($pdo, $memberId, $productId)) {
            $_SESSION['error'] = 'You have already reviewed this product.';
            redirect('/product/detail.php?id=' . $productId);
        }
        $stmt = $pdo->prepare("SELECT p.product_id, p.name FROM product p WHERE p.product_id = ?");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();
        if (!$prod) {
            $_SESSION['error'] = 'Product not found.';
            redirect('/product/list.php');
        }
        $productsToReview[] = $prod;
    } else {
        // No context provided
        $_SESSION['error'] = 'No order or product specified for review.';
        redirect('/order/history.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Unable to load review data.';
    redirect('/order/history.php');
}

// Handle POST submission(s)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = sanitize($_POST['product_id'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');

    if (empty($product_id) || $rating < 1 || $rating > 5 || empty($content)) {
        $errors[] = 'Please provide a rating (1-5) and a review comment.';
    } else {
        // Check eligibility
        if (!memberHasPurchasedProduct($pdo, $memberId, $product_id)) {
            $errors[] = 'You are not eligible to review this product.';
        } elseif (hasMemberReviewedProduct($pdo, $memberId, $product_id)) {
            $errors[] = 'You have already reviewed this product.';
        } else {
            try {
                $ok = addReview($pdo, $memberId, $product_id, $rating, $title, $content);
                if ($ok) {
                    $_SESSION['success'] = 'Thank you for your review.';
                    // Redirect back to product detail or order review page
                    if ($orderId) redirect('/order/review.php?order_id=' . $orderId);
                    redirect('/product/detail.php?id=' . $product_id);
                } else {
                    $errors[] = 'Failed to save review. Please try again.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to save review. Please try again.';
            }
        }
    }
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Write a Review</h1>

        <?php if (!empty($errors)): ?>
            <?php renderErrors($errors); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (empty($productsToReview)): ?>
            <p class="info-message">No products available for review.</p>
        <?php else: ?>
            <?php foreach ($productsToReview as $prod): ?>
                <div class="product-review-box" style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <h3><?php echo htmlspecialchars($prod['name'] ?? $prod['product_name'] ?? 'Product'); ?></h3>
                    <form method="POST" action="/order/review.php?<?php echo $orderId ? 'order_id=' . urlencode($orderId) : 'product_id=' . urlencode($prod['product_id']); ?>">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($prod['product_id']); ?>">
                        <div class="form-group">
                            <label>Rating</label>
                            <select name="rating" required>
                                <option value="">Select</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Title (optional)</label>
                            <input type="text" name="title" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>Review</label>
                            <textarea name="content" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary member-btn-primary">Submit Review</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 1rem;">
            <a href="/order/history.php" class="btn btn-secondary member-btn-secondary">Back</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
