// ============================================
// FILE: backend/src/middleware/auth.middleware.js
// ============================================
const { verifyToken } = require('../config/jwt');
const { AppError } = require('../utils/errorHandler');
const db = require('../config/database');

const authenticate = async (req, res, next) => {
  try {
    // Lấy token từ header
    const authHeader = req.headers.authorization;
    
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return next(new AppError('Vui lòng đăng nhập để tiếp tục', 401));
    }

    const token = authHeader.split(' ')[1];
    
    // Verify token
    const decoded = verifyToken(token);
    
    if (!decoded) {
      return next(new AppError('Token không hợp lệ hoặc đã hết hạn', 401));
    }

    // Lấy thông tin user từ database
    const result = await db.query(
      'SELECT user_id, email, full_name, role, is_active FROM users WHERE user_id = $1',
      [decoded.userId]
    );

    if (result.rows.length === 0) {
      return next(new AppError('Người dùng không tồn tại', 401));
    }

    const user = result.rows[0];

    if (!user.is_active) {
      return next(new AppError('Tài khoản đã bị khóa', 403));
    }

    // Gắn thông tin user vào request
    req.user = user;
    next();
  } catch (error) {
    next(new AppError('Xác thực thất bại', 401));
  }
};

// Middleware optional auth (cho phép cả đã và chưa đăng nhập)
const optionalAuth = async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    
    if (authHeader && authHeader.startsWith('Bearer ')) {
      const token = authHeader.split(' ')[1];
      const decoded = verifyToken(token);
      
      if (decoded) {
        const result = await db.query(
          'SELECT user_id, email, full_name, role FROM users WHERE user_id = $1 AND is_active = true',
          [decoded.userId]
        );
        
        if (result.rows.length > 0) {
          req.user = result.rows[0];
        }
      }
    }
    
    next();
  } catch (error) {
    next();
  }
};

module.exports = {
  authenticate,
  optionalAuth
};

// ============================================
// FILE: backend/src/middleware/role.middleware.js
// ============================================
const { AppError } = require('../utils/errorHandler');
const { USER_ROLES } = require('../utils/constants');

// Check if user has required role
const checkRole = (...roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return next(new AppError('Vui lòng đăng nhập', 401));
    }

    if (!roles.includes(req.user.role)) {
      return next(new AppError('Bạn không có quyền truy cập', 403));
    }

    next();
  };
};

// Middleware cho admin
const isAdmin = checkRole(USER_ROLES.ADMIN);

// Middleware cho shop
const isShop = checkRole(USER_ROLES.SHOP, USER_ROLES.ADMIN);

// Middleware cho customer
const isCustomer = checkRole(USER_ROLES.CUSTOMER, USER_ROLES.ADMIN);

// Check if user is the owner of resource or admin
const isOwnerOrAdmin = async (req, res, next) => {
  const { user_id } = req.params;
  
  if (req.user.role === USER_ROLES.ADMIN) {
    return next();
  }

  if (parseInt(user_id) !== req.user.user_id) {
    return next(new AppError('Bạn không có quyền truy cập tài nguyên này', 403));
  }

  next();
};

module.exports = {
  checkRole,
  isAdmin,
  isShop,
  isCustomer,
  isOwnerOrAdmin
};

// ============================================
// FILE: backend/src/middleware/upload.middleware.js
// ============================================
const multer = require('multer');
const path = require('path');
const { AppError } = require('../utils/errorHandler');

// Cấu hình storage cho multer
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, 'src/public/uploads/');
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname));
  }
});

// File filter - chỉ cho phép upload ảnh
const fileFilter = (req, file, cb) => {
  const allowedTypes = /jpeg|jpg|png|gif|webp/;
  const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
  const mimetype = allowedTypes.test(file.mimetype);

  if (mimetype && extname) {
    return cb(null, true);
  } else {
    cb(new AppError('Chỉ cho phép upload file ảnh (jpeg, jpg, png, gif, webp)', 400));
  }
};

// Giới hạn kích thước file (5MB)
const limits = {
  fileSize: 5 * 1024 * 1024 // 5MB
};

// Upload single file
const uploadSingle = multer({
  storage,
  fileFilter,
  limits
}).single('image');

// Upload multiple files (max 10)
const uploadMultiple = multer({
  storage,
  fileFilter,
  limits
}).array('images', 10);

// Upload avatar
const uploadAvatar = multer({
  storage,
  fileFilter,
  limits
}).single('avatar');

module.exports = {
  uploadSingle,
  uploadMultiple,
  uploadAvatar
};

// ============================================
// FILE: backend/src/middleware/validation.middleware.js
// ============================================
const { validationResult } = require('express-validator');

const validate = (req, res, next) => {
  const errors = validationResult(req);
  
  if (!errors.isEmpty()) {
    return res.status(400).json({
      status: 'fail',
      message: 'Dữ liệu không hợp lệ',
      errors: errors.array().map(err => ({
        field: err.param,
        message: err.msg
      }))
    });
  }
  
  next();
};

module.exports = { validate };