<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Favorites and cart counts for header badge
$favorites_count = 0;
$cart_count = 0;
if (isset($_SESSION['member_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM favorite WHERE member_id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $favorites_count = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        $favorites_count = 0;
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM shopping_cart WHERE member_id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $cart_count = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        $cart_count = 0;
    }
}
?>

<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/index.php">
                    <h1>📚 Bookstore & Stationery</h1>
                </a>
            </div>
            <div class="header-right">
                <nav class="main-nav">
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/product/list.php">Products</a></li>
                        <?php if (isset($_SESSION['member_id'])): ?>
                            <li><a href="/cart/view.php" class="cart-link" title="Shopping Cart">Cart <span class="count-badge"><?php echo $cart_count; ?></span></a></li>
                            <li><a href="/member/favorites.php" class="favorites-link" title="Favorites">Favorites <span class="count-badge"><?php echo $favorites_count; ?></span></a></li>
                            <li><a href="/order/history.php">Orders</a></li>
                            <li><a href="/member/profile.php">Profile</a></li>
                            <li><a href="/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="/login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</header>

