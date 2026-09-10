const express = require('express');
const router = express.Router();
const upload = require('../middlewares/upload');
const authController = require('../controllers/authController');

// Registration API 
router.post('/register', upload.single('cv'), authController.registerUser);

// Login API
router.post('/login', authController.loginUser);

module.exports = router;