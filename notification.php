<?php
require __DIR__ . '/backend/common.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
  <link rel="stylesheet" href="portal.css">
</head>

<body class="portal-page">

  <div class="app-container portal-layout">

    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content">
      <div class="live-content">
        <?php show_message(); ?>

        <h1>Notifications</h1>
        <p><?= $unread ?> unread · latest 100 notifications</p>
        <form method="post"><button>Mark All Read</button></form>
        <?php if (!$notifications): ?><p class="box empty">No notifications yet.</p><?php endif; ?>
        <?php foreach ($notifications as $notification): ?><article class="box <?= $notification['is_read'] ? '' : 'unread' ?>">
            <p><?= e($notification['message']) ?></p><time class="muted"><?= e($notification['created_at']) ?></time>
          </article><?php endforeach; ?>
      </div>
    </main>

  </div>

</body>

</html>