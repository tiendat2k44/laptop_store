// Seed sample data (users, brands, categories, products)
const { Pool } = require('pg');
const bcrypt = require('bcryptjs');
require('dotenv').config();
const fs = require('fs');
const path = require('path');

const pool = new Pool({ connectionString: process.env.DATABASE_URL });

async function run() {
  try {
    console.log('Seeding database...');

    // sample users (hash password)
    const users = [
      { email: 'admin@laptopstore.com', password: 'Admin@123', full_name: 'Admin', role: 'admin' },
      { email: 'techshop@laptopstore.com', password: 'Shop@123', full_name: 'Tech Shop', role: 'shop', shop_name: 'Tech Shop' },
      { email: 'customer@example.com', password: 'Customer@123', full_name: 'Customer', role: 'customer' }
    ];

    for (let u of users) {
      const hashed = await bcrypt.hash(u.password, 10);
      const existing = await pool.query('SELECT user_id FROM users WHERE email=$1', [u.email]);
      if (existing.rows.length === 0) {
        await pool.query(
          `INSERT INTO users (email, password, full_name, role, shop_name) VALUES ($1,$2,$3,$4,$5)`,
          [u.email, hashed, u.full_name, u.role, u.shop_name || null]
        );
        console.log('Inserted user', u.email);
      } else {
        console.log('User exists', u.email);
      }
    }

    // brands
    const brands = ['Dell', 'HP', 'Lenovo', 'Asus', 'Acer', 'MSI'];
    for (let b of brands) {
      const ex = await pool.query('SELECT brand_id FROM brands WHERE brand_name=$1', [b]);
      if (ex.rows.length === 0) {
        await pool.query('INSERT INTO brands (brand_name) VALUES ($1)', [b]);
      }
    }

    // categories
    const categories = ['Gaming', 'Office', 'Design', 'Student'];
    for (let c of categories) {
      const ex = await pool.query('SELECT category_id FROM categories WHERE category_name=$1', [c]);
      if (ex.rows.length === 0) {
        await pool.query('INSERT INTO categories (category_name) VALUES ($1)', [c]);
      }
    }

    // products (simple)
    const brandsRes = await pool.query('SELECT brand_id, brand_name FROM brands LIMIT 6');
    const categoriesRes = await pool.query('SELECT category_id, category_name FROM categories LIMIT 10');
    const shopRes = await pool.query("SELECT user_id FROM users WHERE role='shop' LIMIT 1");
    const shopId = shopRes.rows[0]?.user_id || null;

    const sampleProducts = [
      {
        product_name: 'Gaming Laptop GX-15',
        short_description: 'Laptop gaming mạnh mẽ, RTX 3060',
        description: 'Mô tả chi tiết Gaming Laptop GX-15.',
        price: 25000000,
        discount_price: 22000000,
        category_name: 'Gaming',
        brand_name: 'MSI',
        stock_quantity: 10,
        images: ['https://via.placeholder.com/300x200?text=GX-15']
      },
      {
        product_name: 'Office Laptop Pro 14',
        short_description: 'Tiện dụng cho văn phòng, pin tốt',
        description: 'Mô tả Office Laptop Pro 14.',
        price: 15000000,
        discount_price: null,
        category_name: 'Office',
        brand_name: 'Dell',
        stock_quantity: 20,
        images: ['https://via.placeholder.com/300x200?text=Office+Pro+14']
      },
      {
        product_name: 'Creator Laptop 16',
        short_description: 'Thiết kế đồ họa, màn 16 inch',
        description: 'Mô tả Creator Laptop 16.',
        price: 35000000,
        discount_price: 32000000,
        category_name: 'Design',
        brand_name: 'Lenovo',
        stock_quantity: 5,
        images: ['https://via.placeholder.com/300x200?text=Creator+16']
      }
    ];

    for (let p of sampleProducts) {
      // get brand and category ids
      const brandRow = await pool.query('SELECT brand_id FROM brands WHERE brand_name=$1 LIMIT 1', [p.brand_name]);
      const catRow = await pool.query('SELECT category_id FROM categories WHERE category_name=$1 LIMIT 1', [p.category_name]);
      const brand_id = brandRow.rows[0]?.brand_id || null;
      const category_id = catRow.rows[0]?.category_id || null;
      // Check already exists
      const ex = await pool.query('SELECT product_id FROM products WHERE product_name=$1', [p.product_name]);
      if (ex.rows.length === 0) {
        await pool.query(
          `INSERT INTO products (product_name, short_description, description, price, discount_price, category_id, brand_id, shop_id, specifications, images, stock_quantity)
           VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)`,
          [
            p.product_name,
            p.short_description,
            p.description,
            p.price,
            p.discount_price,
            category_id,
            brand_id,
            shopId,
            JSON.stringify({ cpu: 'Intel i7', ram: '16GB', storage: '512GB SSD' }),
            p.images,
            p.stock_quantity
          ]
        );
        console.log('Inserted product', p.product_name);
      } else {
        console.log('Product exists', p.product_name);
      }
    }

    console.log('Seeding complete.');
  } catch (err) {
    console.error('Seeding error:', err);
  } finally {
    await pool.end();
  }
}

run();