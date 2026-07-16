<?php
$pageTitle = 'Home';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/_head.php';
require_once __DIR__ . '/includes/_header.php';
?>

<main>
    <div class="container">
        <div class="home-hero">
            <h1 class="page-title">Welcome to Bookstore & Stationery</h1>
            <?php if (isset($_SESSION['member_id'])): ?>
                <div class="home-welcome">Welcome <?php echo htmlspecialchars($_SESSION['member_name'] ?? 'Member'); ?></div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-messages">
                <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <?php
        require_once __DIR__ . '/config/db.php';

        $sliderProducts = [];
        try {
            $stmt = $pdo->query("SELECT DISTINCT category_id FROM product WHERE category_id IS NOT NULL ORDER BY category_id LIMIT 5");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $selectedProducts = [];
            foreach ($categories as $catId) {
                $stmt = $pdo->prepare("SELECT p.*, c.category_name,
                                    (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path
                                    FROM product p 
                                    LEFT JOIN product_category c ON p.category_id = c.category_id
                                    WHERE p.category_id = ?
                                    ORDER BY p.product_id DESC
                                    LIMIT 1");
                $stmt->execute([$catId]);
                $product = $stmt->fetch();
                if ($product) {
                    $selectedProducts[] = $product;
                }
                if (count($selectedProducts) >= 5) break;
            }
            
            $sliderProducts = array_slice($selectedProducts, 0, 5);
            
            // Map column names for compatibility (avoid foreach by reference)
            foreach ($sliderProducts as $k => $product) {
                $sliderProducts[$k]['product_name'] = $product['name'] ?? '';
                $sliderProducts[$k]['stock_quantity'] = $product['stock'] ?? 0;
                if (!empty($product['image_path'])) {
                    $sliderProducts[$k]['image_path'] = '/uploads/products/' . $product['image_path'];
                }
            }
        } catch (PDOException $e) {
            $sliderProducts = [];
        }
        
        // Get best selling products
        $bestSellingProducts = [];
        try {
            $stmt = $pdo->query("SELECT p.*, c.category_name,
                                (SELECT filename FROM product_photo WHERE product_id = p.product_id LIMIT 1) as image_path,
                                COALESCE(SUM(od.quantity), 0) as total_sold
                                FROM product p 
                                LEFT JOIN product_category c ON p.category_id = c.category_id
                                LEFT JOIN order_detail od ON p.product_id = od.product_id
                                LEFT JOIN order_header oh ON od.order_id = oh.order_id
                                GROUP BY p.product_id, c.category_name
                                ORDER BY total_sold DESC, p.product_id DESC
                                LIMIT 8");
            $bestSellingProducts = $stmt->fetchAll();
            
            // Map column names for compatibility (avoid foreach by reference)
            foreach ($bestSellingProducts as $k => $product) {
                $bestSellingProducts[$k]['product_name'] = $product['name'] ?? '';
                $bestSellingProducts[$k]['stock_quantity'] = $product['stock'] ?? 0;
                if (!empty($product['image_path'])) {
                    $bestSellingProducts[$k]['image_path'] = '/uploads/products/' . $product['image_path'];
                }
            }
        } catch (PDOException $e) {
            $bestSellingProducts = [];
        }
        ?>
        
        <?php if (!empty($sliderProducts)): ?>
            <!-- Product Slider -->
            <div class="product-slider-container" style="margin: 2rem 0; position: relative;">
                <button class="slider-arrow slider-arrow-left" id="sliderPrev" aria-label="Previous slide">‹</button>
                <div class="product-slider" id="productSlider">
                    <?php foreach ($sliderProducts as $index => $product): ?>
                        <div class="slider-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="slider-content">
                                <div class="slider-image">
                                    <?php if (!empty($product['image_path']) && file_exists(__DIR__ . $product['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <?php else: ?>
                                        <div class="slider-image-placeholder">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="slider-info">
                                    <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                                    <p class="slider-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                                    <p class="slider-price"><?php echo formatPrice($product['price']); ?></p>
                                    <p class="slider-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 150)); ?><?php echo strlen($product['description'] ?? '') > 150 ? '...' : ''; ?></p>
                                    <?php if (isset($_SESSION['member_id'])): ?>
                                        <a href="/product/detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary member-btn-primary">View Details</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="slider-arrow slider-arrow-right" id="sliderNext" aria-label="Next slide">›</button>
                <div class="slider-indicators">
                    <?php foreach ($sliderProducts as $index => $product): ?>
                        <span class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($bestSellingProducts)): ?>
            <!-- Best Selling Products -->
            <h2 class="page-title" style="margin-top: 3rem;">Best Selling Products</h2>
            <div class="product-grid">
                <?php foreach ($bestSellingProducts as $product): ?>
                    <?php include __DIR__ . '/includes/components/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="info-message">No products available at the moment. Check back soon!</p>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('productSlider');
    if (!slider) return;
    
    const slides = slider.querySelectorAll('.slider-slide');
    const indicators = document.querySelectorAll('.slider-indicators .indicator');
    const prevBtn = document.getElementById('sliderPrev');
    const nextBtn = document.getElementById('sliderNext');
    let currentSlide = 0;
    
    function showSlide(index) {
        // Remove active class from all slides and indicators
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        // Add active class to current slide and indicator
        if (slides[index]) {
            slides[index].classList.add('active');
        }
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }
    
    // Auto-rotate every 5 seconds
    let slideInterval = setInterval(nextSlide, 5000);
    
    // Add click handlers to arrows
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            nextSlide();
            // Reset the interval
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            prevSlide();
            // Reset the interval
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        });
    }
    
    // Add click handlers to indicators
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            currentSlide = index;
            showSlide(currentSlide);
            // Reset the interval
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        });
    });
    
    // Pause on hover
    slider.addEventListener('mouseenter', function() {
        clearInterval(slideInterval);
    });
    
    // Resume on mouse leave
    slider.addEventListener('mouseleave', function() {
        slideInterval = setInterval(nextSlide, 5000);
    });
});
</script>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>

