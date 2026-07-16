<?php
$pageTitle = 'Products';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/_head.php';
require_once __DIR__ . '/../includes/_header.php';

$categoryId = isset($_GET['category_id']) ? sanitize($_GET['category_id']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$priceRange = isset($_GET['price_range']) ? sanitize($_GET['price_range']) : '';

$categories = [];
$products = [];
$error = null;

// Ensure favorite table exists (safe to try)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorite (
        favorite_id VARCHAR(32) PRIMARY KEY,
        member_id VARCHAR(32) NOT NULL,
        product_id VARCHAR(32) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(member_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // ignore
}

try {
    // Get categories for filter
    $stmt = $pdo->query("SELECT * FROM product_category ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Compatibility: ensure 'is_hidden' column exists (safe add on first-run)
$hasIsHidden = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM product LIKE 'is_hidden'");
    $hasIsHidden = (bool)$stmt->fetch();
    if (!$hasIsHidden) {
        // add column with safe default
        $pdo->exec("ALTER TABLE product ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
        $hasIsHidden = true;
    }
} catch (PDOException $e) {
    // If any error (missing table etc.), leave $hasIsHidden as false and continue
}

try {
    // Build query
    // Initialize params first so we can append conditionally
    $params = [];

    // Base where-clause: only reference is_hidden if it exists; otherwise use a neutral condition
    $baseWhere = $hasIsHidden ? "(p.is_hidden = 0 OR p.is_hidden IS NULL)" : "1";

    // If logged in, show favorites first by joining favorites table for this user
    if (isset($_SESSION['member_id'])) {
        $sql = "SELECT p.*, c.category_name, (f.member_id IS NOT NULL) as is_favorite,
                (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                FROM product p 
                LEFT JOIN product_category c ON p.category_id = c.category_id 
                LEFT JOIN favorite f ON f.product_id = p.product_id AND f.member_id = ?
                WHERE {$baseWhere}";
        $params[] = $_SESSION['member_id'];
    } else {
        $sql = "SELECT p.*, c.category_name,
                (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                FROM product p 
                LEFT JOIN product_category c ON p.category_id = c.category_id 
                WHERE {$baseWhere}";
    }
    
    if (!empty($categoryId)) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Price range filtering
    if (!empty($priceRange)) {
        if (strpos($priceRange, '-') !== false) {
            list($min, $max) = explode('-', $priceRange, 2);
            $min = ($min === '') ? null : (float)$min;
            $max = ($max === '') ? null : (float)$max;
            if ($min !== null && $max !== null) {
                $sql .= " AND (p.price BETWEEN ? AND ?)";
                $params[] = $min;
                $params[] = $max;
            } elseif ($min !== null && $max === null) {
                $sql .= " AND p.price >= ?";
                $params[] = $min;
            }
        }
    }
    
    // Order favorites first when available
    if (isset($_SESSION['member_id'])) {
        $sql .= " ORDER BY is_favorite DESC, p.product_id DESC";
    } else {
        $sql .= " ORDER BY p.product_id DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    // Map column names for compatibility (avoid foreach by reference)
    foreach ($products as $idx => $product) {
        $products[$idx]['product_name'] = $product['name'] ?? '';
        $products[$idx]['stock_quantity'] = $product['stock'] ?? 0;
        if (!empty($product['image_path'])) {
            $products[$idx]['image_path'] = '/uploads/products/' . $product['image_path'];
        }
    }
    // Capture SQL and params for optional debug (visible only when ?debug=1)
    $debugInfo = ['sql' => $sql ?? '', 'params' => $params ?? []];
} catch (PDOException $e) {
    $products = [];
    $error = 'Unable to load products.';
    $debugInfo = ['sql' => $sql ?? '', 'params' => $params ?? [], 'error' => $e->getMessage()];
}
?>

<main>
    <div class="container">
        <h1 class="page-title">Products</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="search-form">
            <form method="GET" action="/product/list.php" id="search-form">
                <?php if (!empty($categoryId)): ?>
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($categoryId); ?>">
                <?php endif; ?>
                <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 0.5rem; align-items: center;">
                    <input type="text" id="search-input" name="search" 
                           placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="button" id="clear-search-btn" class="btn btn-secondary" style="display: <?php echo !empty($search) ? 'inline-block' : 'none'; ?>;">&times;</button>
                    <button type="submit" class="btn btn-primary member-btn-primary">Search</button>
                </div>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('search-input');
            const clearBtn = document.getElementById('clear-search-btn');
            const params = new URLSearchParams(window.location.search);

            function updateClear() {
                if (input.value.trim() !== '') {
                    clearBtn.style.display = 'inline-block';
                } else {
                    clearBtn.style.display = 'none';
                }
            }

            input.addEventListener('input', updateClear);

            clearBtn.addEventListener('click', function () {
                // Keep category_id if present
                if (params.has('category_id')) {
                    window.location = '/product/list.php?category_id=' + encodeURIComponent(params.get('category_id'));
                } else {
                    window.location = '/product/list.php';
                }
            });

            // Allow ESC to clear search and reload without query
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    if (params.has('category_id')) {
                        window.location = '/product/list.php?category_id=' + encodeURIComponent(params.get('category_id'));
                    } else {
                        window.location = '/product/list.php';
                    }
                }
            });
        });
        </script>
        
        <div class="product-list-layout" style="margin-bottom:2rem; display:flex; gap:2rem; align-items:flex-start;">
            <aside class="product-filters">
                <div>
                    <strong>Filter by Category:</strong>
                    <?php
                    $allUrl = '/product/list.php';
                    if (!empty($search)) {
                        $allUrl .= '?search=' . urlencode($search);
                    }
                    if (!empty($priceRange)) {
                        $allUrl .= (strpos($allUrl, '?') === false ? '?' : '&') . 'price_range=' . urlencode($priceRange);
                    }
                    ?>
                    <a href="<?php echo $allUrl; ?>" class="btn btn-sm <?php echo empty($categoryId) ? 'btn-primary' : 'btn-secondary'; ?>" 
                       style="margin-left: 0.5rem;">All</a>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $catUrl = '/product/list.php?category_id=' . urlencode($cat['category_id']);
                        if (!empty($search)) {
                            $catUrl .= '&search=' . urlencode($search);
                        }
                        if (!empty($priceRange)) {
                            $catUrl .= '&price_range=' . urlencode($priceRange);
                        }
                        ?>
                        <a href="<?php echo $catUrl; ?>" 
                           class="btn btn-sm <?php echo $categoryId === $cat['category_id'] ? 'btn-primary' : 'btn-secondary'; ?>"
                           style="margin-left: 0.5rem; display:block; margin-bottom:0.25rem;">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:1rem;">
                    <strong>Price Range:</strong>
                    <?php
                    $ranges = [
                        '0-10' => 'RM0 - RM10',
                        '10-20' => 'RM10 - RM20',
                        '20-50' => 'RM20 - RM50',
                    ];
                    ?>
                    <?php foreach ($ranges as $key=>$label): ?>
                        <?php
                        $url = '/product/list.php?price_range=' . urlencode($key);
                        if (!empty($search)) $url .= '&search=' . urlencode($search);
                        if (!empty($categoryId)) $url .= '&category_id=' . urlencode($categoryId);
                        ?>
                        <a href="<?php echo $url; ?>" class="btn btn-sm <?php echo ($priceRange === $key) ? 'btn-primary' : 'btn-secondary'; ?>" style="display:block; margin-top:0.25rem;">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
            <section class="product-list">
        
        <?php if (isset($error)): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php elseif (empty($products)): ?>
            <p class="info-message">No products found.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../includes/components/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1' && !empty($debugInfo)): ?>
                <pre class="debug-output"><?php echo htmlspecialchars("SQL:\n".$debugInfo['sql']."\n\nParams: ".print_r($debugInfo['params'], true)."\n\nError: ".($debugInfo['error'] ?? '')) ; ?></pre>
            <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>

