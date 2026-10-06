<?php
// XAMPP normally uses root with an empty password. Change these for your computer.
$db_host = 'localhost';
$db_port = 3306;
$db_user = 'root';
$db_password = '';
$db_name = 'research_portal';

// Keep machine-specific credentials out of version control.
$local_config = __DIR__ . '/config.local.php';
if (is_file($local_config)) {
    require $local_config;
}

