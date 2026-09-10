const bcrypt = require('bcryptjs');
const db = require('../config/db');

// Registration Logic
exports.registerUser = async (req, res) => {
    try {
        const { fullName, role, department, email, password } = req.body;

        const [existingUser] = await db.execute('SELECT * FROM users WHERE email=?', [email]);
        if (existingUser.length > 0) {
            return res.status(400).json({ message: "User already exists" });
        }
        if (!req.file) {
            return res.status(400).json({ message: "CV File is required" });
        }

        const hashedPassword = await bcrypt.hash(password, 10);
        const cvPath = req.file.path;

        const insertQuery = `INSERT INTO users (full_name, role, department, email, password, cv_path) VALUES (?, ?, ?, ?, ?, ?)`;
        await db.execute(insertQuery, [fullName, role, department, email, hashedPassword, cvPath]);

        res.redirect('http://127.0.0.1:5500/signIn.html');
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: "Internal server error" });
    }
};

// Sign-In (Login) Logic
exports.loginUser = async (req, res) => {
    try {
        const { email, password } = req.body;

        // ১. ইমেইল দিয়ে ইউজার খুঁজুন
        const [users] = await db.execute('SELECT * FROM users WHERE email=?', [email]);
        if (users.length === 0) {
            return res.status(400).json({ message: "Invalid email or password" });
        }

        const user = users[0];

        // ২. পাসওয়ার্ড চেক করুন (ডাটাবেসের Hash পাসওয়ার্ডের সাথে ইউজারের দেওয়া পাসওয়ার্ড)
        const isMatch = await bcrypt.compare(password, user.password);
        if (!isMatch) {
            return res.status(400).json({ message: "Invalid email or password" });
        }

        // ৩. লগইন সফল হলে ড্যাশবোর্ডে পাঠিয়ে দিন
        res.redirect('http://127.0.0.1:5500/User_dashboard.html');

    } catch (error) {
        console.error(error);
        res.status(500).json({ error: "Internal server error" });
    }
};