<?php
require __DIR__ . '/backend/common.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$discussion = $conn->execute_query('SELECT d.*, u.full_name FROM discussions d JOIN users u ON u.id = d.user_id WHERE d.id = ?', [$id])->fetch_assoc();
if (!$discussion) { http_response_code(404); exit('Discussion not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $body = required_text('body', 'Reply', 5000);
        $conn->begin_transaction();
        $conn->execute_query('INSERT INTO replies (discussion_id, user_id, body) VALUES (?, ?, ?)', [$id, $user['id'], $body]);
        if ($discussion['user_id'] != $user['id']) { notify_user($discussion['user_id'], $user['full_name'] . ' replied to your discussion.'); }
        $conn->commit();
        flash('Reply posted.');
        go('discussion.php?id=' . $id);
    } catch (Throwable $exception) { $conn->rollback(); $error = page_error($exception); }
}
$replies = $conn->execute_query('SELECT r.*, u.full_name FROM replies r JOIN users u ON u.id = r.user_id WHERE r.discussion_id = ? ORDER BY r.id', [$id])->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Discussion</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="portal.css"></head>
<body>

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

<a href="Community_Forum.php">← Community Forum</a><article class="box"><h1><?= e($discussion['title']) ?></h1><p class="muted"><?= e($discussion['full_name']) ?> · <?= e($discussion['category']) ?></p><p><?= nl2br(e($discussion['description'])) ?></p></article>
<h2>Replies (<?= count($replies) ?>)</h2><?php foreach ($replies as $reply): ?><article class="box"><h3><?= e($reply['full_name']) ?></h3><p><?= nl2br(e($reply['body'])) ?></p><time class="muted"><?= e($reply['created_at']) ?></time></article><?php endforeach; ?>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="box"><?php csrf_field(); ?><label class="field">Your reply<textarea name="body" maxlength="5000" required><?= e(input('body')) ?></textarea></label><button>Post Reply</button></form>
</div></main>

  </div>

</body>
</html>