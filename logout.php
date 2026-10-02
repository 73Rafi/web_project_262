<?php
require __DIR__ . '/backend/common.php';

session_destroy();

header("Location: signIn.php");
exit;
?>