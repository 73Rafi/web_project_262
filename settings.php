<?php
require __DIR__ . '/backend/common.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (input('action') === 'password') {
      $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
      $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
      if (!password_verify($current, $user['password'])) {
        throw new InvalidArgumentException('Your current password is incorrect.');
      }
      valid_password($password);
      $conn->execute_query('UPDATE users SET password = ?, session_version = session_version + 1 WHERE id = ?', [password_hash($password, PASSWORD_BCRYPT), $user['id']]);
      $conn->execute_query('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
      unset($_SESSION['user_id'], $_SESSION['version']);
      session_regenerate_id(true);
      flash('Password changed. Please sign in again.');
      go('sign-in.php');
    }
    $name = required_text('fullName', 'Full name');
    $department = required_text('department', 'Department');
    $bio = input('bio');
    if (strlen($bio) > 5000) {
      throw new InvalidArgumentException('Bio is too long.');
    }
    $conn->execute_query('UPDATE users SET full_name = ?, department = ?, bio = ? WHERE id = ?', [$name, $department, $bio, $user['id']]);
    flash('Profile updated.');
    go('settings.php');
  } catch (Throwable $exception) {
    $error = page_error($exception);
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Settings</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="portal.css">
</head>

<body class="portal-page">

  <div class="app-container portal-layout">

    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content">
      <div class="live-content">
        <?php show_message(); ?>

        <h1>Settings</h1>
        <p>Update your profile and password.</p><?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="box">
          <h2>Profile</h2>
          <label class="field">Full name<input name="fullName" maxlength="255" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('fullName') : $user['full_name']) ?>" required></label>
          <label class="field">Email<input value="<?= e($user['email']) ?>" readonly></label>
          <label class="field">Department<input name="department" maxlength="255" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('department') : $user['department']) ?>" required></label>
          <label class="field">Bio<textarea name="bio" maxlength="5000"><?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('bio') : $user['bio']) ?></textarea></label>
          <button name="action" value="profile">Save Changes</button>
        </form>
        <form method="post" class="box">
          <h2>Change Password</h2>
          <label class="field">Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
          <label class="field">New password<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
          <button name="action" value="password">Change Password</button>
        </form>
      </div>
    </main>

  </div>

</body>

</html>
