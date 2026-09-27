<?php
// XAMPP normally uses root with an empty password. Change these for your computer.
$db_host = '127.0.0.1';
$db_port = 3306;
$db_user = 'root';
$db_password = '';
$db_name = 'research_portal';
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
