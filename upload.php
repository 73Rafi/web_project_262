<?php
require __DIR__ . '/backend/common.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $file = null;
    try {
        $title = required_text('title', 'Title');
        $authors = required_text('authors', 'Authors');
        $abstract = required_text('abstract', 'Abstract', 10000);
        $keywords = required_text('keywords', 'Keywords');
        $department = required_text('department', 'Department');
        $category = required_text('category', 'Category', 100);
        $status = input('action') === 'draft' ? 'draft' : 'pending';
        $file = save_pdf('paper', 50);
        $conn->execute_query('INSERT INTO papers (user_id, title, authors, abstract, keywords, department, category, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$user['id'], $title, $authors, $abstract, $keywords, $department, $category, $file, $status]);
        flash($status === 'draft' ? 'Draft saved. Submit it from your profile when ready.' : 'Paper submitted. An admin will review it.');
        go('profile.php');
    } catch (Throwable $exception) {
        remove_upload($file);
        $error = page_error($exception);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Upload Paper</title>
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
        <a href="upload.php" class="menu-item active"><i class="fa-solid fa-upload"></i> Upload Paper</a>
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

<h1>Upload Research Paper</h1><p>Share your research with the UIU community.</p>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<form class="box" method="post" enctype="multipart/form-data">
<?php csrf_field(); ?>
<label class="field">Paper PDF (up to 50 MB)<input type="file" name="paper" accept="application/pdf,.pdf" required></label>
<?php foreach (['title'=>'Paper title', 'authors'=>'Authors', 'keywords'=>'Keywords', 'department'=>'Department', 'category'=>'Category'] as $field=>$label): ?>
<label class="field"><?= e($label) ?><input name="<?= e($field) ?>" maxlength="<?= $field === 'category' ? 100 : 255 ?>" value="<?= e(input($field)) ?>" required></label>
<?php endforeach; ?>
<label class="field">Abstract<textarea name="abstract" maxlength="10000" required><?= e(input('abstract')) ?></textarea></label>
<button name="action" value="publish">Submit for Review</button>
<button name="action" value="draft" class="secondary">Save Draft</button>
</form>
</div></main>

  </div>

</body>
</html>