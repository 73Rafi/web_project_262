<?php
require __DIR__ . '/backend/common.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';
if ($type === 'cv') {
    if ($id != $user['id'] && $user['role'] !== 'admin') {
        http_response_code(403);
        exit('You cannot download another user\'s CV.');
    }
    $record = $conn->execute_query('SELECT cv_path AS file_path FROM users WHERE id = ?', [$id])->fetch_assoc();
} elseif ($type === 'paper') {
    $record = $conn->execute_query('SELECT file_path, status, user_id FROM papers WHERE id = ?', [$id])->fetch_assoc();
    if ($record && $record['status'] !== 'approved' && $record['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        http_response_code(404);
        exit('Paper not found.');
    }
} else {
    http_response_code(400);
    exit('Unknown file type.');
}
// basename also supports old Node.js paths such as uploads/123.pdf.
$name = $record ? basename(str_replace('\\', '/', $record['file_path'])) : '';
$file = __DIR__ . '/backend/uploads/' . $name;
if (!$record || !$name || !is_file($file)) {
    http_response_code(404);
    exit('File not found.');
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $type . '-' . $id . '.' . preg_replace('/[^a-z0-9]/i', '', pathinfo($name, PATHINFO_EXTENSION)) . '"');
header('Content-Length: ' . filesize($file));
session_write_close();
readfile($file);
exit;
