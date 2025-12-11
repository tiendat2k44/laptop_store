// ============================================
// FILE: backend/src/models/User.js
// ============================================
const db = require('../config/database');
const bcrypt = require('bcryptjs');

class User {
  // Tạo user mới
  static async create(userData) {
    const { email, password, full_name, phone, address, role, shop_name, shop_description } = userData;
    
    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);
    
    const query = `
      INSERT INTO users (email, password, full_name, phone, address, role, shop_name, shop_description)
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      RETURNING user_id, email, full_name, phone, address, role, shop_name, created_at
    `;
    
    const values = [email, hashedPassword, full_name, phone || null, address || null, role, shop_name || null, shop_description || null];
    const result = await db.query(query, values);
    return result.rows[0];
  }

  // Tìm user theo email
  static async findByEmail(email) {
    const query = 'SELECT * FROM users WHERE email = $1';
    const result = await db.query(query, [email]);
    return result.rows[0];
  }

  // Tìm user theo ID
  static async findById(userId) {
    const query = 'SELECT user_id, email, full_name, phone, address, avatar, role, balance, shop_name, shop_description, shop_rating, is_active, created_at FROM users WHERE user_id = $1';
    const result = await db.query(query, [userId]);
    return result.rows[0];
  }

  // Verify password
  static async verifyPassword(password, hashedPassword) {
    return await bcrypt.compare(password, hashedPassword);
  }

  // Cập nhật user
  static async update(userId, updateData) {
    const fields = [];
    const values = [];
    let paramCount = 1;

    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        fields.push(`${key} = $${paramCount}`);
        values.push(updateData[key]);
        paramCount++;
      }
    });

    if (fields.length === 0) return null;

    values.push(userId);
    const query = `
      UPDATE users 
      SET ${fields.join(', ')}, updated_at = CURRENT_TIMESTAMP
      WHERE user_id = $${paramCount}
      RETURNING user_id, email, full_name, phone, address, avatar, role, shop_name, shop_description
    `;

    const result = await db.query(query, values);
    return result.rows[0];
  }

  // Cập nhật balance
  static async updateBalance(userId, amount, type = 'add') {
    const user = await this.findById(userId);
    const newBalance = type === 'add' 
      ? parseFloat(user.balance) + parseFloat(amount)
      : parseFloat(user.balance) - parseFloat(amount);

    const query = 'UPDATE users SET balance = $1 WHERE user_id = $2 RETURNING balance';
    const result = await db.query(query, [newBalance, userId]);
    return result.rows[0].balance;
  }

  // Lấy danh sách users (admin)
  static async getAll(filters = {}) {
    let query = 'SELECT user_id, email, full_name, phone, role, balance, is_active, created_at FROM users WHERE 1=1';
    const values = [];
    let paramCount = 1;

    if (filters.role) {
      query += ` AND role = $${paramCount}`;
      values.push(filters.role);
      paramCount++;
    }

    if (filters.is_active !== undefined) {
      query += ` AND is_active = $${paramCount}`;
      values.push(filters.is_active);
      paramCount++;
    }

    query += ' ORDER BY created_at DESC';

    if (filters.limit) {
      query += ` LIMIT $${paramCount}`;
      values.push(filters.limit);
      paramCount++;
    }

    if (filters.offset) {
      query += ` OFFSET $${paramCount}`;
      values.push(filters.offset);
    }

    const result = await db.query(query, values);
    return result.rows;
  }

  // Đếm số lượng users
  static async count(filters = {}) {
    let query = 'SELECT COUNT(*) FROM users WHERE 1=1';
    const values = [];
    let paramCount = 1;

    if (filters.role) {
      query += ` AND role = $${paramCount}`;
      values.push(filters.role);
    }

    const result = await db.query(query, values);
    return parseInt(result.rows[0].count);
  }
}

module.exports = User;

// ============================================
// FILE: backend/src/models/Product.js
// ============================================
const db = require('../config/database');

class Product {
  // Tạo sản phẩm mới
  static async create(productData) {
    const {
      product_name, slug, description, short_description, price, discount_price,
      category_id, brand_id, shop_id, specifications, images, stock_quantity
    } = productData;

    const query = `
      INSERT INTO products (
        product_name, slug, description, short_description, price, discount_price,
        category_id, brand_id, shop_id, specifications, images, stock_quantity
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
      RETURNING *
    `;

    const values = [
      product_name, slug, description, short_description || null, price, discount_price || null,
      category_id, brand_id, shop_id, JSON.stringify(specifications), images, stock_quantity
    ];

    const result = await db.query(query, values);
    return result.rows[0];
  }

