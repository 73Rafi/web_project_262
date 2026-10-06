<?php
$admin_page = basename($_SERVER['SCRIPT_NAME']);
$admin_links = [
    ['admin-dashboard.php', 'Overview'],
    ['admin-paper-reviews.php', 'Paper reviews'],
    ['admin-project-reviews.php', 'Project reviews'],
    ['admin-join-requests.php', 'Join requests'],
    ['admin-users.php', 'Users'],
    ['admin-password-resets.php', 'Password resets'],
    ['admin-accounts.php', 'Admin accounts'],
];
?>
<nav class="admin-nav" aria-label="Administration">
    <?php foreach ($admin_links as [$href, $label]): ?>
        <a href="<?= e($href) ?>"<?= $admin_page === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
