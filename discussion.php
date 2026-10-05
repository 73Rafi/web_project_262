<?php

require __DIR__ . '/backend/common.php';
require_login();


// -------------------------------------
// Get discussion ID from URL
// -------------------------------------

$id = $_GET['id'] ?? 0;


// -------------------------------------
// Find discussion
// -------------------------------------

$sql = "SELECT discussions.*, users.full_name
        FROM discussions
        JOIN users
        ON discussions.user_id = users.id
        WHERE discussions.id = ?";

$result = $conn->execute_query($sql, [$id]);

$discussion = $result->fetch_assoc();


// Discussion not found
if (!$discussion) {
    exit('Discussion not found.');
}


// -------------------------------------
// Error message
// -------------------------------------

$error = '';


// -------------------------------------
// Add Reply
// -------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $body = trim($_POST['body'] ?? '');


    // Empty reply check
    if ($body == '') {

        $error = 'Please write a reply.';

    }

    // Maximum 5000 characters
    else if (strlen($body) > 5000) {

        $error = 'Reply is too long.';

    }

    else {

        // Save reply in database
        $sql = "INSERT INTO replies
                (discussion_id, user_id, body)
                VALUES (?, ?, ?)";

        $conn->execute_query(
            $sql,
            [$id, $user['id'], $body]
        );


        // Notify discussion owner
        if ($discussion['user_id'] != $user['id']) {

            $message = $user['full_name']
                     . ' replied to your discussion.';

            notify_user(
                $discussion['user_id'],
                $message
            );
        }


        flash('Reply posted.');

        go('discussion.php?id=' . $id);
    }
}


// -------------------------------------
// Get All Replies
// -------------------------------------

$sql = "SELECT replies.*, users.full_name
        FROM replies
        JOIN users
        ON replies.user_id = users.id
        WHERE replies.discussion_id = ?
        ORDER BY replies.id";

$result = $conn->execute_query($sql, [$id]);

$replies = $result->fetch_all(MYSQLI_ASSOC);

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