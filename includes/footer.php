    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-laptop"></i> <?php echo SITE_NAME; ?></h5>
                    <p>Cung cấp laptop chính hãng, giá tốt nhất thị trường. Đa dạng mẫu mã từ các thương hiệu hàng đầu.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="bi bi-facebook fs-4"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-youtube fs-4"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-instagram fs-4"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3">Hỗ trợ</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">Hướng dẫn mua hàng</a></li>
                        <li><a href="#" class="text-white-50">Chính sách đổi trả</a></li>
                        <li><a href="#" class="text-white-50">Bảo hành</a></li>
                        <li><a href="#" class="text-white-50">Vận chuyển</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/products.php" class="text-white-50">Sản phẩm</a></li>
                        <li><a href="#" class="text-white-50">Giới thiệu</a></li>
                        <li><a href="#" class="text-white-50">Tin tức</a></li>
                        <li><a href="#" class="text-white-50">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> Hà Nội, Việt Nam</li>
                        <li class="mb-2"><i class="bi bi-telephone"></i> 1900-xxxx</li>
                        <li class="mb-2"><i class="bi bi-envelope"></i> <?php echo SITE_EMAIL; ?></li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-secondary">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="badge bg-light text-dark me-2 px-3 py-2">Visa</span>
                    <span class="badge bg-light text-dark me-2 px-3 py-2">Mastercard</span>
                    <span class="badge bg-light text-dark px-3 py-2">MoMo</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for AJAX operations) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>
