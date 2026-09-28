<?php
require __DIR__ . '/backend/common.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$project = $conn->execute_query('SELECT p.*, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id])->fetch_assoc();
if (!$project || ($project['approval'] !== 'approved' && $project['user_id'] != $user['id'] && $user['role'] !== 'admin')) {
    http_response_code(404); exit('Project not found.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        if (input('action') === 'status') {
            if ($project['user_id'] != $user['id']) { http_response_code(403); exit('Only the project owner can change its status.'); }
            $status = input('status');
            if (!in_array($status, ['Active', 'Recruiting', 'Completed'], true)) { throw new InvalidArgumentException('Invalid status.'); }
            $conn->execute_query('UPDATE projects SET status = ? WHERE id = ? AND user_id = ?', [$status, $id, $user['id']]);
            flash('Project status updated.');
        } elseif (input('action') === 'leave') {
            $conn->execute_query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$id, $user['id']]);
            flash('You left the project.');
        } else {
            if ($project['approval'] !== 'approved' || $project['status'] !== 'Recruiting' || $project['user_id'] == $user['id']) {
                throw new InvalidArgumentException('This project is not accepting members.');
            }
            $conn->begin_transaction();
            $conn->execute_query('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)', [$id, $user['id']]);
            if ($conn->affected_rows) { notify_user($project['user_id'], $user['full_name'] . ' joined your project.'); }
            $conn->commit();
            flash('You joined the project.');
        }
        go('project_details.php?id=' . $id);
    } catch (Throwable $exception) { $conn->rollback(); $error = page_error($exception); }
}
$members = $conn->execute_query('SELECT u.id, u.full_name FROM project_members m JOIN users u ON u.id = m.user_id WHERE m.project_id = ?', [$id])->fetch_all(MYSQLI_ASSOC);
$joined = in_array($user['id'], array_column($members, 'id'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Project Details</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="portal.css"></head>
<body class="portal-page">

  <div class="app-container portal-layout">
    
    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content"><div class="live-content">
<?php show_message(); ?>

<a href="Project.php">← All projects</a><section class="box"><h1><?= e($project['title']) ?></h1><p>Led by <?= e($project['full_name']) ?></p><p><span class="tag"><?= e($project['status']) ?></span> <span class="tag"><?= e($project['approval']) ?></span></p><p><?= nl2br(e($project['description'])) ?></p></section>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<?php if ($project['user_id'] == $user['id']): ?><form class="box" method="post"><?php csrf_field(); ?><label class="field">Project status<select name="status"><?php foreach (['Active', 'Recruiting', 'Completed'] as $option): ?><option <?= $project['status'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><button name="action" value="status">Update Status</button></form>
<?php elseif ($joined || ($project['approval'] === 'approved' && $project['status'] === 'Recruiting')): ?><form method="post"><?php csrf_field(); ?><button name="action" value="<?= $joined ? 'leave' : 'join' ?>"><?= $joined ? 'Leave Project' : 'Join Project' ?></button></form><?php endif; ?>
<section class="box"><h2>Members (<?= count($members) ?>)</h2><?php if (!$members): ?><p>No members yet.</p><?php endif; ?><?php foreach ($members as $member): ?><p><?= e($member['full_name']) ?></p><?php endforeach; ?></section>
</div></main>

  </div>

</body>
</html>