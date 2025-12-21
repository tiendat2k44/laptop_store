<div class="card product-card shadow-sm position-relative">
    <?php if ($product['featured']): ?>
        <span class="badge-featured">Nổi bật</span>
    <?php endif; ?>
    
    <?php if (Auth::check()): ?>
        <button class="btn-wishlist" data-product-id="<?php echo $product['id']; ?>">
            <i class="bi bi-heart"></i>
        </button>
    <?php endif; ?>
    
    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>">
        <?php 
            $img = image_url($product['main_image'] ?? '');
        ?>
        <img src="<?php echo $img; ?>" class="card-img-top" alt="<?php echo escape($product['name']); ?>" loading="lazy">
    </a>
    
    <div class="product-card-body">
        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" class="product-title">
            <?php echo escape($product['name']); ?>
        </a>
        
        <div class="text-muted small mb-2">
            <i class="bi bi-shop"></i> <?php echo escape($product['shop_name']); ?>
        </div>
        
        <?php if ($product['rating_average'] > 0): ?>
            <div class="product-rating mb-2">
                <?php
                $rating = round($product['rating_average']);
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        echo '<i class="bi bi-star-fill"></i>';
                    } else {
                        echo '<i class="bi bi-star"></i>';
                    }
                }
                ?>
                <span class="text-muted small">(<?php echo $product['review_count']; ?>)</span>
            </div>
        <?php endif; ?>
        
        <div>
            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                <span class="product-old-price"><?php echo formatPrice($product['price']); ?></span>
                <span class="product-price"><?php echo formatPrice($product['sale_price']); ?></span>
            <?php else: ?>
                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="mt-auto pt-3">
            <?php if ($product['stock_quantity'] > 0): ?>
                <button class="btn btn-primary w-100 btn-add-to-cart" 
                        data-product-id="<?php echo $product['id']; ?>"
                        data-quantity="1">
                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                </button>
            <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>
                    <i class="bi bi-x-circle"></i> Hết hàng
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
