// ============================================
// FILE: backend/src/controllers/order.controller.js
// ============================================
const db = require('../config/database');
const Product = require('../models/Product');
const { AppError, catchAsync } = require('../utils/errorHandler');
const { generateOrderCode } = require('../utils/helpers');

// Tạo đơn hàng mới
exports.createOrder = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;
  const {
    items, // [{product_id, quantity}, ...]
    shipping_address,
    shipping_phone,
    shipping_name,
    customer_note,
    payment_method
  } = req.body;

  // Validate input
  if (!items || items.length === 0) {
    return next(new AppError('Giỏ hàng trống', 400));
  }

  if (!shipping_address || !shipping_phone) {
    return next(new AppError('Vui lòng nhập đầy đủ thông tin giao hàng', 400));
  }

  // Start transaction
  const client = await db.pool.connect();
  
  try {
    await client.query('BEGIN');

    // Validate products và tính tổng tiền
    let totalAmount = 0;
    const orderDetails = [];

    for (const item of items) {
      const product = await Product.findById(item.product_id);
      
      if (!product) {
        throw new AppError(`Sản phẩm ID ${item.product_id} không tồn tại`, 404);
      }

      if (product.status !== 'active') {
        throw new AppError(`Sản phẩm "${product.product_name}" không khả dụng`, 400);
      }

      if (product.stock_quantity < item.quantity) {
        throw new AppError(`Sản phẩm "${product.product_name}" không đủ số lượng`, 400);
      }

      const unitPrice = product.discount_price || product.price;
      const subtotal = unitPrice * item.quantity;
      totalAmount += subtotal;

      orderDetails.push({
        product_id: product.product_id,
        product_name: product.product_name,
        product_image: product.images[0] || null,
        shop_id: product.shop_id,
        quantity: item.quantity,
        unit_price: unitPrice,
        subtotal
      });
    }

    // Calculate shipping fee (giả sử cố định 30k)
    const shippingFee = 30000;
    const discountAmount = 0; // TODO: Apply voucher
    const finalAmount = totalAmount + shippingFee - discountAmount;

    // Generate order code
    const orderCode = generateOrderCode();

    // Insert order
    const orderQuery = `
      INSERT INTO orders (
        order_code, customer_id, total_amount, shipping_fee, 
        discount_amount, final_amount, shipping_address, 
        shipping_phone, shipping_name, customer_note, payment_method
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
      RETURNING *
    `;

    const orderValues = [
      orderCode, customerId, totalAmount, shippingFee,
      discountAmount, finalAmount, shipping_address,
      shipping_phone, shipping_name || req.user.full_name, 
      customer_note || null, payment_method
    ];

    const orderResult = await client.query(orderQuery, orderValues);
    const order = orderResult.rows[0];

    // Insert order details
    for (const detail of orderDetails) {
      const detailQuery = `
        INSERT INTO order_details (
          order_id, product_id, shop_id, product_name, 
          product_image, quantity, unit_price, subtotal
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `;

      await client.query(detailQuery, [
        order.order_id,
        detail.product_id,
        detail.shop_id,
        detail.product_name,
        detail.product_image,
        detail.quantity,
        detail.unit_price,
        detail.subtotal
      ]);

      // Update stock quantity
      await client.query(
        'UPDATE products SET stock_quantity = stock_quantity - $1, sold_quantity = sold_quantity + $1 WHERE product_id = $2',
        [detail.quantity, detail.product_id]
      );
    }

    // Clear cart
    await client.query('DELETE FROM cart WHERE customer_id = $1', [customerId]);

    // Create notification for customer
    await client.query(
      `INSERT INTO notifications (user_id, title, message, type, related_id)
       VALUES ($1, $2, $3, $4, $5)`,
      [
        customerId,
        'Đơn hàng mới',
        `Đơn hàng ${orderCode} đã được tạo thành công`,
        'order',
        order.order_id
      ]
    );

    // Create notifications for shops
    const shopIds = [...new Set(orderDetails.map(d => d.shop_id))];
    for (const shopId of shopIds) {
      await client.query(
        `INSERT INTO notifications (user_id, title, message, type, related_id)
         VALUES ($1, $2, $3, $4, $5)`,
        [
          shopId,
          'Đơn hàng mới',
          `Bạn có đơn hàng mới ${orderCode}`,
          'order',
          order.order_id
        ]
      );
    }

    await client.query('COMMIT');

    res.status(201).json({
      status: 'success',
      message: 'Đặt hàng thành công',
      data: {
        order: {
          ...order,
          items: orderDetails
        }
      }
    });

  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
});

// Lấy danh sách đơn hàng (Customer)
exports.getMyOrders = catchAsync(async (req, res, next) => {
  const customerId = req.user.user_id;
  const { status, page = 1, limit = 10 } = req.query;

  let query = `
    SELECT o.*, 
           COUNT(od.order_detail_id) as item_count
    FROM orders o
    LEFT JOIN order_details od ON o.order_id = od.order_id
    WHERE o.customer_id = $1
  `;

  const values = [customerId];
  let paramCount = 2;

  if (status) {
    query += ` AND o.order_status = $${paramCount}`;
    values.push(status);
    paramCount++;
  }

  query += ` GROUP BY o.order_id ORDER BY o.created_at DESC`;

  // Pagination
  const offset = (page - 1) * limit;
  query += ` LIMIT $${paramCount} OFFSET $${paramCount + 1}`;
  values.push(limit, offset);

  const result = await db.query(query, values);

  res.status(200).json({
    status: 'success',
    data: {
      orders: result.rows
    }
  });
});

