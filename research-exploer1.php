<?php
require __DIR__ . '/backend/common.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $paper_id = (int) input('paper_id');
    $paper = $conn->execute_query("SELECT id FROM papers WHERE id = ? AND status = 'approved'", [$paper_id])->fetch_assoc();
    if (!$paper) { http_response_code(404); exit('Paper not found.'); }
    if (input('action') === 'unsave') {
        $conn->execute_query('DELETE FROM saved_papers WHERE user_id = ? AND paper_id = ?', [$user['id'], $paper_id]);
    } else {
        $conn->execute_query('INSERT IGNORE INTO saved_papers (user_id, paper_id) VALUES (?, ?)', [$user['id'], $paper_id]);
    }
    flash('Saved papers updated.');
    go('research-exploer1.php?source=portal');
}
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$category = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
$department = is_string($_GET['department'] ?? null) ? $_GET['department'] : '';
$year = (int) ($_GET['year'] ?? 0);
$saved_only = isset($_GET['saved']);
$term = '%' . $search . '%';
$papers = $conn->execute_query("SELECT p.*, s.paper_id AS saved FROM papers p LEFT JOIN saved_papers s ON s.paper_id = p.id AND s.user_id = ? WHERE p.status = 'approved' AND (p.title LIKE ? OR p.authors LIKE ? OR p.keywords LIKE ?) AND (? = '' OR p.category = ?) AND (? = '' OR p.department = ?) AND (? = 0 OR YEAR(p.created_at) = ?) AND (? = 0 OR s.paper_id IS NOT NULL) ORDER BY p.id DESC LIMIT 100", [$user['id'], $term, $term, $term, $category, $category, $department, $department, $year, $year, (int) $saved_only])->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT DISTINCT category FROM papers WHERE status = 'approved' ORDER BY category");
$departments = $conn->query("SELECT DISTINCT department FROM papers WHERE status = 'approved' ORDER BY department");

