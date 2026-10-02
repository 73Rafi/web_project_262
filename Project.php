<?php
require __DIR__ . '/backend/common.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = required_text('title', 'Project name');
        $description = required_text('description', 'Description', 10000);
        $status = input('status');
        if (!in_array($status, ['Active', 'Recruiting', 'Completed'], true)) {
            throw new InvalidArgumentException('Choose a valid project status.');
        }
        $conn->execute_query('INSERT INTO projects (user_id, title, description, department, status) VALUES (?, ?, ?, ?, ?)', [$user['id'], $title, $description, $user['department'], $status]);
        flash('Project submitted for admin review. You can see it in My Profile.');
        go('profile.php');
    } catch (Throwable $exception) { $error = page_error($exception); }
}
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$projects = $conn->execute_query("SELECT p.*, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.approval = 'approved' AND (? = '' OR p.status = ?) ORDER BY p.id DESC LIMIT 100", [$status, $status])->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Document</title>
    <style>
        .project-main {
            max-width: 1080px;
            padding: 30px 34px 38px;
        }

        .page-heading {
            margin-bottom: 22px;
        }

        .page-heading h1 {
            font-size: 28px;
        }

        .page-heading p {
            font-size: 15px;
        }

        .create-button {
            padding: 10px 17px;
            font-size: 13px;
        }

        .filters {
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-button {
            padding: 8px 16px;
            font-size: 12px;
        }

        .project-grid {
            gap: 18px;
        }

        .project-card {
            min-height: 235px;
            padding: 18px;
        }

        .project-card-header {
            gap: 12px;
        }

        .project-icon {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }

        .project-title {
            font-size: 14px;
        }

        .project-lead {
            margin-top: 4px;
            font-size: 11px;
        }

        .status {
            padding: 4px 9px;
            font-size: 11px;
        }

        .project-description {
            min-height: 51px;
            margin: 16px 0 10px;
            font-size: 12px;
        }

        .tag {
            padding: 4px 9px;
            font-size: 11px;
        }

        .project-info {
            gap: 16px;
            font-size: 11px;
        }

        .progress-track {
            height: 6px;
            margin: 11px 0;
        }

        .details-button {
            padding: 7px 11px;
            font-size: 11px;
        }

        .share-button {
            font-size: 12px;
        }

        * {
            box-sizing: border-box;
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

        .open-sans {
            font-family: "Open Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            font-variation-settings: "wdth" 100;
        }
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

        .project-main {
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            padding: 22px 26px 28px;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .page-heading h1 {
            margin: 0 0 4px;
            color: #18233d;
            font-size: 20px;
        }

        .page-heading p {
            margin: 0;
            color: #68748a;
            font-size: 11px;
        }

        .create-button {
            padding: 8px 14px;
            border-radius: 16px;
            background-color: #e69275;
            color: #ffffff;
            font-size: 10px;
            white-space: nowrap;
        }

        .create-button i {
            margin-right: 5px;
        }

        .filters {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }

        .filter-button {
            padding: 6px 13px;
            border: 1px solid #dce4f3;
            border-radius: 13px;
            background-color: #f7f9ff;
            color: #536078;
            font-size: 9px;
        }

        .filter-button.active {
            border-color: #2057d4;
            background-color: #2057d4;
            color: #ffffff;
        }

        .project-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .project-card {
            min-height: 178px;
            padding: 13px;
            border: 1px solid #dce3f0;
            border-radius: 11px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(32, 53, 92, 0.04);
        }

        .project-card-header {
            display: flex;
            align-items: flex-start;
            gap: 9px;
        }

        .project-icon {
            width: 23px;
            height: 23px;
            flex-shrink: 0;
            border-radius: 50%;
            background-color: #7628e8;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
        }

        .project-card:nth-child(2) .project-icon {
            background-color: #009b70;
        }

        .project-card:nth-child(3) .project-icon {
            background-color: #e87900;
        }

        .project-card:nth-child(4) .project-icon {
            background-color: #009d50;
        }

        .project-title {
            flex: 1;
            color: #1d2943;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.25;
        }

        .project-lead {
            display: block;
            margin-top: 2px;
            color: #68748a;
            font-size: 8px;
            font-weight: 400;
        }

        .status {
            padding: 3px 7px;
            border-radius: 8px;
            background-color: #d6f8e9;
            color: #00885e;
            font-size: 8px;
            white-space: nowrap;
        }

        .status.recruiting {
            background-color: #e4edff;
            color: #155eef;
        }

        .status.completed {
            background-color: #eeeeee;
            color: #5f6570;
        }

        .project-description {
            min-height: 35px;
            margin: 12px 0 7px;
            color: #667289;
            font-size: 9px;
            line-height: 1.45;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 8px;
        }

        .tag {
            padding: 3px 7px;
            border-radius: 8px;
            background-color: #edf2fc;
            color: #52617b;
            font-size: 8px;
        }

        .project-info {
            display: flex;
            gap: 13px;
            color: #68748a;
            font-size: 8px;
        }

        .project-info i {
            margin-right: 3px;
        }

        .progress-track {
            height: 4px;
            margin: 8px 0 8px;
            border-radius: 4px;
            background-color: #e4edff;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background-color: #285be0;
        }

        .progress-80 {
            width: 80%;
        }

        .progress-70 {
            width: 70%;
        }

        .progress-100 {
            width: 100%;
        }

        .project-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .details-button {
            padding: 5px 9px;
            border-radius: 8px;
            background-color: #edf3ff;
            color: #155eef;
            font-size: 8px;
        }

        .details-button.join {
            background-color: #285be0;
            color: #ffffff;
        }

        .share-button {
            color: #536078;
            font-size: 9px;
        }

        @media (max-width: 760px) {
            nav {
                padding: 12px 15px;
            }

            nav > div:last-child {
                gap: 8px !important;
            }

            nav > div:last-child > a:not(:first-child) {
                display: none;
            }

            .sidebar {
                width: 55px;
                padding: 12px 6px;
            }

            .side-link {
                justify-content: center;
                padding: 10px 0;
            }

            .side-link span,
            .profile > div:last-child,
            .profile + a {
                display: none;
            }

            .profile {
                justify-content: center;
                padding: 15px 0 2px;
            }

            .project-main {
                padding: 18px 14px;
            }

            .page-heading {
                align-items: flex-start;
            }

            .project-grid {
                grid-template-columns: 1fr;
            }
        }
        .project-main { max-width: 1080px; padding: 30px 34px 38px; }
        .page-heading { margin-bottom: 22px; }
        .page-heading h1 { font-size: 28px; }
        .page-heading p { font-size: 15px; }
        .create-button { padding: 10px 17px; font-size: 13px; }
        .filters { gap: 10px; margin-bottom: 20px; }
        .filter-button { padding: 8px 16px; font-size: 12px; }
        .project-grid { gap: 18px; }
        .project-card { min-height: 235px; padding: 18px; }
        .project-card-header { gap: 12px; }
        .project-icon { width: 32px; height: 32px; font-size: 12px; }
        .project-title { font-size: 14px; }
        .project-lead { margin-top: 4px; font-size: 11px; }
        .status { padding: 4px 9px; font-size: 11px; }
        .project-description { min-height: 51px; margin: 16px 0 10px; font-size: 12px; }
        .tag { padding: 4px 9px; font-size: 11px; }
        .project-info { gap: 16px; font-size: 11px; }
        .progress-track { height: 6px; margin: 11px 0; }
        .details-button { padding: 7px 11px; font-size: 11px; }
        .share-button { font-size: 12px; }

        @media (max-width: 760px) {
            .project-main { padding: 22px 16px 30px; }
            .page-heading h1 { font-size: 23px; }
            .project-grid { grid-template-columns: 1fr; }
        }
        .project-main { max-width: 1160px; padding: 38px 44px 46px; }
        .page-heading { margin-bottom: 28px; }
        .page-heading h1 { font-size: 34px; }
        .page-heading p { font-size: 17px; }
        .create-button { padding: 12px 20px; font-size: 15px; }
        .filters { gap: 12px; margin-bottom: 25px; }
        .filter-button { padding: 10px 19px; font-size: 14px; }
        .project-grid { gap: 22px; }
        .project-card { min-height: 280px; padding: 23px; }
        .project-card-header { gap: 15px; }
        .project-icon { width: 38px; height: 38px; font-size: 14px; }
        .project-title { font-size: 16px; }
        .project-lead { margin-top: 5px; font-size: 13px; }
        .status { padding: 5px 11px; font-size: 13px; }
        .project-description { min-height: 59px; margin: 20px 0 13px; font-size: 14px; }
        .tag { padding: 5px 11px; font-size: 13px; }
        .project-info { gap: 19px; font-size: 13px; }
        .progress-track { height: 7px; margin: 14px 0; }
        .details-button { padding: 9px 13px; font-size: 13px; }
        .share-button { font-size: 14px; }

        @media (max-width: 760px) {
            .project-main { padding: 26px 18px 34px; }
            .page-heading h1 { font-size: 27px; }
            .project-grid { grid-template-columns: 1fr; }
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(24, 35, 61, .45);
        }

        .modal-overlay.open { display: flex; }

        .project-modal {
            width: min(100%, 520px);
            padding: 26px;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 35px rgba(24, 35, 61, .2);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .modal-header h2 { margin: 0; font-size: 22px; }
        .modal-close { border: 0; background: transparent; color: #64748b; font-size: 22px; cursor: pointer; }
        .project-form label { display: block; margin: 12px 0 6px; font-weight: 600; }
        .project-form input, .project-form textarea, .project-form select {
            width: 100%; padding: 10px; border: 1px solid #d6deeb; border-radius: 7px; font: inherit;
        }
        .project-form textarea { min-height: 90px; resize: vertical; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .form-actions button { padding: 10px 16px; border: 0; border-radius: 7px; cursor: pointer; font: inherit; }
        .form-cancel { background: #eef2f7; color: #334155; }
        .form-submit { background: #155eef; color: #fff; }
    </style>
<link rel="stylesheet" href="portal.css"></head>
<body class="open-sans portal-page">
    <div class="page-layout portal-layout">
        <?php require __DIR__ . '/backend/sidebar.php'; ?>

        <main class="project-main"><div class="live-content">
<?php show_message(); ?>

<h1>Research Projects</h1><p>Find collaboration opportunities and share your project.</p>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<details class="box" <?= $error ? 'open' : '' ?>><summary>Create Project</summary><form method="post">
<label class="field">Project name<input name="title" maxlength="255" required value="<?= e(input('title')) ?>"></label>
<label class="field">Description<textarea name="description" maxlength="10000" required><?= e(input('description')) ?></textarea></label>
<label class="field">Status<select name="status"><?php foreach (['Recruiting', 'Active', 'Completed'] as $option): ?><option <?= input('status') === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label>
<button>Submit for Review</button></form></details>
<div class="box row"><a href="Project.php">All</a><?php foreach (['Active', 'Recruiting', 'Completed'] as $option): ?><a href="Project.php?status=<?= $option ?>"><?= $option ?></a><?php endforeach; ?></div>
<?php if (!$projects): ?><p class="box empty">No published projects in this category yet.</p><?php endif; ?>
<div class="grid"><?php foreach ($projects as $project): ?><article class="box"><span class="tag"><?= e($project['status']) ?></span><h2><?= e($project['title']) ?></h2><p class="muted">Led by <?= e($project['full_name']) ?> · <?= e($project['department']) ?></p><p><?= nl2br(e($project['description'])) ?></p><a class="button" href="project_details.php?id=<?= $project['id'] ?>">View Details</a></article><?php endforeach; ?></div>
</div></main>
    </div>

</body></html>