<?php
require __DIR__ . '/backend/common.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = strtolower(required_text('email', 'Email'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $email);
        $conn->execute_query('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 15 MINUTE');
        $attempts = $conn->execute_query('SELECT COUNT(*) AS total FROM login_attempts WHERE attempt_key = ?', [$key])->fetch_assoc()['total'];
        if ($attempts >= 5) {
            throw new InvalidArgumentException('Too many attempts. Try again in 15 minutes.');
        }
        $account = $conn->execute_query('SELECT * FROM users WHERE email = ?', [$email])->fetch_assoc();
        if (!$account || !$account['is_active'] || !password_verify($password, $account['password'])) {
            $conn->execute_query('INSERT INTO login_attempts (attempt_key) VALUES (?)', [$key]);
            throw new InvalidArgumentException('Invalid email or password, or the account is disabled.');
        }
        $conn->execute_query('DELETE FROM login_attempts WHERE attempt_key = ?', [$key]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['version'] = $account['session_version'];
        go($account['role'] === 'admin' ? 'admin_index.php' : 'User_dashboard.php');
    } catch (Throwable $exception) {
        $error = page_error($exception);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign-In</title>

    <style>
        ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
<link rel="stylesheet" href="portal.css"></head>

<body>
    <div class="Panel" style="display: flex; width: 100%; height: 100vh;">

        <div class="left-panel"
            style="background-color: #EA5F34; width: 50%; height: 100vh; display: flex; gap:15px 20px;padding: 0 8%; box-sizing: border-box; justify-content: center; align-items: flex-start;flex-direction: column;">
            <h1 style="color: white; font-weight: bold;"> Advanced Research,<br>Together.</h1>
            <p style="color: rgb(243, 238, 238); font-size: 16px;">Join UIU's research
                community to discover papers, Collaborate on <br style="gap: 5px;">projects and make an impact.
            </p>
            <ul>
                <li style="color: white; font-size: 18px; text-align: center;">Access thousand of Research Papers</li>
                <li style="color: white; font-size: 18px; text-align: center;">Collaborate with UIU Researchers</li>
                <li style="color: white; font-size: 18px; text-align: center;">Publish and share your work</li>
            </ul>
        </div>

        <div class="right-panel"
            style="width: 50%; height: 100vh; display: flex; justify-content: center; align-items: center;flex-direction: column;">
            <h1 style="color: #c05332; font-weight: bold;">Welcome Back!</h1>
            <p style="text-align: left;"> Sign in to your UIU Research account</p>

            <!-- অন্যান্য কোড আগের মতই থাকবে, শুধু form অংশটুকু পরিবর্তন করুন -->

            <?php show_message(); ?><?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="live-content" style="max-width:340px">

<label class="field">Email<input type="email" name="email" value="<?= e(input('email')) ?>" maxlength="255" required autocomplete="email"></label>
<label class="field">Password<input type="password" name="password" required autocomplete="current-password"></label>
<p><a href="forgot_password.php">Forgot Password?</a></p>
<button type="submit">Sign In</button>
</form>
            <p style="font-size: 14px;">No account? <a href="register.php"
                    style="text-decoration: none; color: #e69275; font-size: 16px;">Register here</a></p>
        </div>
    </div>

</body>

</html>