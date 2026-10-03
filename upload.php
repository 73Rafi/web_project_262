<?php

require __DIR__ . '/backend/common.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $file = null;

    try {

        // Get form data
        $title = input('title');
        $authors = input('authors');
        $abstract = input('abstract');
        $keywords = input('keywords');
        $department = input('department');
        $category = input('category');

        // Check required fields
        if (
            empty($title) ||
            empty($authors) ||
            empty($abstract) ||
            empty($keywords) ||
            empty($department) ||
            empty($category)
        ) {
            throw new Exception('Please fill in all fields.');
        }

        // Draft or submit
        if (input('action') == 'draft') {
            $status = 'draft';
        } else {
            $status = 'pending';
        }

        // Upload PDF
        $file = save_pdf('paper', 50);

        // Insert into database
        $sql = "INSERT INTO papers
                (user_id, title, authors, abstract, keywords,
                 department, category, file_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $conn->execute_query($sql, [
            $user['id'],
            $title,
            $authors,
            $abstract,
            $keywords,
            $department,
            $category,
            $file,
            $status
        ]);

        // Success message
        if ($status == 'draft') {
            flash('Draft saved.');
        } else {
            flash('Paper submitted.');
        }

        go('profile.php');

    } catch (Throwable $exception) {

        remove_upload($file);

        $error = page_error($exception);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UIU Research Portal - Upload Paper</title>

    <link rel="stylesheet" href="mystyle.css">
    <link rel="stylesheet" href="portal.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="portal-page">

<div class="app-container portal-layout">

    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content">

        <div class="live-content">

            <?php show_message(); ?>

            <h1>Upload Research Paper</h1>

            <p>Share your research with the UIU community.</p>


            <?php
            if ($error) {
                echo '<p class="notice error">' . e($error) . '</p>';
            }
            ?>


            <form class="box"
                  method="post"
                  enctype="multipart/form-data">


                <!-- PDF File -->

                <label class="field">

                    Paper PDF (up to 50 MB)

                    <input
                        type="file"
                        name="paper"
                        accept=".pdf"
                        required
                    >

                </label>


                <!-- Paper Title -->

                <label class="field">

                    Paper Title

                    <input
                        type="text"
                        name="title"
                        maxlength="255"
                        value="<?php echo e(input('title')); ?>"
                        required
                    >

                </label>


                <!-- Authors -->

                <label class="field">

                    Authors

                    <input
                        type="text"
                        name="authors"
                        maxlength="255"
                        value="<?php echo e(input('authors')); ?>"
                        required
                    >

                </label>


                <!-- Keywords -->

                <label class="field">

                    Keywords

                    <input
                        type="text"
                        name="keywords"
                        maxlength="255"
                        value="<?php echo e(input('keywords')); ?>"
                        required
                    >

                </label>


                <!-- Department -->

                <label class="field">

                    Department

                    <input
                        type="text"
                        name="department"
                        maxlength="255"
                        value="<?php echo e(input('department')); ?>"
                        required
                    >

                </label>


                <!-- Category -->

                <label class="field">

                    Category

                    <input
                        type="text"
                        name="category"
                        maxlength="100"
                        value="<?php echo e(input('category')); ?>"
                        required
                    >

                </label>


                <!-- Abstract -->

                <label class="field">

                    Abstract

                    <textarea
                        name="abstract"
                        maxlength="10000"
                        required
                    ><?php echo e(input('abstract')); ?></textarea>

                </label>


                <!-- Buttons -->

                <button
                    type="submit"
                    name="action"
                    value="publish"
                >
                    Submit for Review
                </button>


                <button
                    type="submit"
                    name="action"
                    value="draft"
                    class="secondary"
                >
                    Save Draft
                </button>


            </form>

        </div>

    </main>

</div>

</body>

</html>