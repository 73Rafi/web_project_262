<?php

require __DIR__ . '/backend/common.php';
require_login();


// Get id and type from URL
$id = $_GET['id'];
$type = $_GET['type'];


// -------------------------
// Download CV
// -------------------------

if ($type == 'cv') {

    // Check permission
    if ($id != $user['id'] && $user['role'] != 'admin') {
        exit('You cannot download this CV.');
    }

    // Find CV from database
    $sql = "SELECT cv_path FROM users WHERE id = ?";

    $result = $conn->execute_query(
        $sql,
        [$id]
    );

    $record = $result->fetch_assoc();

    if (!$record) {
        exit('CV not found.');
    }

    $fileName = basename($record['cv_path']);
}


// -------------------------
// Download Paper
// -------------------------

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


    $fileName = basename($record['file_path']);
}


// -------------------------
// Wrong type
// -------------------------

else {

    exit('Wrong file type.');
}


// -------------------------
// Find the actual file
// -------------------------

$file = __DIR__ . '/backend/uploads/' . $fileName;


// Check file exists
if (!file_exists($file)) {

    exit('File not found.');
}


// -------------------------
// Download the file
// -------------------------

header('Content-Type: application/octet-stream');

header(
    'Content-Disposition: attachment; filename="' .
    $fileName .
    '"'
);

readfile($file);

exit;

?>