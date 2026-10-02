<?php
require __DIR__ . '/backend/common.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$discussion = $conn->execute_query('SELECT d.*, u.full_name FROM discussions d JOIN users u ON u.id = d.user_id WHERE d.id = ?', [$id])->fetch_assoc();
if (!$discussion) { http_response_code(404); exit('Discussion not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
<body class="portal-page">

  <div class="app-container portal-layout">
    
    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content"><div class="live-content">
<?php show_message(); ?>

<a href="Community_Forum.php">← Community Forum</a><article class="box"><h1><?= e($discussion['title']) ?></h1><p class="muted"><?= e($discussion['full_name']) ?> · <?= e($discussion['category']) ?></p><p><?= nl2br(e($discussion['description'])) ?></p></article>
<h2>Replies (<?= count($replies) ?>)</h2><?php foreach ($replies as $reply): ?><article class="box"><h3><?= e($reply['full_name']) ?></h3><p><?= nl2br(e($reply['body'])) ?></p><time class="muted"><?= e($reply['created_at']) ?></time></article><?php endforeach; ?>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="box"><label class="field">Your reply<textarea name="body" maxlength="5000" required><?= e(input('body')) ?></textarea></label><button>Post Reply</button></form>
</div></main>

  </div>

</body>
</html>