<?php
require __DIR__ . '/backend/common.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $cv = null;
    try {
        $name = required_text('fullName', 'Full name');
        $department = required_text('department', 'Department');
        $email = strtolower(required_text('email', 'Email'));
        $role = input('role');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (!in_array($role, ['student', 'teacher'], true)) {
            throw new InvalidArgumentException('Choose Student or Teacher.');
        }
        valid_password($password);
        $exists = $conn->execute_query('SELECT id FROM users WHERE email = ?', [$email])->fetch_assoc();
        if ($exists) {
            throw new InvalidArgumentException('This email is already registered.');
        }
        $cv = save_pdf('cv', 5);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $conn->execute_query('INSERT INTO users (full_name, role, department, email, password, cv_path) VALUES (?, ?, ?, ?, ?, ?)', [$name, $role, $department, $email, $hash, $cv]);
        flash('Account created. You can now sign in.');
        go('signIn.php');
    } catch (Throwable $exception) {
        remove_upload($cv);
        $error = $exception instanceof mysqli_sql_exception && $exception->getCode() === 1062 ? 'This email is already registered.' : page_error($exception);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

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
            <div style="color: #c05332; font-weight: bold; font-size: 36px;">Create account</div>
            <p style="text-align: left; font-size: 16px;"> Join the UIU research Community</p>

            <!-- MySQL Backend এর সাথে যুক্ত Form -->
            <?php show_message(); ?><?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="live-content" style="max-width:340px">
<?php csrf_field(); ?>
<label class="field">Full name<input name="fullName" maxlength="255" value="<?= e(input('fullName')) ?>" required autocomplete="name"></label>
<label class="field">Role<select name="role"><option value="student">Student</option><option value="teacher" <?= input('role') === 'teacher' ? 'selected' : '' ?>>Teacher</option></select></label>
<label class="field">Department<input name="department" maxlength="255" value="<?= e(input('department')) ?>" required></label>
<label class="field">CV / Resume (PDF, up to 5 MB)<input type="file" name="cv" accept="application/pdf,.pdf" required></label>
<label class="field">Email<input type="email" name="email" maxlength="255" value="<?= e(input('email')) ?>" required autocomplete="email"></label>
<label class="field">Password<input type="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password"></label>
<button type="submit">Create Account</button>
</form>

            <p style="font-size: 14px; margin-top: 10px;">Already registered? <a href="signIn.php"
                    style="text-decoration: none; color: #e69275; font-size: 16px;">Sign In</a></p>
        </div>
    </div>
</body>

</html>