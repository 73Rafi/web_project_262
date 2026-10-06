<?php
require __DIR__ . '/backend/common.php';
header('Referrer-Policy: no-referrer');
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
$error = '';
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $reset = $conn->execute_query('SELECT r.user_id FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.expires_at > NOW() AND u.is_active = 1', [hash('sha256', $token)])->fetch_assoc();
}
if (!$reset) {
    http_response_code(400);
    $error = 'This reset link is invalid, expired, or already used. Request a new link.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    try {
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        valid_password($password);
        $conn->begin_transaction();
        // Lock this request so the same link cannot be used twice.
        $reset = $conn->execute_query('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE', [hash('sha256', $token)])->fetch_assoc();
        if (!$reset) {
            throw new InvalidArgumentException('This reset link has already been used.');
        }
        $conn->execute_query('UPDATE users SET password = ?, session_version = session_version + 1 WHERE id = ?', [password_hash($password, PASSWORD_BCRYPT), $reset['user_id']]);
        $conn->execute_query('DELETE FROM password_resets WHERE user_id = ?', [$reset['user_id']]);
        $conn->commit();
        unset($_SESSION['user_id'], $_SESSION['version']);
        session_regenerate_id(true);
        flash('Password reset. Sign in with your new password.');
        go('sign-in.php');
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = page_error($exception);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link rel="stylesheet" href="portal.css">
</head>

<body style="font-family:Arial,sans-serif;background:#f5f6f8;padding:30px">
    <main class="live-content" style="max-width:440px">
        <div class="box">
            <h1>Reset Password</h1>
            <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
            <?php if ($reset): ?><form method="post"><label class="field">New password<input type="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password"></label><button>Save New Password</button></form><?php endif; ?>
            <p><a href="sign-in.php">Back to Sign In</a></p>
        </div>
    </main>
</body>

</html>
