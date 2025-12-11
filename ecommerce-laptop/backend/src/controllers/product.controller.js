// ============================================
// FILE: backend/src/controllers/product.controller.js
// ============================================
const Product = require('../models/Product');
const { AppError, catchAsync } = require('../utils/errorHandler');
const { generateSlug, paginate } = require('../utils/helpers');

// Lấy danh sách sản phẩm
exports.getAllProducts = catchAsync(async (req, res, next) => {
  const {
    page = 1,
    limit = 20,
    category_id,
    brand_id,
    min_price,
    max_price,
    search,
    sort_by = 'created_at',
    sort_order = 'DESC'
  } = req.query;

  const { limit: queryLimit, offset } = paginate(parseInt(page), parseInt(limit));

  const filters = {
    category_id,
    brand_id,
    min_price,
    max_price,
    search,
    sort_by,
    sort_order,
    limit: queryLimit,
    offset
  };

  const products = await Product.getAll(filters);
  const total = await Product.count({ category_id, brand_id, search });

  res.status(200).json({
    status: 'success',
    data: {
      products,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        totalPages: Math.ceil(total / limit)
      }
    }
  });
});

// Lấy chi tiết sản phẩm
exports.getProduct = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  
  const product = await Product.findById(id);

  if (!product) {
    return next(new AppError('Không tìm thấy sản phẩm', 404));
  }

  res.status(200).json({
    status: 'success',
    data: { product }
  });
});

// Tạo sản phẩm mới (Shop/Admin)
exports.createProduct = catchAsync(async (req, res, next) => {
  const {
    product_name,
    description,
    short_description,
    price,
    discount_price,
    category_id,
    brand_id,
    specifications,
    images,
    stock_quantity
  } = req.body;

  // Generate slug
  const slug = generateSlug(product_name);

  // Shop_id từ user đăng nhập
  const shop_id = req.user.role === 'admin' ? req.body.shop_id : req.user.user_id;

  const productData = {
    product_name,
    slug,
    description,
    short_description,
    price,
    discount_price,
    category_id,
    brand_id,
    shop_id,
    specifications,
    images: images || [],
    stock_quantity
  };

  const newProduct = await Product.create(productData);

  res.status(201).json({
    status: 'success',
    message: 'Tạo sản phẩm thành công',
    data: { product: newProduct }
  });
});

// Cập nhật sản phẩm
exports.updateProduct = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  
  // Kiểm tra sản phẩm tồn tại
  const product = await Product.findById(id);
  if (!product) {
    return next(new AppError('Không tìm thấy sản phẩm', 404));
  }

  // Kiểm tra quyền (chỉ shop owner hoặc admin)
  if (req.user.role !== 'admin' && product.shop_id !== req.user.user_id) {
    return next(new AppError('Bạn không có quyền cập nhật sản phẩm này', 403));
  }

  const updateData = { ...req.body };
  
  // Update slug nếu đổi tên
  if (updateData.product_name) {
    updateData.slug = generateSlug(updateData.product_name);
  }

  const updatedProduct = await Product.update(id, updateData);

  res.status(200).json({
    status: 'success',
    message: 'Cập nhật sản phẩm thành công',
    data: { product: updatedProduct }
  });
});

// Xóa sản phẩm
exports.deleteProduct = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  
  const product = await Product.findById(id);
  if (!product) {
    return next(new AppError('Không tìm thấy sản phẩm', 404));
  }

  // Kiểm tra quyền
  if (req.user.role !== 'admin' && product.shop_id !== req.user.user_id) {
    return next(new AppError('Bạn không có quyền xóa sản phẩm này', 403));
  }

  await Product.delete(id);

  res.status(200).json({
    status: 'success',
    message: 'Xóa sản phẩm thành công'
  });
});

// Tìm kiếm sản phẩm
exports.searchProducts = catchAsync(async (req, res, next) => {
  const { q, page = 1, limit = 20 } = req.query;

  if (!q) {
    return next(new AppError('Vui lòng nhập từ khóa tìm kiếm', 400));
  }

  const { limit: queryLimit, offset } = paginate(parseInt(page), parseInt(limit));

  const products = await Product.getAll({
    search: q,
    limit: queryLimit,
    offset
  });

  const total = await Product.count({ search: q });

  res.status(200).json({
    status: 'success',
    data: {
      products,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        totalPages: Math.ceil(total / limit)
      }
    }
  });
});

