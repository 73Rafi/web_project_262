<?php
require __DIR__ . '/backend/common.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the Sign Out button.');
}
check_csrf();
$_SESSION = [];
session_destroy();
go('signIn.php');
