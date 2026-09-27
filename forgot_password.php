<?php
require __DIR__ . '/backend/common.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $email = strtolower(required_text('email', 'Email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new InvalidArgumentException('Enter a valid email address.'); }
        $account = $conn->execute_query('SELECT id FROM users WHERE email = ? AND is_active = 1', [$email])->fetch_assoc();
        if ($account) {
            $conn->execute_query('INSERT INTO password_resets (user_id) VALUES (?) ON DUPLICATE KEY UPDATE requested_at = CURRENT_TIMESTAMP', [$account['id']]);
        }
        flash('If this email has an active account, a reset request is now with the administrator. Contact your portal admin to verify your identity and receive a reset link.');
        go('forgot_password.php');
    } catch (Throwable $exception) { $error = page_error($exception); }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Forgot Password</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f1f5ff; color: #18233d; font-family: Arial, sans-serif; }
    main { width: min(420px, calc(100% - 32px)); padding: 32px; background: #fff; border: 1px solid #e3e8f2; border-radius: 12px; }
    h1 { margin: 0 0 8px; color: #c05332; font-size: 24px; }
    p { color: #64748b; line-height: 1.5; }
    label { display: block; margin: 20px 0 8px; font-weight: 600; }
    input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; }
    button { width: 100%; margin-top: 20px; padding: 10px; border: 0; border-radius: 4px; background: #c76949; color: #fff; cursor: pointer; }
    a { display: block; margin-top: 18px; color: #c76949; text-align: center; text-decoration: none; }
  </style>
<link rel="stylesheet" href="portal.css"></head>
<body>
  <main>
    <h1>Reset your password</h1>
    <p>Request a reset link from your portal administrator. You will need to verify your identity; no automatic email is sent.</p>
    <?php show_message(); ?><?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post"><?php csrf_field(); ?><label for="email">Account email</label><input type="email" name="email" id="email" maxlength="255" required><button>Request Password Reset</button></form>
    <a href="signIn.php">Back to Sign In</a>
  </main>
</body>
</html>