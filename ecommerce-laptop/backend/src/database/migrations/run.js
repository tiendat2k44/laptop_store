// Run migrations: execute database/schema.sql
const fs = require('fs');
const path = require('path');
const { Pool } = require('pg');
require('dotenv').config();

async function run() {
  const sql = fs.readFileSync(path.join(__dirname, '..', '..', '..', 'database', 'schema.sql'), 'utf8');
  const pool = new Pool({
    connectionString: process.env.DATABASE_URL
  });

  try {
    console.log('Running migrations...');
    await pool.query(sql);
    console.log('Migrations finished.');
  } catch (err) {
    console.error('Migration error:', err);
  } finally {
    await pool.end();
  }
}

run();