// Old search/bookmark links still open the local portal collection.
$local_search = isset($_GET['q']) || isset($_GET['category']) || isset($_GET['department']) || isset($_GET['year']) || $saved_only;
$source = ($_GET['source'] ?? '') === 'portal' || $local_search ? 'portal' : 'crossref';
$online_search = is_string($_GET['online_q'] ?? null) ? substr(trim($_GET['online_q']), 0, 150) : '';
$topic = is_string($_GET['topic'] ?? null) ? $_GET['topic'] : 'cs.*';
$page = max(1, min(100, (int) ($_GET['page'] ?? 1)));
if ($source === 'crossref') {
    require __DIR__ . '/backend/crossref.php';
    $topics = crossref_topics();
    if (!isset($topics[$topic])) { $topic = 'cs.*'; }
    $online = fetch_crossref_papers($topic, $online_search, $page);
    $page_link = 'research-exploer1.php?' . http_build_query(['source' => 'crossref', 'online_q' => $online_search, 'topic' => $topic]);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<title>Research Explorer</title>

	<style>
		.explorer-main {
			max-width: 1080px;
			padding: 30px 34px 38px;
		}

		.page-heading h1 {
			font-size: 28px;
		}

		.page-heading p {
			margin-bottom: 22px;
			font-size: 15px;
		}

		.search-area {
			gap: 10px;
			margin-bottom: 14px;
		}

		.search-box {
			height: 40px;
			padding: 0 14px;
			font-size: 14px;
		}

		.search-box input {
			font-size: 12px;
		}

		.filter-button,
		.sort-button {
			height: 40px;
			padding: 0 14px;
			font-size: 12px;
		}

		.category-button {
			padding: 7px 13px;
			font-size: 11px;
		}

		.results-bar {
			margin-bottom: 10px;
			font-size: 12px;
		}

		.sort-button {
			height: 36px;
		}

		.paper-list {
			gap: 12px;
		}

		.paper-card {
			padding: 17px;
		}

		.paper-heading {
			gap: 12px;
		}

		.paper-icon {
			width: 30px;
			height: 30px;
			font-size: 13px;
		}

		.paper-title {
			font-size: 13px;
		}

		.paper-authors,
		.paper-year {
			font-size: 11px;
		}

		.paper-description {
			margin: 12px 0 9px 42px;
			font-size: 11px;
		}

		.paper-tags,
		.paper-actions {
			margin-left: 42px;
		}

		.paper-tag {
			padding: 4px 8px;
			font-size: 10px;
		}

		.paper-actions {
			gap: 13px;
			font-size: 10px;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			background-color: #f1f5ff;
			color: #18233d;
			font-family: "Open Sans", sans-serif;
			font-size: 13px;
		}

		a {
			color: inherit;
			text-decoration: none;
		}

		nav {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 16px 32px;
			background-color: #f9fafb;
		}

		.brand,
		.nav-links,
		.nav-button,
		.profile,
		.side-link,
		.search-box,
		.filter-row,
		.paper-heading,
		.paper-actions {
			display: flex;
			align-items: center;
		}

		.brand {
			gap: 8px;
		}

		.brand-logo,
		.profile-avatar {
			border-radius: 50%;
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
		}

		.brand-logo {
			width: 32px;
			height: 32px;
			background-color: #e69275;
			font-size: 10px;
		}

		.brand-name {
			font-size: 14px;
			font-weight: 700;
		}

		.nav-links {
			gap: 16px;
			color: #4b5563;
			font-size: 14px;
		}

		.nav-button {
			justify-content: center;
			height: 28px;
			padding: 0 13px;
			border-radius: 15px;
			background-color: #e69275;
			color: #ffffff;
		}

		.nav-button i {
			margin-right: 5px;
		}

		.top-avatar {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			background-color: #e69275;
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 10px;
			font-weight: 700;
		}

		.page-layout {
			display: flex;
			min-height: 100vh;
		}

		.sidebar {
			width: 190px;
			flex-shrink: 0;
			padding: 12px 10px;
			border-right: 1px solid #e3e8f2;
			background-color: #ffffff;
			display: flex;
			flex-direction: column;
		}

		.side-link {
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
			gap: 8px;
			margin-top: auto;
			padding: 15px 8px 2px;
			border-top: 1px solid #edf0f6;
		}

		.profile-avatar {
			width: 25px;
			height: 25px;
			flex-shrink: 0;
			background-color: #165dff;
			font-size: 10px;
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

		.explorer-main {
			width: 100%;
			max-width: 980px;
			margin: 0 auto;
			padding: 22px 26px 28px;
		}

		.page-heading h1 {
			margin: 0 0 4px;
			font-size: 20px;
		}

		.page-heading p {
			margin: 0 0 16px;
			color: #68748a;
			font-size: 11px;
		}

		.search-area {
			gap: 8px;
			margin-bottom: 10px;
		}

		.search-box {
			flex: 1;
			gap: 8px;
			height: 30px;
			padding: 0 11px;
			border-radius: 8px;
			background-color: #ffffff;
			color: #9aa5b8;
		}

		.search-box input {
			width: 100%;
			border: 0;
			outline: 0;
			color: #38445c;
			font-size: 9px;
		}

		.filter-button,
		.sort-button {
			height: 30px;
			padding: 0 11px;
			border: 1px solid #d9e1ef;
			border-radius: 8px;
			background-color: #ffffff;
			color: #536078;
			font-size: 9px;
		}

		.filter-button:hover,
		.filter-button.is-active {
			border-color: #2057d4;
			background-color: #2057d4;
			color: #ffffff;
			cursor: pointer;
		}

		.filter-button i {
			margin-right: 5px;
		}

		.filter-panel {
			display: none;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 18px;
			margin-bottom: 16px;
			padding: 22px;
			border: 1px solid #dce3f0;
			border-radius: 12px;
			background-color: #ffffff;
		}

		.filter-panel.open {
			display: grid;
		}

		.filter-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.filter-group label,
		.filter-group legend {
			color: #536078;
			font-size: 12px;
			font-weight: 700;
		}

		.filter-group select {
			width: 100%;
			height: 40px;
			padding: 0 11px;
			border: 1px solid #dce4f3;
			border-radius: 8px;
			background-color: #ffffff;
			color: #536078;
			font-size: 12px;
		}

		.year-options {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}

		.year-options label {
			padding: 10px 12px;
			border: 1px solid #dce4f3;
			border-radius: 8px;
			font-size: 12px;
			font-weight: 400;
			cursor: pointer;
		}

		.year-options input {
			accent-color: #2057d4;
			margin: 0 3px 0 0;
		}

		.filter-actions {
			grid-column: 1 / -1;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
			padding-top: 6px;
		}

		.filter-actions button {
			padding: 10px 16px;
			border: 1px solid #dce4f3;
			border-radius: 8px;
			background: #ffffff;
			color: #536078;
			font-size: 12px;
			cursor: pointer;
		}

		.filter-actions .apply-filters {
			border-color: #2057d4;
			background: #2057d4;
			color: #ffffff;
		}

		.filter-row {
			gap: 7px;
			flex-wrap: wrap;
			margin-bottom: 8px;
		}

		.category-button {
			padding: 5px 11px;
			border: 1px solid #dce4f3;
			border-radius: 12px;
			background-color: #f7f9ff;
			color: #536078;
			font-size: 8px;
		}

		.category-button.active {
			border-color: #2057d4;
			background-color: #2057d4;
			color: #ffffff;
		}

		.results-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 7px;
			color: #68748a;
			font-size: 9px;
		}

		.sort-button {
			height: 27px;
		}

		.paper-list {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.paper-card {
			padding: 12px;
			border: 1px solid #dce3f0;
			border-radius: 10px;
			background-color: #ffffff;
			box-shadow: 0 1px 3px rgba(32, 53, 92, 0.03);
		}

		.paper-heading {
			align-items: flex-start;
			gap: 9px;
		}

		.paper-icon {
			width: 22px;
			height: 22px;
			flex-shrink: 0;
			border-radius: 50%;
			background-color: #eaf1ff;
			color: #477cef;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 10px;
		}

		.paper-title {
			flex: 1;
			color: #1d2943;
			font-size: 10px;
			font-weight: 700;
		}

		.paper-authors {
			display: block;
			margin-top: 3px;
			color: #68748a;
			font-size: 8px;
			font-weight: 400;
		}

		.paper-year {
			color: #536078;
			font-size: 8px;
		}

		.paper-description {
			margin: 9px 0 6px 31px;
			color: #667289;
			font-size: 8px;
			line-height: 1.45;
		}

		.paper-tags {
			display: flex;
			gap: 5px;
			flex-wrap: wrap;
			margin: 0 0 6px 31px;
		}

		.paper-tag {
			padding: 3px 7px;
			border-radius: 8px;
			background-color: #eaf1ff;
			color: #2860d8;
			font-size: 8px;
		}

		.paper-actions {
			gap: 10px;
			margin-left: 31px;
			color: #536078;
			font-size: 8px;
		}

		.paper-actions i {
			margin-right: 3px;
		}

		@media (max-width: 760px) {
			nav {
				padding: 12px 15px;
			}

			.nav-links {
				gap: 8px;
			}

			.nav-links > a:not(:first-child) {
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

			.explorer-main {
				padding: 18px 14px;
			}

			.search-area {
				flex-wrap: wrap;
			}

			.search-box {
				flex-basis: 100%;
			}

			.filter-panel.open {
				grid-template-columns: 1fr 1fr;
			}

			.paper-description,
			.paper-tags,
			.paper-actions {
				margin-left: 0;
			}
		}
		.explorer-main { max-width: 1080px; padding: 30px 34px 38px; }
		.page-heading h1 { font-size: 28px; }
		.page-heading p { margin-bottom: 22px; font-size: 15px; }
		.search-area { gap: 10px; margin-bottom: 14px; }
		.search-box { height: 40px; padding: 0 14px; font-size: 14px; }
		.search-box input { font-size: 12px; }
		.filter-button, .sort-button { height: 40px; padding: 0 14px; font-size: 12px; }
		.category-button { padding: 7px 13px; font-size: 11px; }
		.results-bar { margin-bottom: 10px; font-size: 12px; }
		.sort-button { height: 36px; }
		.paper-list { gap: 12px; }
		.paper-card { padding: 17px; }
		.paper-heading { gap: 12px; }
		.paper-icon { width: 30px; height: 30px; font-size: 13px; }
		.paper-title { font-size: 13px; }
		.paper-authors, .paper-year { font-size: 11px; }
		.paper-description { margin: 12px 0 9px 42px; font-size: 11px; }
		.paper-tags, .paper-actions { margin-left: 42px; }
		.paper-tag { padding: 4px 8px; font-size: 10px; }
		.paper-actions { gap: 13px; font-size: 10px; }

		@media (max-width: 760px) {
			.explorer-main { padding: 22px 16px 30px; }
			.page-heading h1 { font-size: 23px; }
			.filter-panel.open { grid-template-columns: 1fr 1fr; }
			.paper-description, .paper-tags, .paper-actions { margin-left: 0; }
		}
		.explorer-main { max-width: 1160px; padding: 38px 44px 46px; }
		.page-heading h1 { font-size: 34px; }
		.page-heading p { margin-bottom: 28px; font-size: 17px; }
		.search-area { gap: 12px; margin-bottom: 18px; }
		.search-box { height: 48px; padding: 0 17px; font-size: 16px; }
		.search-box input { font-size: 14px; }
		.filter-button, .sort-button { height: 48px; padding: 0 17px; font-size: 14px; }
		.category-button { padding: 9px 16px; font-size: 13px; }
		.results-bar { margin-bottom: 13px; font-size: 14px; }
		.sort-button { height: 42px; }
		.paper-list { gap: 16px; }
		.paper-card { padding: 22px; }
		.paper-heading { gap: 15px; }
		.paper-icon { width: 36px; height: 36px; font-size: 15px; }
		.paper-title { font-size: 15px; }
		.paper-authors, .paper-year { font-size: 13px; }
		.paper-description { margin: 15px 0 12px 51px; font-size: 13px; }
		.paper-tags, .paper-actions { margin-left: 51px; }
		.paper-tag { padding: 5px 10px; font-size: 12px; }
		.paper-actions { gap: 16px; font-size: 12px; }

		@media (max-width: 760px) {
			.explorer-main { padding: 26px 18px 34px; }
			.page-heading h1 { font-size: 27px; }
			.filter-panel.open { grid-template-columns: 1fr 1fr; }
			.paper-description, .paper-tags, .paper-actions { margin-left: 0; }
		}
	</style>
<link rel="stylesheet" href="portal.css"></head>

<body>
	<div class="page-layout">
		<aside class="sidebar">
			<a class="side-link" href="User_dashboard.php">
				<i class="fa-solid fa-table-columns"></i>
				<span>Dashboard</span>
			</a>
			<a class="side-link active" href="research-exploer1.php">
				<i class="fa-regular fa-folder-open"></i>
				<span>Research Explorer</span>
			</a>
			<a class="side-link" href="Project.php">
				<i class="fa-regular fa-folder"></i>
				<span>Projects</span>
			</a>
			<a class="side-link" href="Community_Forum.php">
				<i class="fa-regular fa-comment"></i>
				<span>Community Forum</span>
			</a>
			<a class="side-link" href="upload.php">
				<i class="fa-solid fa-upload"></i>
				<span>Upload Paper</span>
			</a>
			<a class="side-link" href="profile.php">
				<i class="fa-regular fa-user"></i>
				<span>My Profile</span>
			</a>
			<a class="side-link" href="notification.php">
				<i class="fa-regular fa-bell"></i>
				<span>Notifications</span>
			</a>
			<a class="side-link" href="setting.php">
				<i class="fa-solid fa-gear"></i>
				<span>Settings</span>
			</a>

			<div class="profile">
				<div class="profile-avatar"><?= e(strtoupper(substr($user["full_name"], 0, 1))) ?></div>
				<div>
					<div class="profile-name"><?= e($user["full_name"]) ?></div>
					<span class="profile-email"><?= e($user["email"]) ?></span>
				</div>
			</div>

						<form class="logout-form" method="post" action="logout.php"><?php csrf_field(); ?><button>Sign Out</button></form>
		<?php if ($user["role"] === "admin"): ?><a class="side-link menu-item" href="admin_index.php">Admin Panel</a><?php endif; ?></aside>

		<main class="explorer-main"><div class="live-content">
<?php show_message(); ?>

<h1>Research Explorer</h1><p>Discover Computer Science research and papers shared by the UIU community.</p>
<div class="box row" aria-label="Paper collections">
    <a class="button <?= $source === 'crossref' ? '' : 'secondary' ?>" href="research-exploer1.php?source=crossref" <?= $source === 'crossref' ? 'aria-current="page"' : '' ?>>CSE Papers (Crossref)</a>
    <a class="button <?= $source === 'portal' ? '' : 'secondary' ?>" href="research-exploer1.php?source=portal" <?= $source === 'portal' ? 'aria-current="page"' : '' ?>>UIU Papers</a>
</div>
<?php if ($source === 'crossref'): ?>
<form class="box" method="get">
    <input type="hidden" name="source" value="crossref">
    <label class="field">Search CSE papers
        <input name="online_q" maxlength="150" placeholder="e.g. machine learning, networks, cybersecurity" value="<?= e($online_search) ?>">
    </label>
    <label class="field">Topic
        <select name="topic">
            <?php foreach ($topics as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= $topic === $code ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button>Search Papers</button> <a href="research-exploer1.php?source=crossref">Reset</a>
</form>
<p class="muted">Source: <a href="https://www.crossref.org" target="_blank" rel="noopener noreferrer">Crossref</a> · Newest first · Includes preprints that may not be peer reviewed.</p>
<?php if ($online['message']): ?><p class="notice" role="status"><?= e($online['message']) ?></p><?php endif; ?>
<?php if (!$online['failed']): ?>
<p><?= number_format($online['total']) ?> matches · Page <?= $page ?> · <?= count($online['papers']) ?> papers shown</p>
<?php if ($online['fetched_at']): ?><p class="muted">Last fetched: <?= e(gmdate('Y-m-d H:i', $online['fetched_at'])) ?> UTC</p><?php endif; ?>
<?php if (!$online['papers']): ?><div class="box empty">No papers found. Try another keyword or topic.</div><?php endif; ?>
<?php foreach ($online['papers'] as $paper): ?>
<article class="box">
    <span class="tag"><?= e($paper['categories']) ?></span>
    <h2><a href="<?= e($paper['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($paper['title']) ?></a></h2>
    <p class="muted"><?= e($paper['authors']) ?> · <?= e($paper['date']) ?></p>
    <?php if ($paper['venue']): ?><p class="muted"><?= e($paper['venue']) ?></p><?php endif; ?>
    <?php if ($paper['abstract']): ?><details><summary>Read abstract</summary><p><?= e($paper['abstract']) ?></p></details>
    <?php else: ?><p class="muted">Abstract not provided by the publisher.</p><?php endif; ?>
    <p class="row">
        <?php if ($paper['pdf']): ?><a class="button" href="<?= e($paper['pdf']) ?>" target="_blank" rel="noopener noreferrer">Publisher PDF</a><?php endif; ?>
        <a class="button secondary" href="<?= e($paper['url']) ?>" target="_blank" rel="noopener noreferrer">View Paper / DOI</a>
    </p>
</article>
<?php endforeach; ?>
<div class="row">
    <?php if ($page > 1): ?><a class="button secondary" href="<?= e($page_link . '&page=' . ($page - 1)) ?>">Previous</a><?php endif; ?>
    <?php if ($page < 100 && $page * 10 < $online['total']): ?><a class="button" href="<?= e($page_link . '&page=' . ($page + 1)) ?>">Next</a><?php endif; ?>
</div>
<?php endif; ?>
<?php else: ?>
<p>Search approved UIU papers. Showing up to 100 newest matches.</p>
<form class="box" method="get">
<input type="hidden" name="source" value="portal">
<label class="field">Search title, author or keyword<input name="q" value="<?= e($search) ?>"></label>
<div class="grid">
<label class="field">Category<select name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option <?= $category === $item['category'] ? 'selected' : '' ?>><?= e($item['category']) ?></option><?php endforeach; ?></select></label>
<label class="field">Department<select name="department"><option value="">All departments</option><?php foreach ($departments as $item): ?><option <?= $department === $item['department'] ? 'selected' : '' ?>><?= e($item['department']) ?></option><?php endforeach; ?></select></label>
<label class="field">Year<input type="number" name="year" min="1900" max="2100" value="<?= $year ?: '' ?>"></label>
</div><label><input type="checkbox" name="saved" value="1" <?= $saved_only ? 'checked' : '' ?>> Saved papers only</label>
<p><button>Search</button> <a href="research-exploer1.php?source=portal">Reset</a></p></form>
<p><?= count($papers) ?> papers found</p>
<?php if (!$papers): ?><div class="box empty">No published papers match your search.</div><?php endif; ?>
<?php foreach ($papers as $paper): ?>
<article class="box"><h2><?= e($paper['title']) ?></h2><p class="muted"><?= e($paper['authors']) ?> · <?= e($paper['department']) ?> · <?= e(substr($paper['created_at'], 0, 4)) ?></p>
<p><?= nl2br(e($paper['abstract'])) ?></p><p><span class="tag"><?= e($paper['category']) ?></span> <?= e($paper['keywords']) ?></p>
<a class="button" href="download.php?type=paper&amp;id=<?= $paper['id'] ?>">Download PDF</a>
<form class="inline" method="post"><?php csrf_field(); ?><input type="hidden" name="paper_id" value="<?= $paper['id'] ?>"><button class="secondary" name="action" value="<?= $paper['saved'] ? 'unsave' : 'save' ?>"><?= $paper['saved'] ? 'Unsave' : 'Save' ?></button></form>
</article><?php endforeach; ?>
<?php endif; ?>
</div></main>
	</div>

	
</body>

</html>
