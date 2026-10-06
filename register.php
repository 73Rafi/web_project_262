<?php
require __DIR__ . '/backend/common.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = required_text('fullName', 'Full name');
        $role = input('role');
        $department = required_text('department', 'Department');
        $email = strtolower(required_text('email', 'Email address'));
        $password = $_POST['password'] ?? '';
        if (!in_array($role, ['student', 'teacher'], true)) {
            throw new InvalidArgumentException('Choose Student or Teacher.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('Password must be between 8 and 72 characters.');
        }
        $exists = $conn->execute_query('SELECT id FROM users WHERE email = ? LIMIT 1', [$email])->fetch_assoc();
        if ($exists) {
            throw new InvalidArgumentException('That email address is already registered.');
        }
        $conn->execute_query('INSERT INTO users (full_name, role, department, email, password, cv_path) VALUES (?, ?, ?, ?, ?, \'\')', [$name, $role, $department, $email, password_hash($password, PASSWORD_DEFAULT)]);
        flash('Your account is ready. Please sign in.');
        go('sign-in.php');
    } catch (Throwable $exception) {
        $error = page_error($exception);
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create account · UIU Research Hub</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="portal.css">
<style>.auth-brand{position:absolute;top:28px;left:8%;color:#fff;text-decoration:none;font-weight:700;letter-spacing:-.02em}.auth-kicker{color:#bcd0ff!important;font-size:11px!important;font-weight:700;letter-spacing:.11em;text-transform:uppercase}.auth-list{list-style:none;padding:0;margin:23px 0 0!important}.auth-list li:before{content:'✓';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:10px;border-radius:50%;background:rgba(255,255,255,.16);font-size:12px}.auth-form-note{margin-top:18px;color:#667085;font-size:14px}.auth-form-note a{color:#2457d6;font-weight:700;text-decoration:none}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.form-grid .full{grid-column:1/-1}@media(max-width:500px){.form-grid{grid-template-columns:1fr}}</style>
</head><body>
<main class="Panel"><section class="left-panel"><a class="auth-brand" href="home.php">UIU Research Hub</a><p class="auth-kicker">United International University</p><h1>Give your ideas a place to grow.</h1><p>Build a research profile that makes it easier to discover work, find collaborators, and contribute back to the UIU community.</p><ul class="auth-list"><li>Save research and track your interests</li><li>Publish work through a clear review flow</li><li>Join focused, active projects</li></ul></section>
<section class="right-panel"><div style="width:min(100%,440px)"><p class="auth-kicker" style="color:#2457d6!important">JOIN THE COMMUNITY</p><h1>Create your profile</h1><p style="color:#667085">A few details are all you need to get started.</p><?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" novalidate><div class="form-grid"><label class="full">Full name<input name="fullName" maxlength="255" value="<?= e(input('fullName')) ?>" autocomplete="name" required></label><label>Role<select name="role"><option value="student" <?= input('role') === 'student' ? 'selected' : '' ?>>Student</option><option value="teacher" <?= input('role') === 'teacher' ? 'selected' : '' ?>>Faculty / Teacher</option></select></label><label>Department<input name="department" maxlength="255" value="<?= e(input('department')) ?>" required></label><label class="full">Email address<input type="email" name="email" maxlength="255" value="<?= e(input('email')) ?>" autocomplete="email" required></label><label class="full">Password<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></label></div><button type="submit">Create account</button></form><p class="auth-form-note">Already have an account? <a href="sign-in.php">Sign in</a></p></div></section></main>
</body></html>