  // Lấy sản phẩm theo ID
  static async findById(productId) {
    const query = `
      SELECT p.*, 
             c.category_name, 
             b.brand_name,
             u.shop_name, u.shop_rating
      FROM products p
      LEFT JOIN categories c ON p.category_id = c.category_id
      LEFT JOIN brands b ON p.brand_id = b.brand_id
      LEFT JOIN users u ON p.shop_id = u.user_id
      WHERE p.product_id = $1
    `;
    
    const result = await db.query(query, [productId]);
    
    if (result.rows.length > 0) {
      // Tăng view count
      await db.query('UPDATE products SET views_count = views_count + 1 WHERE product_id = $1', [productId]);
    }
    
    return result.rows[0];
  }

  // Lấy danh sách sản phẩm với filter
  static async getAll(filters = {}) {
    let query = `
      SELECT p.*, 
             c.category_name, 
             b.brand_name,
             u.shop_name
      FROM products p
      LEFT JOIN categories c ON p.category_id = c.category_id
      LEFT JOIN brands b ON p.brand_id = b.brand_id
      LEFT JOIN users u ON p.shop_id = u.user_id
      WHERE p.status = 'active'
    `;
    
    const values = [];
    let paramCount = 1;

    // Filter by category
    if (filters.category_id) {
      query += ` AND p.category_id = $${paramCount}`;
      values.push(filters.category_id);
      paramCount++;
    }

    // Filter by brand
    if (filters.brand_id) {
      query += ` AND p.brand_id = $${paramCount}`;
      values.push(filters.brand_id);
      paramCount++;
    }

    // Filter by shop
    if (filters.shop_id) {
      query += ` AND p.shop_id = $${paramCount}`;
      values.push(filters.shop_id);
      paramCount++;
    }

    // Filter by price range
    if (filters.min_price) {
      query += ` AND p.price >= $${paramCount}`;
      values.push(filters.min_price);
      paramCount++;
    }

    if (filters.max_price) {
      query += ` AND p.price <= $${paramCount}`;
      values.push(filters.max_price);
      paramCount++;
    }

    // Search by name
    if (filters.search) {
      query += ` AND (p.product_name ILIKE $${paramCount} OR p.description ILIKE $${paramCount})`;
      values.push(`%${filters.search}%`);
      paramCount++;
    }

    // Sorting
    const sortBy = filters.sort_by || 'created_at';
    const sortOrder = filters.sort_order || 'DESC';
    query += ` ORDER BY p.${sortBy} ${sortOrder}`;

    // Pagination
    if (filters.limit) {
      query += ` LIMIT $${paramCount}`;
      values.push(filters.limit);
      paramCount++;
    }

    if (filters.offset) {
      query += ` OFFSET $${paramCount}`;
      values.push(filters.offset);
    }

    const result = await db.query(query, values);
    return result.rows;
  }

  // Cập nhật sản phẩm
  static async update(productId, updateData) {
    const fields = [];
    const values = [];
    let paramCount = 1;

    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        if (key === 'specifications') {
          fields.push(`${key} = $${paramCount}`);
          values.push(JSON.stringify(updateData[key]));
        } else {
          fields.push(`${key} = $${paramCount}`);
          values.push(updateData[key]);
        }
        paramCount++;
      }
    });

    if (fields.length === 0) return null;

    values.push(productId);
    const query = `
      UPDATE products 
      SET ${fields.join(', ')}, updated_at = CURRENT_TIMESTAMP
      WHERE product_id = $${paramCount}
      RETURNING *
    `;

    const result = await db.query(query, values);
    return result.rows[0];
  }

  // Xóa sản phẩm (soft delete)
  static async delete(productId) {
    const query = 'UPDATE products SET status = $1 WHERE product_id = $2 RETURNING product_id';
    const result = await db.query(query, ['inactive', productId]);
    return result.rows[0];
  }

  // Cập nhật stock
  static async updateStock(productId, quantity, operation = 'decrease') {
    const product = await this.findById(productId);
    const newStock = operation === 'decrease'
      ? product.stock_quantity - quantity
      : product.stock_quantity + quantity;

    const query = 'UPDATE products SET stock_quantity = $1 WHERE product_id = $2 RETURNING stock_quantity';
    const result = await db.query(query, [newStock, productId]);
    return result.rows[0];
  }

  // Đếm số lượng sản phẩm
  static async count(filters = {}) {
    let query = 'SELECT COUNT(*) FROM products WHERE status = $1';
    const values = ['active'];
    let paramCount = 2;

    if (filters.category_id) {
      query += ` AND category_id = $${paramCount}`;
      values.push(filters.category_id);
      paramCount++;
    }

    if (filters.brand_id) {
      query += ` AND brand_id = $${paramCount}`;
      values.push(filters.brand_id);
    }

    const result = await db.query(query, values);
    return parseInt(result.rows[0].count);
  }
}

module.exports = Product;