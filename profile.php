<?php
require __DIR__ . '/backend/common.php';
require_login();

$papers = $conn->execute_query('SELECT * FROM papers WHERE user_id = ? ORDER BY id DESC', [$user['id']])->fetch_all(MYSQLI_ASSOC);
$projects = $conn->execute_query('SELECT * FROM projects WHERE user_id = ? ORDER BY id DESC', [$user['id']])->fetch_all(MYSQLI_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) input('paper_id');
    $conn->execute_query("UPDATE papers SET status = 'pending' WHERE id = ? AND user_id = ? AND status IN ('draft', 'rejected')", [$id, $user['id']]);
    flash($conn->affected_rows ? 'Paper submitted for review.' : 'Paper could not be submitted.');
    go('profile.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - My Profile</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="portal.css"></head>
<body class="portal-page">

  <div class="app-container portal-layout">
    
    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content"><div class="live-content">
<?php show_message(); ?>

<h1>My Profile</h1><section class="box"><h2><?= e($user['full_name']) ?></h2>
<p><?= e(ucfirst($user['role'])) ?> · <?= e($user['department']) ?></p><p><?= e($user['email']) ?></p>
<p><?= nl2br(e($user['bio'] ?: 'Add your research interests in Settings.')) ?></p>
<a class="button" href="setting.php">Edit Profile</a>
<?php if ($user['cv_path'] !== ''): ?>
<a class="button secondary" href="download.php?type=cv&amp;id=<?= $user['id'] ?>">Download My CV</a>
<?php endif; ?></section>
<h2>My Papers (<?= count($papers) ?>)</h2>
<?php if (!$papers): ?><p class="box empty">You have not uploaded any papers yet.</p><?php endif; ?>
<?php foreach ($papers as $paper): ?><article class="box"><h3><?= e($paper['title']) ?></h3><p><span class="tag"><?= e(ucfirst($paper['status'])) ?></span></p>
<a href="download.php?type=paper&amp;id=<?= $paper['id'] ?>">Download PDF</a>
<?php if (in_array($paper['status'], ['draft', 'rejected'], true)): ?><form class="inline" method="post"><input type="hidden" name="paper_id" value="<?= $paper['id'] ?>"><button>Submit for Review</button></form><?php endif; ?>
</article><?php endforeach; ?>
<h2>My Projects (<?= count($projects) ?>)</h2><?php foreach ($projects as $project): ?><div class="box"><a href="project_details.php?id=<?= $project['id'] ?>"><?= e($project['title']) ?></a> <span class="tag"><?= e($project['approval']) ?></span></div><?php endforeach; ?>
</div></main>

  </div>

</body>
</html>
