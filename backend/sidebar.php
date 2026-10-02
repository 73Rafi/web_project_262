<?php
// Shared navigation for every signed-in portal page.
$sidebar_page = basename($_SERVER['SCRIPT_NAME']);
$sidebar_groups = [
    'project_details.php' => 'Project.php',
    'discussion.php' => 'Community_Forum.php',
    'general_discussion.php' => 'Community_Forum.php',
    'research_methods.php' => 'Community_Forum.php',
    'career_funding.php' => 'Community_Forum.php',
    'paper_reviews.php' => 'Community_Forum.php',
    'tools_software.php' => 'Community_Forum.php',
];
$sidebar_active = $sidebar_groups[$sidebar_page] ?? $sidebar_page;
$sidebar_links = [
    ['User_dashboard.php', 'fa-solid fa-table-columns', 'Dashboard'],
    ['research-exploer1.php', 'fa-regular fa-folder-open', 'Research Explorer'],
    ['Project.php', 'fa-regular fa-folder', 'Projects'],
    ['Community_Forum.php', 'fa-regular fa-comments', 'Community Forum'],
    ['upload.php', 'fa-solid fa-upload', 'Upload Paper'],
    ['profile.php', 'fa-regular fa-user', 'My Profile'],
    ['notification.php', 'fa-regular fa-bell', 'Notifications'],
    ['setting.php', 'fa-solid fa-gear', 'Settings'],
];
if ($user['role'] === 'admin') {
    $sidebar_links[] = ['admin_index.php', 'fa-solid fa-user-shield', 'Admin Panel'];
}
?>
<aside class="sidebar portal-sidebar">
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
