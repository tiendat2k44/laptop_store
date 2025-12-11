// ============================================
// FILE: backend/src/routes/auth.routes.js
// ============================================
const express = require('express');
const router = express.Router();
const authController = require('../controllers/auth.controller');
const { authenticate } = require('../middleware/auth.middleware');
const { validateEmail, validatePassword, checkValidation } = require('../utils/validators');

router.post('/register', [
  validateEmail(),
  validatePassword(),
  checkValidation
], authController.register);

router.post('/login', authController.login);
router.post('/logout', authenticate, authController.logout);
router.get('/me', authenticate, authController.getMe);
router.put('/change-password', authenticate, authController.changePassword);
router.post('/forgot-password', authController.forgotPassword);
router.post('/reset-password', authController.resetPassword);

module.exports = router;

// ============================================
// FILE: backend/src/routes/product.routes.js
// ============================================
const express = require('express');
const router = express.Router();
const productController = require('../controllers/product.controller');
const { authenticate, optionalAuth } = require('../middleware/auth.middleware');
const { isShop, isAdmin } = require('../middleware/role.middleware');

// Public routes
router.get('/', optionalAuth, productController.getAllProducts);
router.get('/search', productController.searchProducts);
router.get('/:id', optionalAuth, productController.getProduct);

// Protected routes (Shop/Admin)
router.post('/', authenticate, isShop, productController.createProduct);
router.put('/:id', authenticate, isShop, productController.updateProduct);
router.delete('/:id', authenticate, isShop, productController.deleteProduct);

module.exports = router;

// ============================================
// FILE: backend/src/routes/cart.routes.js
// ============================================
const express = require('express');
const router = express.Router();
const cartController = require('../controllers/cart.controller');
const { authenticate } = require('../middleware/auth.middleware');
const { isCustomer } = require('../middleware/role.middleware');

router.use(authenticate, isCustomer);

router.get('/', cartController.getCart);
router.post('/', cartController.addToCart);
router.put('/:productId', cartController.updateCartItem);
router.delete('/:productId', cartController.removeFromCart);
router.delete('/', cartController.clearCart);

module.exports = router;

// ============================================
// FILE: backend/src/routes/order.routes.js
// ============================================
const express = require('express');
const router = express.Router();
const orderController = require('../controllers/order.controller');
const { authenticate } = require('../middleware/auth.middleware');
const { isCustomer, isShop } = require('../middleware/role.middleware');

// Customer routes
router.post('/', authenticate, isCustomer, orderController.createOrder);
router.get('/my-orders', authenticate, isCustomer, orderController.getMyOrders);
router.post('/:id/cancel', authenticate, isCustomer, orderController.cancelOrder);

// Shop routes
router.get('/shop', authenticate, isShop, orderController.getShopOrders);

// Common routes
router.get('/:id', authenticate, orderController.getOrderDetail);
router.put('/:id/status', authenticate, isShop, orderController.updateOrderStatus);

module.exports = router;

// ============================================
// FILE: backend/src/app.js
// ============================================
const express = require('express');
const cors = require('cors');
const path = require('path');
const { errorHandler } = require('./utils/errorHandler');
const rateLimit = require('express-rate-limit');

const app = express();

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // limit each IP to 100 requests per windowMs
  message: 'Too many requests from this IP, please try again later.'
});

// Middleware
app.use(cors({
  origin: process.env.FRONTEND_URL || 'http://localhost:3000',
  credentials: true
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use('/api/', limiter);

// Serve static files
app.use('/uploads', express.static(path.join(__dirname, 'public/uploads')));

// Routes
app.use('/api/auth', require('./routes/auth.routes'));
app.use('/api/products', require('./routes/product.routes'));
app.use('/api/cart', require('./routes/cart.routes'));
app.use('/api/orders', require('./routes/order.routes'));

// Health check
app.get('/health', (req, res) => {
  res.status(200).json({
    status: 'success',
    message: 'Server is running',
    timestamp: new Date().toISOString()
  });
});

// 404 handler
app.all('*', (req, res) => {
  res.status(404).json({
    status: 'fail',
    message: `Can't find ${req.originalUrl} on this server`
  });
});

// Global error handler
app.use(errorHandler);

module.exports = app;

// ============================================
// FILE: backend/src/server.js
// ============================================
require('dotenv').config();
const app = require('./app');
const db = require('./config/database');

const PORT = process.env.PORT || 5000;

// Test database connection
const testConnection = async () => {
  try {
    await db.query('SELECT NOW()');
    console.log('✓ Database connection successful');
  } catch (error) {
    console.error('✗ Database connection failed:', error.message);
    process.exit(1);
  }
};

// Start server
const startServer = async () => {
  await testConnection();
  
  app.listen(PORT, () => {
    console.log(`
╔═══════════════════════════════════════════╗
║   E-commerce Laptop API Server            ║
║   Port: ${PORT}                              ║
║   Environment: ${process.env.NODE_ENV || 'development'}            ║
║   Time: ${new Date().toLocaleString()}      ║
╚═══════════════════════════════════════════╝
    `);
  });
};

// Handle unhandled promise rejections
process.on('unhandledRejection', (err) => {
  console.error('UNHANDLED REJECTION! Shutting down...');
  console.error(err.name, err.message);
  process.exit(1);
});

// Handle uncaught exceptions
process.on('uncaughtException', (err) => {
  console.error('UNCAUGHT EXCEPTION! Shutting down...');
  console.error(err.name, err.message);
  process.exit(1);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received. Shutting down gracefully...');
  db.pool.end(() => {
    console.log('Database pool closed');
    process.exit(0);
  });
});

startServer();