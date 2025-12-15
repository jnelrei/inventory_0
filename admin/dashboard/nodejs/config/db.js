const mysql = require('mysql2/promise');

// Database configuration
const dbConfig = {
  host: 'localhost',
  database: 'tci',
  user: 'root',
  password: '',
  charset: 'utf8mb4',
  timezone: '+08:00' // Philippine Time
};

// Create connection pool
const pool = mysql.createPool({
  ...dbConfig,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

module.exports = pool;

