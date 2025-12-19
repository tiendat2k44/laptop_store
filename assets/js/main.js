// Main JavaScript for Laptop Store

$(document).ready(function() {
    
    // Add to cart
    $(document).on('click', '.btn-add-to-cart', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const quantity = $(this).data('quantity') || 1;
        const button = $(this);
        
        // Check if user is logged in
        if (!isLoggedIn()) {
            window.location.href = window.SITE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
            return;
        }
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');
        
        $.ajax({
            url: window.SITE_URL + '/ajax/cart-add.php',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                csrf_token: getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message || 'Đã thêm vào giỏ hàng!');
                    updateCartCount(response.cart_count);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    showNotification('error', 'Vui lòng đăng nhập để thêm vào giỏ hàng!');
                    setTimeout(function() {
                        window.location.href = window.SITE_URL + '/login.php';
                    }, 1500);
                } else {
                    showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="bi bi-cart-plus"></i> Thêm vào giỏ');
            }
        });
    });
    
    // Add to wishlist
    $(document).on('click', '.btn-wishlist', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = $(this).data('product-id');
        const button = $(this);
        
        $.ajax({
            url: '/ajax/wishlist-toggle.php',
            method: 'POST',
            data: {
                product_id: productId,
                csrf_token: getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.added) {
                        button.addClass('active').html('<i class="bi bi-heart-fill"></i>');
                    } else {
                        button.removeClass('active').html('<i class="bi bi-heart"></i>');
                    }
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });
    
    // Update cart quantity
    $(document).on('change', '.cart-quantity-input', function() {
        const itemId = $(this).data('item-id');
        const quantity = parseInt($(this).val());
        
        if (quantity < 1) {
            $(this).val(1);
            return;
        }
        
        updateCartItem(itemId, quantity);
    });
    
    // Remove cart item
    $(document).on('click', '.btn-remove-cart-item', function(e) {
        e.preventDefault();
        
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            return;
        }
        
        const itemId = $(this).data('item-id');
        
        $.ajax({
            url: '/ajax/cart-remove.php',
            method: 'POST',
            data: {
                item_id: itemId,
                csrf_token: getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    location.reload();
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });
    
    // Live search
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length < 2) {
            $('#searchSuggestions').html('').hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '/ajax/search-suggestions.php',
                method: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.products.length > 0) {
                        let html = '<div class="search-suggestions">';
                        response.products.forEach(function(product) {
                            html += `
                                <div class="search-suggestion-item" data-url="/product-detail.php?id=${product.id}">
                                    <img src="${product.image}" alt="${product.name}">
                                    <div>
                                        <div class="fw-bold">${product.name}</div>
                                        <div class="text-danger fw-bold">${product.price}</div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#searchSuggestions').html(html).show();
                    } else {
                        $('#searchSuggestions').html('').hide();
                    }
                }
            });
        }, 300);
    });
    
    // Click on search suggestion
    $(document).on('click', '.search-suggestion-item', function() {
        window.location.href = $(this).data('url');
    });
    
    // Hide search suggestions when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('#searchInput, #searchSuggestions').length) {
            $('#searchSuggestions').hide();
        }
    });
    
    // Product image gallery
    $('.product-thumbnail').on('click', function() {
        const imageUrl = $(this).data('image');
        $('.product-detail-image').attr('src', imageUrl);
        $('.product-thumbnail').removeClass('active');
        $(this).addClass('active');
    });
    
    // Form validation
    $('form[data-validate="true"]').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('error', 'Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
    });
    
    // Clear invalid state on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Helper functions
function addToCart(productId, quantity = 1) {
    try {
        if (!isLoggedIn()) {
            window.location.href = window.SITE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
            return;
        }

        $.ajax({
            url: window.SITE_URL + '/ajax/cart-add.php',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                csrf_token: getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message || 'Đã thêm vào giỏ hàng!');
                    if (typeof response.cart_count !== 'undefined') {
                        updateCartCount(response.cart_count);
                    }
                } else {
                    showNotification('error', response.message || 'Không thể thêm vào giỏ hàng');
                }
            },
            error: function() {
                showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    } catch (e) {
        console.error(e);
        showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
    }
}

function isLoggedIn() {
    // This should be set from PHP
    return typeof window.userLoggedIn !== 'undefined' && window.userLoggedIn;
}

function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;" role="alert">
            <i class="bi ${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alert);
    
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}

function updateCartCount(count) {
    const badge = $('.btn-primary .badge');
    if (count > 0) {
        if (badge.length) {
            badge.text(count);
        } else {
            $('.btn-primary').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${count}</span>`);
        }
    } else {
        badge.remove();
    }
}

function updateCartItem(itemId, quantity) {
    $.ajax({
        url: '/ajax/cart-update.php',
        method: 'POST',
        data: {
            item_id: itemId,
            quantity: quantity,
            csrf_token: getCsrfToken()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
}
