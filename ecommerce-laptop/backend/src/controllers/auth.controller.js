// ============================================
// FILE: backend/src/controllers/auth.controller.js
// ============================================
const User = require('../models/User');
const { generateToken } = require('../config/jwt');
const { AppError, catchAsync } = require('../utils/errorHandler');
const { generateToken: generateRandomToken } = require('../utils/helpers');

// Đăng ký
exports.register = catchAsync(async (req, res, next) => {
  const { email, password, full_name, phone, address, role, shop_name, shop_description } = req.body;

  // Kiểm tra email đã tồn tại
  const existingUser = await User.findByEmail(email);
  if (existingUser) {
    return next(new AppError('Email đã được sử dụng', 400));
  }

  // Tạo user mới
  const userData = {
    email,
    password,
    full_name,
    phone,
    address,
    role: role || 'customer',
    shop_name,
    shop_description
  };

  const newUser = await User.create(userData);

  // Generate JWT token
  const token = generateToken({ userId: newUser.user_id, role: newUser.role });

  res.status(201).json({
    status: 'success',
    message: 'Đăng ký thành công',
    data: {
      user: newUser,
      token
    }
  });
});

// Đăng nhập
exports.login = catchAsync(async (req, res, next) => {
  const { email, password } = req.body;

  // Validate input
  if (!email || !password) {
    return next(new AppError('Vui lòng nhập email và mật khẩu', 400));
  }

  // Tìm user
  const user = await User.findByEmail(email);
  if (!user) {
    return next(new AppError('Email hoặc mật khẩu không đúng', 401));
  }

  // Kiểm tra account active
  if (!user.is_active) {
    return next(new AppError('Tài khoản đã bị khóa', 403));
  }

  // Verify password
  const isPasswordValid = await User.verifyPassword(password, user.password);
  if (!isPasswordValid) {
    return next(new AppError('Email hoặc mật khẩu không đúng', 401));
  }

  // Generate token
  const token = generateToken({ userId: user.user_id, role: user.role });

  // Remove password from response
  delete user.password;

  res.status(200).json({
    status: 'success',
    message: 'Đăng nhập thành công',
    data: {
      user,
      token
    }
  });
});

// Lấy thông tin user hiện tại
exports.getMe = catchAsync(async (req, res, next) => {
  const user = await User.findById(req.user.user_id);

  if (!user) {
    return next(new AppError('Không tìm thấy người dùng', 404));
  }

  res.status(200).json({
    status: 'success',
    data: { user }
  });
});

// Đổi mật khẩu
exports.changePassword = catchAsync(async (req, res, next) => {
  const { current_password, new_password } = req.body;

  if (!current_password || !new_password) {
    return next(new AppError('Vui lòng nhập đầy đủ thông tin', 400));
  }

  // Lấy user với password
  const user = await User.findByEmail(req.user.email);

  // Verify current password
  const isPasswordValid = await User.verifyPassword(current_password, user.password);
  if (!isPasswordValid) {
    return next(new AppError('Mật khẩu hiện tại không đúng', 401));
  }

  // Hash new password
  const bcrypt = require('bcryptjs');
  const hashedPassword = await bcrypt.hash(new_password, 10);

  // Update password
  await User.update(req.user.user_id, { password: hashedPassword });

  res.status(200).json({
    status: 'success',
    message: 'Đổi mật khẩu thành công'
  });
});

// Quên mật khẩu
exports.forgotPassword = catchAsync(async (req, res, next) => {
  const { email } = req.body;

  const user = await User.findByEmail(email);
  if (!user) {
    return next(new AppError('Không tìm thấy email này trong hệ thống', 404));
  }

  // Generate reset token
  const resetToken = generateRandomToken();
  const resetExpires = new Date(Date.now() + 3600000); // 1 hour

  // Save token to database
  await User.update(user.user_id, {
    reset_password_token: resetToken,
    reset_password_expires: resetExpires
  });

  // TODO: Send email with reset link
  // const resetUrl = `${process.env.FRONTEND_URL}/reset-password?token=${resetToken}`;

  res.status(200).json({
    status: 'success',
    message: 'Link đặt lại mật khẩu đã được gửi đến email của bạn',
    data: { resetToken } // Remove in production
  });
});

// Reset mật khẩu
exports.resetPassword = catchAsync(async (req, res, next) => {
  const { token, new_password } = req.body;

  if (!token || !new_password) {
    return next(new AppError('Token và mật khẩu mới là bắt buộc', 400));
  }

  // Find user by reset token
  const db = require('../config/database');
  const result = await db.query(
    'SELECT * FROM users WHERE reset_password_token = $1 AND reset_password_expires > NOW()',
    [token]
  );

  if (result.rows.length === 0) {
    return next(new AppError('Token không hợp lệ hoặc đã hết hạn', 400));
  }

  const user = result.rows[0];

  // Hash new password
  const bcrypt = require('bcryptjs');
  const hashedPassword = await bcrypt.hash(new_password, 10);

  // Update password and clear reset token
  await User.update(user.user_id, {
    password: hashedPassword,
    reset_password_token: null,
    reset_password_expires: null
  });

  res.status(200).json({
    status: 'success',
    message: 'Đặt lại mật khẩu thành công'
  });
});

// Đăng xuất (client-side xóa token)
exports.logout = catchAsync(async (req, res, next) => {
  res.status(200).json({
    status: 'success',
    message: 'Đăng xuất thành công'
  });
});