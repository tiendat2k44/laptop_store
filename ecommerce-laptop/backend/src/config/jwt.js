require('dotenv').config();
const jwt = require('jsonwebtoken');
const SECRET = process.env.JWT_SECRET || 'dev_secret';
const EXPIRES_IN = process.env.JWT_EXPIRE || '7d';

function sign(payload) {
  return jwt.sign(payload, SECRET, { expiresIn: EXPIRES_IN });
}

function verify(token) {
  return jwt.verify(token, SECRET);
}

module.exports = { sign, verify };