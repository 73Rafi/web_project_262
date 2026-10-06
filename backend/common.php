<?php

// ==========================================
// Start Session
// ==========================================
session_start();


// ==========================================
// Database Connection
// ==========================================
require __DIR__ . '/db.php';


// ==========================================
// 1. Safe Output
// ==========================================
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}


// ==========================================
// 2. Redirect
// ==========================================
function go($page)
{
    header("Location: $page");
    exit;
}


// ==========================================
// 3. Flash Message
// ==========================================
function flash($message)
{
    $_SESSION['message'] = $message;
}


// Show flash message
function show_message()
{
    if (!empty($_SESSION['message'])) {

        echo '<p class="notice">'
            . e($_SESSION['message'])
            . '</p>';

        unset($_SESSION['message']);
    }
}


// ==========================================
// 4. Get Form Input
// ==========================================
function input($name)
{
    if (isset($_POST[$name])) {
        return trim($_POST[$name]);
    }

    return '';
}


// ==========================================
// 5. Required Text Validation
// ==========================================
function required_text($name, $label, $max = 255)
{
    $value = input($name);

    if ($value == '') {
        throw new Exception("$label is required.");
    }

    if (strlen($value) > $max) {
        throw new Exception("$label is too long.");
    }

    return $value;
}


// ==========================================
// 6. Password Validation
// ==========================================
function valid_password($password)
{
    if (strlen($password) < 8) {

        throw new Exception(
            'Password must be at least 8 characters.'
        );
    }
}


// ==========================================
// 7. Error Message
// ==========================================
function page_error($error)
{
    return $error->getMessage();
}


// ==========================================
// 8. Get Current Logged-in User
// ==========================================
$user = null;

if (!empty($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

    $result = $conn->execute_query(
        "SELECT * FROM users WHERE id = ?",
        [$user_id]
    );

    $user = $result->fetch_assoc();


    // If user account is disabled
    if ($user && !$user['is_active']) {

        unset($_SESSION['user_id']);

        $user = null;
    }
}


// ==========================================
// 9. Login Check
// ==========================================
function require_login()
{
    global $user;

    if (!$user) {

        flash('Please login first.');

        go('sign-in.php');
    }
}


// ==========================================
// 10. Admin Check
// ==========================================
function require_admin()
{
    global $user;

    // First check login
    require_login();

    // Then check admin role
    if ($user['role'] != 'admin') {

        die('Admin access only.');
    }
}


// ==========================================
// 11. Save PDF
// ==========================================
function save_pdf($field, $max_mb)
{

    // Check file selected or not
    if (empty($_FILES[$field]['name'])) {

        throw new Exception(
            'Please select a PDF file.'
        );
    }


    $file = $_FILES[$field];


    // Check upload error
    if ($file['error'] != 0) {

        throw new Exception(
            'File upload failed.'
        );
    }


    // Check file size
    $max_size = $max_mb * 1024 * 1024;

    if ($file['size'] > $max_size) {

        throw new Exception(
            "Maximum file size is $max_mb MB."
        );
    }


    // Get file extension
    $extension = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );


    // Only PDF allowed
    if ($extension != 'pdf') {

        throw new Exception(
            'Only PDF files are allowed.'
        );
    }


    // Upload folder
    $folder = __DIR__ . '/uploads';


    // Create folder if it does not exist
    if (!is_dir($folder)) {

        mkdir($folder);
    }


    // Create unique file name
    $name = uniqid() . '.pdf';


    // Full destination
    $destination = $folder . '/' . $name;


    // Move uploaded file
    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {

        throw new Exception(
            'Could not save the file.'
        );
    }


    // Return filename
    return $name;
}


// ==========================================
// 12. Delete Uploaded File
// ==========================================
function remove_upload($name)
{
    $file =
        __DIR__
        . '/uploads/'
        . basename($name);


    if ($name && file_exists($file)) {

        unlink($file);
    }
}


// ==========================================
// 13. Create Notification
// ==========================================
function notify_user($user_id, $message)
{
    global $conn;

    $conn->execute_query(
        "INSERT INTO notifications
        (user_id, message)
        VALUES (?, ?)",
        [$user_id, $message]
    );
}
