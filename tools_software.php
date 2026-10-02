<?php
require __DIR__ . '/backend/common.php';
require_login();
$filter = 'Tools & Software';

$categories = ['General Discussion', 'Research Methods', 'Career & Funding', 'Paper Reviews', 'Tools & Software'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = required_text('title', 'Title');
        $category = input('category');
        $description = required_text('description', 'Description', 10000);
        if (!in_array($category, $categories, true)) { throw new InvalidArgumentException('Choose a category.'); }
        $conn->execute_query('INSERT INTO discussions (user_id, title, category, description) VALUES (?, ?, ?, ?)', [$user['id'], $title, $category, $description]);
        $id = $conn->insert_id;
        flash('Discussion posted.');
        go('discussion.php?id=' . $id);
    } catch (Throwable $exception) { $error = page_error($exception); }
}
$discussions = $conn->execute_query('SELECT d.*, u.full_name, (SELECT COUNT(*) FROM replies r WHERE r.discussion_id = d.id) AS reply_count FROM discussions d JOIN users u ON u.id = d.user_id WHERE (? = \'\' OR d.category = ?) ORDER BY d.id DESC LIMIT 100', [$filter, $filter])->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Research Portal - Tools and Software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="community_Forum.css">
    <style>
        .nav_a_color {
            color: #4b5563;
            font-size: 14px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background-color: #F9FAFB;
        }

        .page-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 190px;
            flex-shrink: 0;
            background-color: #ffffff;
            border-right: 1px solid #e3e8f2;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 9px;
            border-radius: 9px;
            color: #4b5563;
            font-size: 13px;
        }

        .side-link i {
            width: 14px;
            color: #64748b;
            text-align: center;
        }

        .side-link.active {
            background-color: #e0ebff;
            color: #155eef;
            font-weight: 600;
        }

        .side-link.active i {
            color: #155eef;
        }

        .profile {
            margin-top: auto;
            padding: 15px 8px 2px;
            border-top: 1px solid #edf0f6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .avatar {
            width: 25px;
            height: 25px;
            flex-shrink: 0;
            border-radius: 50%;
            background-color: #165dff;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .profile-name {
            font-size: 10px;
            font-weight: 700;
        }

        .profile-email {
            display: block;
            margin-top: 2px;
            color: #94a0b5;
            font-size: 8px;
        }

        body {
            margin: 0;
            background: #f1f5ff;
            color: #18233d;
            font-size: 13px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .discussion-scrim {
            position: fixed;
            inset: 0;
            z-index: 10;
            background: rgba(16, 20, 29, 0.42);
        }

        .discussion-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 11;
            width: min(100% - 30px, 500px);
            transform: translate(-50%, -50%);
            padding: 22px 18px 18px;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.24);
        }

        .discussion-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 0 12px;
            border-bottom: 1px solid #edf0f6;
        }

        .discussion-modal-header h2 {
            margin: 0;
            color: #14203c;
            font-size: 14px;
        }

        .discussion-close {
            border: 0;
            background: transparent;
            color: #68748a;
            cursor: pointer;
            font-size: 10px;
        }

        .discussion-form {
            padding-top: 11px;
        }

        .discussion-field {
            display: block;
            margin-bottom: 14px;
            color: #1e2b47;
            font-size: 10px;
            font-weight: 700;
        }

        .discussion-required {
            color: #e11d48;
        }

        .discussion-field input,
        .discussion-field select,
        .discussion-field textarea {
            display: block;
            width: 100%;
            margin-top: 5px;
            border: 1px solid #dce5fb;
            border-radius: 10px;
            outline: none;
            background: #eef3ff;
            color: #24324e;
            font: inherit;
            font-size: 11px;
        }

        .discussion-field input,
        .discussion-field select {
            height: 32px;
            padding: 0 12px;
        }

        .discussion-field textarea {
            min-height: 90px;
            padding: 10px 12px;
            resize: vertical;
        }

        .discussion-field input::placeholder,
        .discussion-field textarea::placeholder {
            color: #7e8aa4;
        }

        .discussion-field input:focus,
        .discussion-field select:focus,
        .discussion-field textarea:focus {
            border-color: #8ba9f7;
            box-shadow: 0 0 0 2px rgba(40, 91, 224, 0.1);
        }

        .discussion-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }

        .discussion-actions button {
            height: 32px;
            border: 1px solid #dce4f3;
            border-radius: 16px;
            background: #ffffff;
            color: #1e2b47;
            font: inherit;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .discussion-actions .discussion-submit {
            flex: 1;
            border-color: #8caaf4;
            background: #8caaf4;
            color: #ffffff;
        }

        .discussion-actions .discussion-submit:hover {
            background: #174bc1;
        }

        .discussion-success {
            display: none;
            margin: 6px 0 0;
            color: #00885e;
            font-size: 9px;
            text-align: center;
        }
    </style>

<link rel="stylesheet" href="portal.css"></head>

<body class="portal-page">
    <div class="app-container">


        <div class="page-layout portal-layout">
            <?php require __DIR__ . '/backend/sidebar.php'; ?>


            <!-- Main Content Area -->
            <main class="content-area"><div class="live-content">
<?php show_message(); ?>

<h1><?= e($filter ?: 'Community Forum') ?></h1><p>Ask questions and connect with researchers.</p>
<div class="box row"><a href="Community_Forum.php">All</a><a href="general_discussion.php">General Discussion</a><a href="research_methods.php">Research Methods</a><a href="career_funding.php">Career &amp; Funding</a><a href="paper_reviews.php">Paper Reviews</a><a href="tools_software.php">Tools &amp; Software</a></div>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<details class="box" <?= $error ? 'open' : '' ?>><summary>New Discussion</summary><form method="post">
<label class="field">Title<input name="title" maxlength="255" value="<?= e(input('title')) ?>" required></label>
<label class="field">Category<select name="category"><?php foreach ($categories as $category): ?><option <?= (input('category') ?: $filter) === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
<label class="field">Description<textarea name="description" maxlength="10000" required><?= e(input('description')) ?></textarea></label><button>Post Discussion</button></form></details>
<?php if (!$discussions): ?><p class="box empty">No discussions yet. Start the first one.</p><?php endif; ?>
<?php foreach ($discussions as $discussion): ?><article class="box"><h2><a href="discussion.php?id=<?= $discussion['id'] ?>"><?= e($discussion['title']) ?></a></h2><p class="muted"><?= e($discussion['full_name']) ?> · <?= e($discussion['category']) ?> · <?= e($discussion['created_at']) ?></p><p><?= $discussion['reply_count'] ?> replies</p></article><?php endforeach; ?>
</div></main>
        </div>
    </div>

</body></html>
