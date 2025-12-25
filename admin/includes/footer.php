    </main>

    <!-- Bundle Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery - Từ Google CDN theo CSP -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <script>
        // Tự động ẩn thông báo
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Xác nhận xóa
        $('.btn-delete').on('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa?')) {
                e.preventDefault();
            }
        });
    </script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>
