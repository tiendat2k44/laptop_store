// ============================================
// FILE: backend/src/config/database.js
// ============================================
const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 5432,
  database: process.env.DB_NAME || 'ecommerce_laptop',
  user: process.env.DB_USER || 'postgres',
  password: process.env.DB_PASSWORD,
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

pool.on('connect', () => {
  console.log('✓ Database connected successfully');
});

pool.on('error', (err) => {
  console.error('Unexpected database error:', err);
  process.exit(-1);
});

module.exports = {
  query: (text, params) => pool.query(text, params),
  pool
};

// ============================================
// FILE: backend/src/config/jwt.js
// ============================================
const jwt = require('jsonwebtoken');

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';
const JWT_EXPIRE = process.env.JWT_EXPIRE || '7d';

const generateToken = (payload) => {
  return jwt.sign(payload, JWT_SECRET, { expiresIn: JWT_EXPIRE });
};

const verifyToken = (token) => {
  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (error) {
    return null;
  }
};

module.exports = {
  generateToken,
  verifyToken,
  JWT_SECRET,
  JWT_EXPIRE
};

// ============================================
// FILE: backend/src/utils/errorHandler.js
// ============================================
class AppError extends Error {
  constructor(message, statusCode) {
    super(message);
    this.statusCode = statusCode;
    this.status = `${statusCode}`.startsWith('4') ? 'fail' : 'error';
    this.isOperational = true;

    Error.captureStackTrace(this, this.constructor);
  }
}

const errorHandler = (err, req, res, next) => {
  err.statusCode = err.statusCode || 500;
  err.status = err.status || 'error';

  if (process.env.NODE_ENV === 'development') {
    res.status(err.statusCode).json({
      status: err.status,
      error: err,
      message: err.message,
      stack: err.stack
    });
  } else {
    // Production - không trả về sensitive info
    if (err.isOperational) {
      res.status(err.statusCode).json({
        status: err.status,
        message: err.message
      });
    } else {
      console.error('ERROR:', err);
      res.status(500).json({
        status: 'error',
        message: 'Something went wrong'
      });
    }
  }
};

const catchAsync = (fn) => {
  return (req, res, next) => {
    fn(req, res, next).catch(next);
  };
};

module.exports = {
  AppError,
  errorHandler,
  catchAsync
};

// ============================================
// FILE: backend/src/utils/helpers.js
// ============================================
const crypto = require('crypto');

// Generate unique order code
const generateOrderCode = () => {
  const timestamp = Date.now().toString(36);
  const randomStr = crypto.randomBytes(4).toString('hex').toUpperCase();
  return `ORD-${timestamp}-${randomStr}`;
};

// Generate slug from string
const generateSlug = (text) => {
  return text
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
};

// Format currency VND
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(amount);
};

// Calculate discount percentage
const calculateDiscount = (originalPrice, discountPrice) => {
  if (!discountPrice || discountPrice >= originalPrice) return 0;
  return Math.round(((originalPrice - discountPrice) / originalPrice) * 100);
};

// Pagination helper
const paginate = (page = 1, limit = 20) => {
  const offset = (page - 1) * limit;
  return { limit, offset };
};

// Generate verification token
const generateToken = () => {
  return crypto.randomBytes(32).toString('hex');
};

module.exports = {
  generateOrderCode,
  generateSlug,
  formatCurrency,
  calculateDiscount,
  paginate,
  generateToken
};

// ============================================
// FILE: backend/src/utils/constants.js
// ============================================
const USER_ROLES = {
  CUSTOMER: 'customer',
  SHOP: 'shop',
  ADMIN: 'admin'
};

const ORDER_STATUS = {
  PENDING: 'pending',
  PROCESSING: 'processing',
  SHIPPED: 'shipped',
  DELIVERED: 'delivered',
  CANCELLED: 'cancelled',
  REFUNDED: 'refunded'
};

const PAYMENT_STATUS = {
  PENDING: 'pending',
  PAID: 'paid',
  FAILED: 'failed',
  REFUNDED: 'refunded'
};

const PAYMENT_METHODS = {
  COD: 'cod',
  BANK_TRANSFER: 'bank_transfer',
  VNPAY: 'vnpay',
  MOMO: 'momo'
};

const PRODUCT_STATUS = {
  ACTIVE: 'active',
  INACTIVE: 'inactive',
  OUT_OF_STOCK: 'out_of_stock'
};

const TRANSACTION_TYPES = {
  DEPOSIT: 'deposit',
  WITHDRAW: 'withdraw',
  PURCHASE: 'purchase',
  REFUND: 'refund',
  COMMISSION: 'commission',
  REVENUE: 'revenue'
};

const NOTIFICATION_TYPES = {
  ORDER: 'order',
  SYSTEM: 'system',
  PROMOTION: 'promotion',
  MESSAGE: 'message',
  REVIEW: 'review'
};

module.exports = {
  USER_ROLES,
  ORDER_STATUS,
  PAYMENT_STATUS,
  PAYMENT_METHODS,
  PRODUCT_STATUS,
  TRANSACTION_TYPES,
  NOTIFICATION_TYPES
};

// ============================================
// FILE: backend/src/utils/validators.js
// ============================================
const { body, param, query, validationResult } = require('express-validator');

// Validate email
const validateEmail = () => {
  return body('email')
    .trim()
    .isEmail()
    .withMessage('Email không hợp lệ')
    .normalizeEmail();
};

// Validate password
const validatePassword = () => {
  return body('password')
    .isLength({ min: 6 })
    .withMessage('Mật khẩu phải có ít nhất 6 ký tự')
    .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
    .withMessage('Mật khẩu phải có chữ hoa, chữ thường và số');
};

// Validate phone number (Vietnam)
const validatePhone = () => {
  return body('phone')
    .optional()
    .matches(/^(0|\+84)[0-9]{9,10}$/)
    .withMessage('Số điện thoại không hợp lệ');
};

// Validate product data
const validateProduct = () => {
  return [
    body('product_name').trim().notEmpty().withMessage('Tên sản phẩm không được để trống'),
    body('price').isFloat({ min: 0 }).withMessage('Giá phải là số dương'),
    body('stock_quantity').isInt({ min: 0 }).withMessage('Số lượng phải là số nguyên dương'),
    body('category_id').isInt().withMessage('Category ID không hợp lệ'),
    body('brand_id').isInt().withMessage('Brand ID không hợp lệ')
  ];
};

// Check validation result
const checkValidation = (req, res, next) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).json({
      status: 'fail',
      errors: errors.array()
    });
  }
  next();
};

module.exports = {
  validateEmail,
  validatePassword,
  validatePhone,
  validateProduct,
  checkValidation
};