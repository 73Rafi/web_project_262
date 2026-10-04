<?php

require __DIR__ . '/backend/common.php';

require_login();


// ==========================================
// Get ID and Type from URL
// ==========================================

$id = $_GET['id'];
$type = $_GET['type'];


// ==========================================
// CV Download
// ==========================================

if ($type == 'cv') {

    // User can download only own CV
    // Admin can download any CV
    if ($id != $user['id'] && $user['role'] != 'admin') {
        exit('You cannot download this CV.');
    }


    // Find CV from database
    $sql = "SELECT cv_path
            FROM users
            WHERE id = ?";

    $result = $conn->execute_query(
        $sql,
        [$id]
    );

    $record = $result->fetch_assoc();


    // Check if CV exists in database
    if (!$record) {
        exit('CV not found.');
    }


    // Get file path
    $filePath = $record['cv_path'];
}


// ==========================================
// Paper Download
// ==========================================

else if ($type == 'paper') {

    // Find paper from database
    $sql = "SELECT file_path, status, user_id
            FROM papers
            WHERE id = ?";

    $result = $conn->execute_query(
        $sql,
        [$id]
    );

    $record = $result->fetch_assoc();


    // Check if paper exists
    if (!$record) {
        exit('Paper not found.');
    }


    // Check permission
    if (
        $record['status'] != 'approved' &&
        $record['user_id'] != $user['id'] &&
        $user['role'] != 'admin'
    ) {
        exit('You cannot download this paper.');
    }


    // Get file path
    $filePath = $record['file_path'];
}


// ==========================================
// Invalid Type
// ==========================================

else {

    exit('Invalid file type.');
}


// ==========================================
// Get File Name
// ==========================================

$fileName = basename($filePath);


// ==========================================
// Full File Location
// ==========================================

$file = __DIR__ . '/backend/uploads/' . $fileName;


// ==========================================
// Check File Exists
// ==========================================

if (!file_exists($file)) {

    exit('File not found.');
}


// ==========================================
// Download File
// ==========================================

header('Content-Type: application/octet-stream');

header(
    'Content-Disposition: attachment; filename="' .
    $fileName .
    '"'
);

header(
    'Content-Length: ' .
    filesize($file)
);


// Send file to browser
readfile($file);

exit;

?>