// ============================================
// FILE: backend/src/controllers/cart.controller.js
// ============================================
const db = require('../config/database');

// Lấy giỏ hàng
exports.getCart = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;

  const query = `
    SELECT c.cart_id, c.quantity, c.added_at,
           p.product_id, p.product_name, p.price, p.discount_price, 
           p.images, p.stock_quantity, p.status,
           u.shop_name
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    JOIN users u ON p.shop_id = u.user_id
    WHERE c.customer_id = $1
    ORDER BY c.added_at DESC
  `;

  const result = await db.query(query, [customerId]);

  // Tính tổng tiền
  let totalAmount = 0;
  const cartItems = result.rows.map(item => {
    const itemPrice = item.discount_price || item.price;
    const subtotal = itemPrice * item.quantity;
    totalAmount += subtotal;

    return {
      ...item,
      unit_price: itemPrice,
      subtotal
    };
  });

  res.status(200).json({
    status: 'success',
    data: {
      cart: cartItems,
      summary: {
        totalItems: cartItems.length,
        totalAmount
      }
    }
  });
});

// Thêm vào giỏ hàng
exports.addToCart = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;
  const { product_id, quantity = 1 } = req.body;

  // Kiểm tra sản phẩm tồn tại
  const product = await Product.findById(product_id);
  if (!product) {
    return next(new AppError('Không tìm thấy sản phẩm', 404));
  }

  // Kiểm tra tồn kho
  if (product.stock_quantity < quantity) {
    return next(new AppError('Sản phẩm không đủ số lượng', 400));
  }

  // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
  const existingItem = await db.query(
    'SELECT * FROM cart WHERE customer_id = $1 AND product_id = $2',
    [customerId, product_id]
  );

  if (existingItem.rows.length > 0) {
    // Cập nhật số lượng
    const newQuantity = existingItem.rows[0].quantity + quantity;
    
    if (product.stock_quantity < newQuantity) {
      return next(new AppError('Sản phẩm không đủ số lượng', 400));
    }

    await db.query(
      'UPDATE cart SET quantity = $1 WHERE cart_id = $2',
      [newQuantity, existingItem.rows[0].cart_id]
    );
  } else {
    // Thêm mới vào giỏ hàng
    await db.query(
      'INSERT INTO cart (customer_id, product_id, quantity) VALUES ($1, $2, $3)',
      [customerId, product_id, quantity]
    );
  }

  res.status(200).json({
    status: 'success',
    message: 'Đã thêm vào giỏ hàng'
  });
});

// Cập nhật số lượng
exports.updateCartItem = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;
  const { productId } = req.params;
  const { quantity } = req.body;

  if (quantity < 1) {
    return next(new AppError('Số lượng phải lớn hơn 0', 400));
  }

  // Kiểm tra sản phẩm
  const product = await Product.findById(productId);
  if (!product) {
    return next(new AppError('Không tìm thấy sản phẩm', 404));
  }

  if (product.stock_quantity < quantity) {
    return next(new AppError('Sản phẩm không đủ số lượng', 400));
  }

  // Cập nhật
  const result = await db.query(
    'UPDATE cart SET quantity = $1 WHERE customer_id = $2 AND product_id = $3 RETURNING *',
    [quantity, customerId, productId]
  );

  if (result.rows.length === 0) {
    return next(new AppError('Không tìm thấy sản phẩm trong giỏ hàng', 404));
  }

  res.status(200).json({
    status: 'success',
    message: 'Cập nhật giỏ hàng thành công'
  });
});

// Xóa khỏi giỏ hàng
exports.removeFromCart = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;
  const { productId } = req.params;

  const result = await db.query(
    'DELETE FROM cart WHERE customer_id = $1 AND product_id = $2 RETURNING *',
    [customerId, productId]
  );

  if (result.rows.length === 0) {
    return next(new AppError('Không tìm thấy sản phẩm trong giỏ hàng', 404));
  }

  res.status(200).json({
    status: 'success',
    message: 'Đã xóa khỏi giỏ hàng'
  });
});

// Xóa toàn bộ giỏ hàng
exports.clearCart = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;

  await db.query('DELETE FROM cart WHERE customer_id = $1', [customerId]);

  res.status(200).json({
    status: 'success',
    message: 'Đã xóa toàn bộ giỏ hàng'
  });
});