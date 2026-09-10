const express = require('express');
const cors = require('cors');

// রুট ইম্পোর্ট
const authRoutes = require('./routes/authRoutes');

const app = express();

// Middlewares
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes ব্যবহার করা (এতে লিংকের আগে /api/auth বসবে)
app.use('/api/auth', authRoutes);

// Server start
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});