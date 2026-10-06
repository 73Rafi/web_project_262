<?php
// Shared navigation for every signed-in portal page.
$sidebar_page = basename($_SERVER['SCRIPT_NAME']);
$sidebar_groups = [
    'project-details.php' => 'research-projects.php',
    'discussion.php' => 'community-forum.php',
    'general-discussion.php' => 'community-forum.php',
    'research-methods.php' => 'community-forum.php',
    'funding-opportunities.php' => 'community-forum.php',
    'paper-reviews.php' => 'community-forum.php',
    'research-tools.php' => 'community-forum.php',
];
$sidebar_active = $sidebar_groups[$sidebar_page] ?? $sidebar_page;
$sidebar_links = [
    ['dashboard.php', 'fa-solid fa-table-columns', 'Dashboard'],
    ['research-explorer.php', 'fa-regular fa-folder-open', 'Research Explorer'],
    ['research-projects.php', 'fa-regular fa-folder', 'Projects'],
    ['community-forum.php', 'fa-regular fa-comments', 'Community Forum'],
    ['submit-paper.php', 'fa-solid fa-upload', 'Submit Paper'],
    ['profile.php', 'fa-regular fa-user', 'My Profile'],
    ['notifications.php', 'fa-regular fa-bell', 'Notifications'],
    ['settings.php', 'fa-solid fa-gear', 'Settings'],
];
if ($user['role'] === 'admin') {
    $sidebar_links = [
        ['admin-dashboard.php', 'fa-solid fa-shield-halved', 'Admin Overview'],
        ['admin-paper-reviews.php', 'fa-solid fa-file-circle-check', 'Paper Reviews'],
        ['admin-project-reviews.php', 'fa-solid fa-diagram-project', 'Project Reviews'],
        ['admin-join-requests.php', 'fa-solid fa-user-check', 'Join Requests'],
        ['admin-users.php', 'fa-solid fa-users', 'User Management'],
        ['admin-password-resets.php', 'fa-solid fa-key', 'Password Resets'],
        ['admin-accounts.php', 'fa-solid fa-user-shield', 'Admin Accounts'],
    ];
}
?>
<aside class="sidebar portal-sidebar">
    <a class="portal-brand" href="<?= $user['role'] === 'admin' ? 'admin-dashboard.php' : 'dashboard.php' ?>" aria-label="UIU Research Hub dashboard">
        <span class="portal-brand-mark" aria-hidden="true">R</span>
        <span><strong>Research Hub</strong><small>United International University</small></span>
    </a>
    <p class="portal-sidebar-kicker"><?= $user['role'] === 'admin' ? 'ADMINISTRATION' : 'WORKSPACE' ?></p>
    <nav class="portal-sidebar-menu" aria-label="Main navigation">
        <?php foreach ($sidebar_links as [$sidebar_href, $sidebar_icon, $sidebar_label]): ?>
            <a class="portal-sidebar-link<?= $sidebar_active === $sidebar_href ? ' active' : '' ?>" href="<?= e($sidebar_href) ?>"<?= $sidebar_active === $sidebar_href ? ' aria-current="page"' : '' ?>>
                <i class="<?= e($sidebar_icon) ?>" aria-hidden="true"></i>
                <span><?= e($sidebar_label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="portal-sidebar-footer">
        <div class="portal-sidebar-account">
            <div class="portal-sidebar-avatar" aria-hidden="true"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
            <div class="portal-sidebar-user">
                <span class="portal-sidebar-name" title="<?= e($user['full_name']) ?>"><?= e($user['full_name']) ?></span>
                <span class="portal-sidebar-email" title="<?= e($user['email']) ?>"><?= e($user['email']) ?></span>
            </div>
        </div>
        <form class="portal-sidebar-logout" method="post" action="logout.php">

            <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Sign Out</span></button>
        </form>
    </div>
</aside>
