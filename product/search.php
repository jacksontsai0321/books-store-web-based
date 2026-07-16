<?php
$pageTitle = 'Search Products';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (empty($search)) {
    redirect('/product/list.php');
}

try {
    // Ensure we only show non-hidden products
    $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM product p 
                          LEFT JOIN product_category c ON p.category_id = c.category_id 
                          WHERE (p.product_name LIKE ? OR p.description LIKE ?) AND (p.is_hidden = 0 OR p.is_hidden IS NULL)
                          ORDER BY p.product_id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = 'Search failed.';
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Search Results for "<?php echo htmlspecialchars($search); ?>"</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="info-message" style="display:flex; align-items:center; gap:1rem;">
                <p style="margin:0;">No products found matching your search.</p>
                <button type="button" id="clear-search-btn" class="btn btn-secondary">Clear search</button>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                const clearBtn = document.getElementById('clear-search-btn');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function(){
                        window.location = '/product/list.php';
                    });
                }
            });
            </script>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../includes/components/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

