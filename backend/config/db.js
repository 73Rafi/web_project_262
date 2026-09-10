const mysql = require('mysql2/promise');

const db = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'research_portal'
});

db.getConnection()
    .then(() => console.log('Connected to MySQL successfully'))
    .catch((err) => console.log("Error connecting to MySQL:", err.message));

module.exports = db;