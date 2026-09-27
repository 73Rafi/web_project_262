<?php
require __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    http_response_code(503);
    exit('Database connection failed. Check backend/config.php and import backend/database.sql.');
}
