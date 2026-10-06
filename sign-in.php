<?php
require __DIR__ . '/backend/common.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(input('email')));
    $password = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter your email address and password.';
    } else {
        $account = $conn->execute_query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email])->fetch_assoc();
        if (!$account || !password_verify($password, $account['password'])) {
            $error = 'Invalid email or password.';
        } elseif (!(int) $account['is_active']) {
            $error = 'This account is currently unavailable. Please contact an administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $account['id'];
            go($account['role'] === 'admin' ? 'admin-dashboard.php' : 'dashboard.php');
        }
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · UIU Research Hub</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="portal.css">
<style>.auth-brand{position:absolute;top:28px;left:8%;color:#fff;text-decoration:none;font-weight:700;letter-spacing:-.02em}.auth-kicker{color:#bcd0ff!important;font-size:11px!important;font-weight:700;letter-spacing:.11em;text-transform:uppercase}.auth-list{list-style:none;padding:0;margin:23px 0 0!important}.auth-list li:before{content:'✓';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:10px;border-radius:50%;background:rgba(255,255,255,.16);font-size:12px}.auth-form-note{margin-top:18px;color:#667085;font-size:14px}.auth-form-note a{color:#2457d6;font-weight:700;text-decoration:none}.forgot{display:block;margin-top:10px;color:#2457d6;text-decoration:none;font-size:13px;font-weight:700}</style>
</head><body>
<main class="Panel"><section class="left-panel"><a class="auth-brand" href="home.php">UIU Research Hub</a><p class="auth-kicker">United International University</p><h1>Research moves farther when it moves together.</h1><p>Find the work, people, and practical conversations that can move your next question forward.</p><ul class="auth-list"><li>Discover research across disciplines</li><li>Join projects built for collaboration</li><li>Share and discuss work in context</li></ul></section>
<section class="right-panel"><div style="width:min(100%,380px)"><p class="auth-kicker" style="color:#2457d6!important">WELCOME BACK</p><h1>Sign in to your workspace</h1><p style="color:#667085">Continue where your research left off.</p><?php show_message(); ?><?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" novalidate><label>Email address<input type="email" name="email" maxlength="255" value="<?= e(input('email')) ?>" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><a class="forgot" href="forgot-password.php">Forgot your password?</a><button type="submit">Sign in</button></form><p class="auth-form-note">New to the hub? <a href="register.php">Create an account</a></p></div></section></main>
</body></html>
