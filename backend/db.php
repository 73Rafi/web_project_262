<?php

require __DIR__ . '/config.php';

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_password,
    $db_name,
    $db_port
);

if ($conn->connect_error) {
    die("Connection failed");
}

$conn->set_charset("utf8mb4");

echo "Connected!";