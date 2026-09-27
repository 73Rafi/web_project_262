<?php
require __DIR__ . '/backend/common.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $conn->execute_query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
    flash('All notifications marked as read.');
    go('notification.php');
}
$notifications = $conn->execute_query('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 100', [$user['id']])->fetch_all(MYSQLI_ASSOC);
$unread = $conn->execute_query('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']])->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Notifications</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="portal.css"></head>
<body>

  <header class="navbar">
    <div class="nav-left">
      <div class="logo">
        <span class="logo-mark">UIU</span>
        <span>UIU Research Portal</span>
      </div>
    </div>
    
    <div class="nav-right">
      <a href="User_dashboard.php" class="nav-item">Dashboard</a>
      <a href="research-exploer1.php" class="nav-item">Research</a>
      <a href="Project.php" class="nav-item">Projects</a>
      <a href="Community_Forum.php" class="nav-item">Forum</a>
      <a href="upload.php" class="btn-top-upload"><i class="fa-solid fa-upload"></i> Upload</a>
    </div>
  </header>

  <div class="app-container">
    
    <aside class="sidebar">
    <nav class="sidebar-menu">
        <a href="User_dashboard.php" class="menu-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="research-exploer1.php" class="menu-item"><i class="fa-solid fa-book-open"></i> Research Explorer</a>
        <a href="Project.php" class="menu-item"><i class="fa-solid fa-folder"></i> Projects</a>
        <a href="Community_Forum.php" class="menu-item"><i class="fa-regular fa-comments"></i> Community Forum</a>
        <a href="upload.php" class="menu-item"><i class="fa-solid fa-upload"></i> Upload Paper</a>
        <a href="profile.php" class="menu-item"><i class="fa-regular fa-user"></i> My Profile</a>
        <a href="notification.php" class="menu-item active"><i class="fa-regular fa-bell"></i> Notifications</a>
        <a href="setting.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
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

<h1>Notifications</h1><p><?= $unread ?> unread · latest 100 notifications</p>
<form method="post"><?php csrf_field(); ?><button>Mark All Read</button></form>
<?php if (!$notifications): ?><p class="box empty">No notifications yet.</p><?php endif; ?>
<?php foreach ($notifications as $notification): ?><article class="box <?= $notification['is_read'] ? '' : 'unread' ?>"><p><?= e($notification['message']) ?></p><time class="muted"><?= e($notification['created_at']) ?></time></article><?php endforeach; ?>
</div></main>

  </div>

</body>
</html>