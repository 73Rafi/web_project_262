<?php
// Every PHP page loads this file before printing HTML.
ini_set('session.use_strict_mode', '1');
session_name('uiu_portal');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require __DIR__ . '/db.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function go($page) {
    header('Location: ' . $page, true, 303);
    exit;
}
function flash($message) {
    $_SESSION['message'] = $message;
}
function show_message() {
    if (isset($_SESSION['message'])) {
        echo '<p class="notice" role="status">' . e($_SESSION['message']) . '</p>';
        unset($_SESSION['message']);
    }
}
// A hidden token prevents another website from submitting our forms.
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field() {
    echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function check_csrf() {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        exit('This form expired. Go back, refresh the page, and try again.');
    }
}
function input($name) {
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}
function required_text($name, $label, $max = 255) {
    $value = input($name);
    if ($value === '' || strlen($value) > $max) {
        throw new InvalidArgumentException($label . ' is required (maximum ' . $max . ' bytes).');
    }
    return $value;
}
function valid_password($password) {
    if (strlen($password) < 8 || strlen($password) > 72) {
        throw new InvalidArgumentException('Use a password between 8 and 72 bytes.');
    }
}
function page_error($error) {
    if ($error instanceof InvalidArgumentException) {
        return $error->getMessage();
    }
    error_log($error->getMessage());
    return 'Something went wrong. Please try again.';
}
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = $conn->execute_query('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']])->fetch_assoc();
    if (!$user || !$user['is_active'] || (int) $user['session_version'] !== (int) ($_SESSION['version'] ?? -1)) {
        unset($_SESSION['user_id'], $_SESSION['version']);
        $user = null;
    }
}
function require_login() {
    global $user;
    if (!$user) {
        flash('Please sign in to continue.');
        go('signIn.php');
    }
}
function require_admin() {
    global $user;
    require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Only an administrator can open this page.');
    }
}
// PDF files are downloaded through download.php, never a public upload URL.
function save_pdf($field, $max_mb) {
    $file = $_FILES[$field] ?? null;
    if (!$file || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Choose a PDF file within the upload limit.');
    }
    if ($file['size'] <= 0 || $file['size'] > $max_mb * 1024 * 1024) {
        throw new InvalidArgumentException('The PDF must be smaller than ' . $max_mb . ' MB.');
    }
    $type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $signature = file_get_contents($file['tmp_name'], false, null, 0, 5);
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf' || $type !== 'application/pdf' || $signature !== '%PDF-') {
        throw new InvalidArgumentException('Please upload a real PDF file.');
    }
    $folder = __DIR__ . '/uploads';
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $name)) {
        throw new RuntimeException('Could not save the file.');
    }
    return $name;
}
function remove_upload($name) {
    if ($name && is_file(__DIR__ . '/uploads/' . basename($name))) {
        unlink(__DIR__ . '/uploads/' . basename($name));
    }
}
function notify_user($id, $message) {
    global $conn;
    $conn->execute_query('INSERT INTO notifications (user_id, message) VALUES (?, ?)', [$id, $message]);
}
