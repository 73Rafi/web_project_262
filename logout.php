<?php
require __DIR__ . '/backend/common.php';

session_destroy();

header("Location: sign-in.php");
exit;
?>
