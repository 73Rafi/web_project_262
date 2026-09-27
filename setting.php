<?php
require __DIR__ . '/backend/common.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
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
            go('signIn.php');
        }
        $name = required_text('fullName', 'Full name');
        $department = required_text('department', 'Department');
        $bio = input('bio');
        if (strlen($bio) > 5000) { throw new InvalidArgumentException('Bio is too long.'); }
        $conn->execute_query('UPDATE users SET full_name = ?, department = ?, bio = ? WHERE id = ?', [$name, $department, $bio, $user['id']]);
        flash('Profile updated.');
        go('setting.php');
    } catch (Throwable $exception) { $error = page_error($exception); }
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
<link rel="stylesheet" href="portal.css"></head>
<body>

  <header class="navbar">
    <div class="nav-left">
      <div class="logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>UIU Research Portal</span>
      </div>
    </div>
    
    <div class="nav-right">
      <a href="User_dashboard.php" class="nav-item">Dashboard</a>
      <a href="research-exploer1.php" class="nav-item">Research</a>
      <a href="Project.php" class="nav-item">Projects</a>
      <a href="Community_Forum.php" class="nav-item">Forum</a>
      <a href="upload.php" class="btn-top-upload"><i class="fa-solid fa-upload"></i> Upload</a>
      <button class="icon-btn"><i class="fa-regular fa-moon"></i></button>
      <div class="notification-icon">
        <i class="fa-regular fa-bell"></i>
        
      </div>
      <div class="avatar-circle top-avatar"><?= e(strtoupper(substr($user["full_name"], 0, 1))) ?></div>
    </div>
  </header>

  <div class="app-container">
    
    <aside class="sidebar">
      <nav class="sidebar-menu">
        <a href="User_dashboard.php" class="menu-item">Dashboard</a>
        <a href="research-exploer1.php" class="menu-item">Research Explorer</a>
        <a href="Project.php" class="menu-item">Projects</a>
        <a href="Community_Forum.php" class="menu-item">Community Forum</a>
        <a href="upload.php" class="menu-item"><i class="fa-solid fa-upload"></i> Upload Paper</a>
        <a href="profile.php" class="menu-item"><i class="fa-regular fa-user"></i> My Profile</a>
        <a href="Community_Forum.php" class="menu-item"><i class="fa-regular fa-envelope"></i> Discussions</a>
        <a href="notification.php" class="menu-item"><i class="fa-regular fa-bell"></i> Notifications</a>
        <a href="setting.php" class="menu-item active"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="avatar-circle side-avatar"><?= e(strtoupper(substr($user["full_name"], 0, 1))) ?></div>
          <div class="user-details">
            <span class="user-name"><?= e($user["full_name"]) ?></span>
            <span class="user-email"><?= e($user["email"]) ?></span>
          </div>
        </div>
        <form class="logout-form" method="post" action="logout.php"><?php csrf_field(); ?><button>Sign Out</button></form>
      </div>
    <?php if ($user["role"] === "admin"): ?><a class="side-link menu-item" href="admin_index.php">Admin Panel</a><?php endif; ?></aside>

    <main class="main-content"><div class="live-content">
<?php show_message(); ?>

<h1>Settings</h1><p>Update your profile and password.</p><?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="box"><?php csrf_field(); ?><h2>Profile</h2>
<label class="field">Full name<input name="fullName" maxlength="255" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('fullName') : $user['full_name']) ?>" required></label>
<label class="field">Email<input value="<?= e($user['email']) ?>" readonly></label>
<label class="field">Department<input name="department" maxlength="255" value="<?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('department') : $user['department']) ?>" required></label>
<label class="field">Bio<textarea name="bio" maxlength="5000"><?= e($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'profile' ? input('bio') : $user['bio']) ?></textarea></label>
<button name="action" value="profile">Save Changes</button></form>
<form method="post" class="box"><?php csrf_field(); ?><h2>Change Password</h2>
<label class="field">Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
<label class="field">New password<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
<button name="action" value="password">Change Password</button></form>
</div></main>

  </div>

</body>
</html>