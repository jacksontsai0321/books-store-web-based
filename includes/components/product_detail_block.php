<?php
/**
 * Reusable Product Detail Block Component
 * Variables expected: $product (array), $productPhotos (array)
 */

require_once __DIR__ . '/../../config/functions.php';
?>

<div class="product-detail-block">
    <div class="product-detail-image">
        <?php if (!empty($productPhotos)): ?>
            <?php if (count($productPhotos) > 1): ?>
                <!-- Photo Slider -->
                <div class="product-photo-slider-container" style="position: relative;">
                    <button class="slider-arrow slider-arrow-left photo-slider-prev" aria-label="Previous photo">‹</button>
                    <div class="product-photo-slider" id="productPhotoSlider">
                        <?php foreach ($productPhotos as $index => $photo): ?>
                            <div class="photo-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <?php if (file_exists(__DIR__ . '/../..' . $photo)): ?>
                                    <img src="<?php echo htmlspecialchars($photo); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <div class="product-image-placeholder" style="min-height: 400px;">
                                        <span>No Image</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-arrow slider-arrow-right photo-slider-next" aria-label="Next photo">›</button>
                    <div class="photo-slider-indicators">
                        <?php foreach ($productPhotos as $index => $photo): ?>
                            <span class="photo-indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-photo="<?php echo $index; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Single Photo -->
                <?php if (file_exists(__DIR__ . '/../..' . $productPhotos[0])): ?>
                    <img src="<?php echo htmlspecialchars($productPhotos[0]); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                <?php else: ?>
                    <div class="product-image-placeholder" style="min-height: 400px;">
                        <span>No Image</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="product-image-placeholder" style="min-height: 400px;">
                <span>No Image</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="product-detail-info">
        <h1 class="product-detail-name"><?php echo htmlspecialchars($product['product_name']); ?></h1>
        <p class="product-detail-category">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
        <p class="product-detail-price"><?php echo formatPrice($product['price']); ?></p>
        <p class="product-detail-stock">
            <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] < 5): ?>
                <span class="low-stock">Low stock (<?php echo $product['stock_quantity']; ?> left)</span>
            <?php elseif ($product['stock_quantity'] > 0): ?>
                <span class="in-stock">In Stock</span>
            <?php else: ?>
                <span class="out-of-stock">Out of Stock</span>
            <?php endif; ?>
        </p>
        <?php if (!empty($product['description'])): ?>
            <div class="product-detail-description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($product['stock_quantity'] > 0 && isset($_SESSION['member_id'])): ?>
            <form method="POST" action="/cart/add.php" class="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <input type="hidden" name="return_to" value="/product/list.php">
                <div class="quantity-selector">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-large member-btn-primary">Add to Cart</button>
            </form>
        <?php elseif (!isset($_SESSION['member_id'])): ?>
            <p class="login-prompt"><a href="/login.php">Login</a> to add items to cart.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($productPhotos) && count($productPhotos) > 1): ?>
<style>
.product-photo-slider-container {
    position: relative;
    width: 100%;
}

.product-photo-slider {
    position: relative;
    width: 100%;
    min-height: 450px;
    overflow: hidden;
}

.photo-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.photo-slide.active {
    opacity: 1;
    position: relative;
}

.photo-slide img {
    width: 100%;
    height: 450px;
    object-fit: contain;
    display: block;
    background-color: #fff;
}

.photo-slider-indicators {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

.photo-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #bdc3c7;
    cursor: pointer;
    transition: background-color 0.3s;
}

.photo-indicator.active {
    background-color: #3498db;
}

.photo-indicator:hover {
    background-color: #7f8c8d;
}

.photo-slider-prev,
.photo-slider-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

.photo-slider-prev {
    left: 20px;
}

.photo-slider-next {
    right: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('productPhotoSlider');
    if (!slider) return;
    
    const slides = slider.querySelectorAll('.photo-slide');
    const indicators = document.querySelectorAll('.photo-indicator');
    const prevBtn = document.querySelector('.photo-slider-prev');
    const nextBtn = document.querySelector('.photo-slider-next');
    let currentPhoto = 0;
    
    function showPhoto(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        if (slides[index]) {
            slides[index].classList.add('active');
        }
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }
    }
    
    function nextPhoto() {
        currentPhoto = (currentPhoto + 1) % slides.length;
        showPhoto(currentPhoto);
    }
    
    function prevPhoto() {
        currentPhoto = (currentPhoto - 1 + slides.length) % slides.length;
        showPhoto(currentPhoto);
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', nextPhoto);
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', prevPhoto);
    }
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            currentPhoto = index;
            showPhoto(currentPhoto);
        });
    });
});
</script>
<?php endif; ?>