// Lấy chi tiết đơn hàng
exports.getOrderDetail = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  const userId = req.user.user_id;
  const userRole = req.user.role;

  // Get order
  const orderQuery = 'SELECT * FROM orders WHERE order_id = $1';
  const orderResult = await db.query(orderQuery, [id]);

  if (orderResult.rows.length === 0) {
    return next(new AppError('Không tìm thấy đơn hàng', 404));
  }

  const order = orderResult.rows[0];

  // Check permission
  if (userRole === 'customer' && order.customer_id !== userId) {
    return next(new AppError('Bạn không có quyền xem đơn hàng này', 403));
  }

  // Get order details
  const detailsQuery = `
    SELECT od.*, p.images, p.slug
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.product_id
    WHERE od.order_id = $1
  `;

  const detailsResult = await db.query(detailsQuery, [id]);

  // Filter items by shop if user is shop
  let orderDetails = detailsResult.rows;
  if (userRole === 'shop') {
    orderDetails = orderDetails.filter(item => item.shop_id === userId);
    
    if (orderDetails.length === 0) {
      return next(new AppError('Bạn không có quyền xem đơn hàng này', 403));
    }
  }

  res.status(200).json({
    status: 'success',
    data: {
      order: {
        ...order,
        items: orderDetails
      }
    }
  });
});

// Cập nhật trạng thái đơn hàng (Shop/Admin)
exports.updateOrderStatus = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  const { status } = req.body;
  const userId = req.user.user_id;
  const userRole = req.user.role;

  // Validate status
  const validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
  if (!validStatuses.includes(status)) {
    return next(new AppError('Trạng thái không hợp lệ', 400));
  }

  // Get order
  const order = await db.query('SELECT * FROM orders WHERE order_id = $1', [id]);
  if (order.rows.length === 0) {
    return next(new AppError('Không tìm thấy đơn hàng', 404));
  }

  // Check permission for shop
  if (userRole === 'shop') {
    const orderItems = await db.query(
      'SELECT * FROM order_details WHERE order_id = $1 AND shop_id = $2',
      [id, userId]
    );

    if (orderItems.rows.length === 0) {
      return next(new AppError('Bạn không có quyền cập nhật đơn hàng này', 403));
    }
  }

  // Update status
  const updateData = { order_status: status };
  
  if (status === 'delivered') {
    updateData.payment_status = 'paid';
    updateData.delivered_at = new Date();
  }

  if (status === 'cancelled') {
    updateData.cancelled_at = new Date();
  }

  const fields = Object.keys(updateData).map((key, idx) => `${key} = $${idx + 1}`);
  const values = [...Object.values(updateData), id];

  await db.query(
    `UPDATE orders SET ${fields.join(', ')}, updated_at = CURRENT_TIMESTAMP WHERE order_id = $${values.length}`,
    values
  );

  // Create notification
  await db.query(
    `INSERT INTO notifications (user_id, title, message, type, related_id)
     VALUES ($1, $2, $3, $4, $5)`,
    [
      order.rows[0].customer_id,
      'Cập nhật đơn hàng',
      `Đơn hàng ${order.rows[0].order_code} đã chuyển sang trạng thái: ${status}`,
      'order',
      id
    ]
  );

  res.status(200).json({
    status: 'success',
    message: 'Cập nhật trạng thái đơn hàng thành công'
  });
});

// Hủy đơn hàng (Customer)
exports.cancelOrder = catchAsync(async (req, res, next) => {
  const { id } = req.params;
  const { reason } = req.body;
  const customerId = req.user.user_id;

  const order = await db.query(
    'SELECT * FROM orders WHERE order_id = $1 AND customer_id = $2',
    [id, customerId]
  );

  if (order.rows.length === 0) {
    return next(new AppError('Không tìm thấy đơn hàng', 404));
  }

  // Chỉ cho phép hủy đơn pending hoặc processing
  if (!['pending', 'processing'].includes(order.rows[0].order_status)) {
    return next(new AppError('Không thể hủy đơn hàng này', 400));
  }

  const client = await db.pool.connect();
  
  try {
    await client.query('BEGIN');

    // Update order status
    await client.query(
      `UPDATE orders 
       SET order_status = 'cancelled', 
           cancelled_reason = $1, 
           cancelled_at = CURRENT_TIMESTAMP
       WHERE order_id = $2`,
      [reason, id]
    );

    // Restore stock
    const items = await client.query(
      'SELECT product_id, quantity FROM order_details WHERE order_id = $1',
      [id]
    );

    for (const item of items.rows) {
      await client.query(
        'UPDATE products SET stock_quantity = stock_quantity + $1, sold_quantity = sold_quantity - $1 WHERE product_id = $2',
        [item.quantity, item.product_id]
      );
    }

    await client.query('COMMIT');

    res.status(200).json({
      status: 'success',
      message: 'Hủy đơn hàng thành công'
    });

  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
});

// Lấy đơn hàng của shop
exports.getShopOrders = catchAsync(async (req, res, next) => {
  const shopId = req.user.user_id;
  const { status, page = 1, limit = 20 } = req.query;

  let query = `
    SELECT DISTINCT o.*, u.full_name as customer_name, u.phone as customer_phone
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    LEFT JOIN users u ON o.customer_id = u.user_id
    WHERE od.shop_id = $1
  `;

  const values = [shopId];
  let paramCount = 2;

  if (status) {
    query += ` AND o.order_status = $${paramCount}`;
    values.push(status);
    paramCount++;
  }

  query += ` ORDER BY o.created_at DESC`;

  const offset = (page - 1) * limit;
  query += ` LIMIT $${paramCount} OFFSET $${paramCount + 1}`;
  values.push(limit, offset);

  const result = await db.query(query, values);

  res.status(200).json({
    status: 'success',
    data: {
      orders: result.rows
    }
  });
});