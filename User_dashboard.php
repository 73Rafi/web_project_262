<?php
require __DIR__ . '/backend/common.php';
require_login();

$paper_count = $conn->execute_query("SELECT COUNT(*) AS total FROM papers WHERE user_id = ? AND status = 'approved'", [$user['id']])->fetch_assoc()['total'];
$project_count = $conn->execute_query('SELECT COUNT(*) AS total FROM projects WHERE user_id = ?', [$user['id']])->fetch_assoc()['total'];
$discussion_count = $conn->execute_query('SELECT COUNT(*) AS total FROM discussions WHERE user_id = ?', [$user['id']])->fetch_assoc()['total'];
$saved_count = $conn->execute_query('SELECT COUNT(*) AS total FROM saved_papers WHERE user_id = ?', [$user['id']])->fetch_assoc()['total'];
$activity = $conn->execute_query('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$user['id']])->fetch_all(MYSQLI_ASSOC);
$recent = $conn->query("SELECT id, title, authors FROM papers WHERE status = 'approved' ORDER BY id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>User Dashboard</title>
    <style>
        .dashboard-main {
            max-width: 1080px;
            padding: 30px 34px 38px;
        }

        .welcome h1 {
            font-size: 28px;
        }

        .welcome p {
            margin-bottom: 24px;
            font-size: 15px;
        }

        .quick-actions {
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-card {
            min-height: 100px;
            gap: 10px;
            font-size: 14px;
        }

        .quick-card i {
            width: 32px;
            height: 32px;
            font-size: 15px;
        }

        .content-grid {
            gap: 18px;
        }

        .panel {
            padding: 21px;
        }

        .panel h2 {
            margin-bottom: 17px;
            font-size: 16px;
        }

        .activity {
            gap: 13px;
            padding: 13px 0;
            font-size: 14px;
        }

        .activity i {
            width: 29px;
            height: 29px;
            font-size: 13px;
        }

        .activity time {
            margin-top: 4px;
            font-size: 12px;
        }

        .stats-row {
            padding: 14px 0;
            font-size: 14px;
        }

        .stats-row i {
            width: 23px;
        }

        .stats-row strong {
            font-size: 15px;
        }

        .papers-panel {
            margin-top: 18px;
        }

        .panel-heading a {
            font-size: 13px;
        }

        .paper {
            gap: 14px;
            padding: 14px 0;
        }

        .paper-icon {
            width: 29px;
            height: 29px;
            flex-basis: 29px;
            font-size: 13px;
        }

        .paper-title {
            font-size: 14px;
        }

        .paper-meta {
            margin-top: 4px;
            font-size: 12px;
        }

        .open-sans {
            font-family: "Open Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            font-variation-settings: "wdth" 100;
        }
        a {
            text-decoration: none;
        }

        .nav_a_color {
            color: #4B5563;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background-color: #F9FAFB;
        }

        body {
            background: #f4f7ff;
            color: #18233d;
            font-size: 13px;
        }

        .dashboard-shell {
            display: flex;
            min-height: calc(100vh - 65px);
        }

        .sidebar {
            width: 190px;
            flex: 0 0 190px;
            background: #fff;
            border-right: 1px solid #e5eaf4;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            font-size: 14px;
            padding: 10px 9px;
            border-radius: 9px;
            font-size: 13px;
        }

        .side-link i {
            width: 14px;
            text-align: center;
            color: #64748b;
        }

        .side-link.active {
            color: #155eef;
            background: #e2edff;
            font-weight: 600;
        }

        .side-link.active i {
            color: #155eef;
        }

        .profile {
            margin-top: auto;
            border-top: 1px solid #edf0f6;
            padding: 15px 8px 2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .avatar {
            width: 25px;
            height: 25px;
            flex: 0 0 25px;
            border-radius: 50%;
            background: #165dff;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .profile small {
            display: block;
            color: #94a0b5;
            font-size: 8px;
        }

        .dashboard-main {
            max-width: 930px;
            width: 100%;
            margin: 0 auto;
            padding: 22px 26px 28px;
        }

        .welcome h1 {
            font-size: 20px;
            margin: 0 0 3px;
            font-weight: 700;
        }

        .welcome p {
            margin: 0 0 18px;
            color: #68748a;
            font-size: 11px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 17px;
        }

        .quick-card {
            min-height: 74px;
            border-radius: 10px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(32, 53, 92, .04);
            color: #1f2a44;
            font-size: 10px;
            font-weight: 600;
        }

        .quick-card i {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            font-size: 12px;
        }

        .quick-card:nth-child(1) i {
            color: #165dff;
            background: #e6efff;
        }

        .quick-card:nth-child(2) i {
            color: #8b32f5;
            background: #f1e9ff;
        }

        .quick-card:nth-child(3) i {
            color: #079669;
            background: #e4f8f0;
        }

        .quick-card:nth-child(4) i {
            color: #e87913;
            background: #fff0df;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(220px, .9fr);
            gap: 14px;
        }

        .panel {
            background: #fff;
            border: 1px solid #e1e7f1;
            border-radius: 11px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(32, 53, 92, .03);
        }

        .panel h2 {
            margin: 0 0 13px;
            font-size: 12px;
            color: #1b2742;
        }

        .activity {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 0;
            border-top: 1px solid #edf0f5;
            font-size: 10px;
            color: #25324d;
        }

        .activity:first-of-type {
            border-top: 0;
            padding-top: 0;
        }

        .activity i {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #165dff;
            background: #eaf1ff;
            font-size: 10px;
        }

        .activity:nth-of-type(3) i {
            color: #08a67b;
            background: #e5f9f2;
        }

        .activity:nth-of-type(4) i {
            color: #9b5de5;
            background: #f1e8ff;
        }

        .activity time {
            display: block;
            color: #8b96a9;
            font-size: 9px;
            margin-top: 2px;
        }

        .stats-list {
            margin: 0;
        }

        .stats-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px solid #edf0f5;
            color: #667289;
            font-size: 10px;
        }

        .stats-row:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .stats-row i {
            width: 18px;
            color: #165dff;
        }

        .stats-row strong {
            color: #1b2742;
            font-size: 11px;
        }

        .papers-panel {
            margin-top: 14px;
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-heading a {
            color: #1b2742;
            font-size: 10px;
            font-weight: 700;
        }

        .paper {
            display: flex;
            align-items: center;
            gap: 11px;
            border-top: 1px solid #edf0f5;
            padding: 10px 0;
        }

        .paper:first-of-type {
            border-top: 0;
        }

        .paper-icon {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eaf1ff;
            color: #477cef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex: 0 0 22px;
        }

        .paper-title {
            color: #20304e;
            font-size: 10px;
            line-height: 1.35;
        }

        .paper-meta {
            color: #8b96a9;
            display: block;
            font-size: 8px;
            margin-top: 2px;
        }

     
        .dashboard-main { max-width: 1080px; padding: 30px 34px 38px; }
        .welcome h1 { font-size: 28px; }
        .welcome p { margin-bottom: 24px; font-size: 15px; }
        .quick-actions { gap: 16px; margin-bottom: 24px; }
        .quick-card { min-height: 100px; gap: 10px; font-size: 14px; }
        .quick-card i { width: 32px; height: 32px; font-size: 15px; }
        .content-grid { gap: 18px; }
        .panel { padding: 21px; }
        .panel h2 { margin-bottom: 17px; font-size: 16px; }
        .activity { gap: 13px; padding: 13px 0; font-size: 14px; }
        .activity i { width: 29px; height: 29px; font-size: 13px; }
        .activity time { margin-top: 4px; font-size: 12px; }
        .stats-row { padding: 14px 0; font-size: 14px; }
        .stats-row i { width: 23px; }
        .stats-row strong { font-size: 15px; }
        .papers-panel { margin-top: 18px; }
        .panel-heading a { font-size: 13px; }
        .paper { gap: 14px; padding: 14px 0; }
        .paper-icon { width: 29px; height: 29px; flex-basis: 29px; font-size: 13px; }
        .paper-title { font-size: 14px; }
        .paper-meta { margin-top: 4px; font-size: 12px; }

        @media (max-width: 760px) {
            .dashboard-main { padding: 22px 16px 30px; }
            .welcome h1 { font-size: 23px; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }
        .dashboard-main { max-width: 1160px; padding: 38px 44px 46px; }
        .welcome h1 { font-size: 34px; }
        .welcome p { margin-bottom: 30px; font-size: 17px; }
        .quick-actions { gap: 20px; margin-bottom: 30px; }
        .quick-card { min-height: 120px; gap: 12px; font-size: 16px; }
        .quick-card i { width: 38px; height: 38px; font-size: 18px; }
        .content-grid { gap: 22px; }
        .panel { padding: 26px; }
        .panel h2 { margin-bottom: 21px; font-size: 19px; }
        .activity { gap: 16px; padding: 16px 0; font-size: 16px; }
        .activity i { width: 34px; height: 34px; font-size: 15px; }
        .activity time { margin-top: 5px; font-size: 13px; }
        .stats-row { padding: 17px 0; font-size: 16px; }
        .stats-row i { width: 27px; }
        .stats-row strong { font-size: 17px; }
        .papers-panel { margin-top: 22px; }
        .panel-heading a { font-size: 15px; }
        .paper { gap: 17px; padding: 17px 0; }
        .paper-icon { width: 34px; height: 34px; flex-basis: 34px; font-size: 15px; }
        .paper-title { font-size: 16px; }
        .paper-meta { margin-top: 5px; font-size: 13px; }

        @media (max-width: 760px) {
            .dashboard-main { padding: 26px 18px 34px; }
            .welcome h1 { font-size: 27px; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .quick-card { min-height: 100px; font-size: 14px; }
        }
    </style>
<link rel="stylesheet" href="portal.css"></head>

<body class="open-sans">
    <header>
        <nav>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background-color: #e69275; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold;">
                    UIU
                </div>
               <div style="font-size: 14px; font-weight: bold; cursor: pointer;" onclick="window.location.href='Home_page.php'">
                        UIU Research Portal
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 108px; height: 28px; border-radius: 12px; background-color: #e69275; color: white; display: flex; justify-content: center; align-items: center;">
                    <a href="User_dashboard.php" style="color: white; text-decoration: none;">Dashboard</a>
                </div>
                <a href="research-exploer1.php" class="nav_a_color">Research</a>
                <a href="Project.php" class="nav_a_color">Projects</a>
                <a href="Community_Forum.php" class="nav_a_color">Forum</a>
                <div style="width: 87px; height: 28px; border-radius: 16px; background-color: #e69275; color: white; display: flex; justify-content: center; align-items: center;">
                    <a href="upload.php" style="color: white; text-decoration: none;">Upload</a>
                </div>
                <i class="fa-duotone fa-regular fa-bell"></i>
                <div style="width: 32px; height: 32px; border-radius: 50%; background-color: #e69275; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold;">
                    <a href="profile.php" style="color: white; text-decoration: none;"><?= e(strtoupper(substr($user["full_name"], 0, 1))) ?></a>
                </div>
            </div>
        </nav>
    </header>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <a class="side-link active" href="User_dashboard.php"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
            <a class="side-link" href="research-exploer1.php"><i class="fa-regular fa-folder-open"></i><span>Research Explorer</span></a>
            <a class="side-link" href="Project.php"><i class="fa-regular fa-folder"></i><span>Projects</span></a>
            <a class="side-link" href="Community_Forum.php"><i class="fa-regular fa-comment"></i><span>Community Forum</span></a>
            <a class="side-link" href="upload.php"><i class="fa-regular fa-file-arrow-up"></i><span>Upload Paper</span></a>
            <a class="side-link" href="profile.php"><i class="fa-regular fa-user"></i><span>My Profile</span></a>
            
            <a class="side-link" href="notification.php"><i class="fa-regular fa-bell"></i><span>Notifications</span></a>
            <a class="side-link" href="setting.php"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
            <div class="profile">
                <div class="avatar"><?= e(strtoupper(substr($user["full_name"], 0, 1))) ?></div>
                <div>
                    <b style="font-size: 10px;"><?= e($user["full_name"]) ?></b>
                    <small><?= e($user["email"]) ?></small>
                </div>
            </div>
            <form class="logout-form" method="post" action="logout.php"><?php csrf_field(); ?><button>Sign Out</button></form>
        <?php if ($user["role"] === "admin"): ?><a class="side-link menu-item" href="admin_index.php">Admin Panel</a><?php endif; ?></aside>
        <main class="dashboard-main"><div class="live-content">
<?php show_message(); ?>

<h1>Welcome, <?= e($user['full_name']) ?></h1><p>Here is what is happening in your research community.</p>
<div class="box row"><a class="button" href="upload.php">Upload Paper</a><a class="button secondary" href="research-exploer1.php">Search Research</a><a class="button secondary" href="Community_Forum.php">New Discussion</a><a class="button secondary" href="Project.php">Browse Projects</a></div>
<div class="grid"><?php foreach (['Published papers'=>$paper_count, 'My projects'=>$project_count, 'Discussions'=>$discussion_count, 'Saved papers'=>$saved_count] as $label=>$count): ?><div class="box"><p class="muted"><?= e($label) ?></p><span class="count"><?= $count ?></span></div><?php endforeach; ?></div>
<section class="box"><h2>Recent Activity</h2><?php if (!$activity): ?><p>No activity yet. Start by sharing your research.</p><?php endif; ?><?php foreach ($activity as $item): ?><p><?= e($item['message']) ?><br><small class="muted"><?= e($item['created_at']) ?></small></p><?php endforeach; ?></section>
<section class="box"><h2>Latest Research</h2><?php if (!$recent): ?><p>No approved papers yet.</p><?php endif; ?><?php foreach ($recent as $paper): ?><p><a href="download.php?type=paper&amp;id=<?= $paper['id'] ?>"><?= e($paper['title']) ?></a><br><small><?= e($paper['authors']) ?></small></p><?php endforeach; ?></section>
</div></main>
    </div>
</body>

</html>