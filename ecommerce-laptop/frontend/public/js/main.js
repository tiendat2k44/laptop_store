// ============================================
// FILE: frontend/public/js/main.js
// ============================================

// API Configuration
const API_URL = 'http://localhost:5000/api';

// Auth Helper Functions
const AuthService = {
  getToken() {
    return localStorage.getItem('token');
  },

  setToken(token) {
    localStorage.setItem('token', token);
  },

  removeToken() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },

  getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
  },

  isAuthenticated() {
    return !!this.getToken();
  },

  getAuthHeaders() {
    const token = this.getToken();
    return {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` })
    };
  }
};

// API Helper Functions
const API = {
  async get(endpoint, auth = false) {
    const headers = auth ? AuthService.getAuthHeaders() : { 'Content-Type': 'application/json' };
    
    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'GET',
      headers
    });

    return await response.json();
  },

  async post(endpoint, data, auth = false) {
    const headers = auth ? AuthService.getAuthHeaders() : { 'Content-Type': 'application/json' };
    
    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'POST',
      headers,
      body: JSON.stringify(data)
    });

    return await response.json();
  },

  async put(endpoint, data, auth = true) {
    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'PUT',
      headers: AuthService.getAuthHeaders(),
      body: JSON.stringify(data)
    });

    return await response.json();
  },

  async delete(endpoint, auth = true) {
    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'DELETE',
      headers: AuthService.getAuthHeaders()
    });

    return await response.json();
  }
};

// Cart Management
const CartService = {
  async getCart() {
    if (!AuthService.isAuthenticated()) {
      return this.getLocalCart();
    }

    try {
      const response = await API.get('/cart', true);
      return response.data?.cart || [];
    } catch (error) {
      console.error('Error getting cart:', error);
      return [];
    }
  },

  async addToCart(productId, quantity = 1) {
    if (!AuthService.isAuthenticated()) {
      showNotification('Vui lòng đăng nhập để thêm vào giỏ hàng', 'warning');
      setTimeout(() => window.location.href = 'login.html', 1000);
      return;
    }

    try {
      const response = await API.post('/cart', { product_id: productId, quantity }, true);
      
      if (response.status === 'success') {
        showNotification('Đã thêm vào giỏ hàng', 'success');
        updateCartCount();
      } else {
        showNotification(response.message || 'Có lỗi xảy ra', 'error');
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
      showNotification('Có lỗi xảy ra', 'error');
    }
  },

  async updateCartItem(productId, quantity) {
    try {
      const response = await API.put(`/cart/${productId}`, { quantity }, true);
      
      if (response.status === 'success') {
        showNotification('Đã cập nhật giỏ hàng', 'success');
        return true;
      }
      return false;
    } catch (error) {
      console.error('Error updating cart:', error);
      return false;
    }
  },

  async removeFromCart(productId) {
    try {
      const response = await API.delete(`/cart/${productId}`, true);
      
      if (response.status === 'success') {
        showNotification('Đã xóa khỏi giỏ hàng', 'success');
        updateCartCount();
        return true;
      }
      return false;
    } catch (error) {
      console.error('Error removing from cart:', error);
      return false;
    }
  },

  // Local cart for non-authenticated users
  getLocalCart() {
    const cart = localStorage.getItem('local_cart');
    return cart ? JSON.parse(cart) : [];
  },

  saveLocalCart(cart) {
    localStorage.setItem('local_cart', JSON.stringify(cart));
  }
};

// Wishlist Management
const WishlistService = {
  async addToWishlist(productId) {
    if (!AuthService.isAuthenticated()) {
      showNotification('Vui lòng đăng nhập để thêm vào yêu thích', 'warning');
      return;
    }

    try {
      const response = await API.post('/wishlist', { product_id: productId }, true);
      
      if (response.status === 'success') {
        showNotification('Đã thêm vào danh sách yêu thích', 'success');
        updateWishlistCount();
      }
    } catch (error) {
      console.error('Error adding to wishlist:', error);
    }
  }
};

// UI Helper Functions
function formatCurrency(amount) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(amount);
}

function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  
  // Style
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#17a2b8'};
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: slideIn 0.3s ease;
  `;

  document.body.appendChild(notification);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add CSS animations
if (!document.getElementById('notification-styles')) {
  const style = document.createElement('style');
  style.id = 'notification-styles';
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(400px); opacity: 0; }
    }
  `;
  document.head.appendChild(style);
}

// Update Header
async function updateHeader() {
  const user = AuthService.getUser();
  
  if (user) {
    document.getElementById('userName').textContent = user.full_name;
    document.getElementById('loggedOutMenu').style.display = 'none';
    document.getElementById('loggedInMenu').style.display = 'block';
  } else {
    document.getElementById('userName').textContent = 'Đăng nhập';
    document.getElementById('loggedOutMenu').style.display = 'block';
    document.getElementById('loggedInMenu').style.display = 'none';
  }

  await updateCartCount();
  await updateWishlistCount();
}

// Update Cart Count
async function updateCartCount() {
  if (!AuthService.isAuthenticated()) {
    const localCart = CartService.getLocalCart();
    document.getElementById('cartCount').textContent = localCart.length;
    return;
  }

  try {
    const response = await API.get('/cart', true);
    const count = response.data?.cart?.length || 0;
    document.getElementById('cartCount').textContent = count;
  } catch (error) {
    console.error('Error updating cart count:', error);
  }
}

// Update Wishlist Count
async function updateWishlistCount() {
  if (!AuthService.isAuthenticated()) {
    document.getElementById('wishlistCount').textContent = '0';
    return;
  }

  // TODO: Implement wishlist count API
  document.getElementById('wishlistCount').textContent = '0';
}

// Global Functions
async function addToCart(productId) {
  await CartService.addToCart(productId);
}

async function addToWishlist(productId) {
  await WishlistService.addToWishlist(productId);
}

function quickView(productId) {
  window.location.href = `product-detail.html?id=${productId}`;
}

// Logout
document.addEventListener('DOMContentLoaded', () => {
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      
      AuthService.removeToken();
      showNotification('Đã đăng xuất', 'success');
      
      setTimeout(() => {
        window.location.href = 'index.html';
      }, 1000);
    });
  }

  // Search functionality
  const searchBtn = document.getElementById('searchBtn');
  const searchInput = document.getElementById('searchInput');

  if (searchBtn && searchInput) {
    searchBtn.addEventListener('click', () => {
      const query = searchInput.value.trim();
      if (query) {
        window.location.href = `products.html?search=${encodeURIComponent(query)}`;
      }
    });

    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const query = searchInput.value.trim();
        if (query) {
          window.location.href = `products.html?search=${encodeURIComponent(query)}`;
        }
      }
    });
  }
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    API_URL,
    AuthService,
    API,
    CartService,
    WishlistService,
    formatCurrency,
    showNotification,
    updateHeader,
    updateCartCount,
    updateWishlistCount
  };
}