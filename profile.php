<?php

require __DIR__ . '/backend/common.php';
require_login();


// -----------------------------------------
// Get user's papers
// -----------------------------------------

$sqlPapers = "SELECT * FROM papers
              WHERE user_id = ?
              ORDER BY id DESC";

$resultPapers = $conn->execute_query(
    $sqlPapers,
    [$user['id']]
);

$papers = $resultPapers->fetch_all(MYSQLI_ASSOC);


// -----------------------------------------
// Get user's projects
// -----------------------------------------

$sqlProjects = "SELECT * FROM projects
                WHERE user_id = ?
                ORDER BY id DESC";

$resultProjects = $conn->execute_query(
    $sqlProjects,
    [$user['id']]
);

$projects = $resultProjects->fetch_all(MYSQLI_ASSOC);


// -----------------------------------------
// Submit draft/rejected paper for review
// -----------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $paper_id = (int) input('paper_id');

    $sql = "UPDATE papers
            SET status = 'pending'
            WHERE id = ?
            AND user_id = ?
            AND (status = 'draft' OR status = 'rejected')";

    $conn->execute_query(
        $sql,
        [
            $paper_id,
            $user['id']
        ]
    );


    // Check if paper was updated
    if ($conn->affected_rows > 0) {

        flash('Paper submitted for review.');

    } else {

        flash('Paper could not be submitted.');
    }


    go('profile.php');
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>UIU Research Portal - My Profile</title>


    <link
        rel="stylesheet"
        href="mystyle.css"
    >

    <link
        rel="stylesheet"
        href="portal.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

</head>


<body class="portal-page">


<div class="app-container portal-layout">


    <!-- Sidebar -->

    <?php
    require __DIR__ . '/backend/sidebar.php';
    ?>


    <main class="main-content">


        <div class="live-content">


            <!-- Show success/error message -->

            <?php
            show_message();
            ?>


            <h1>My Profile</h1>


            <!-- Profile Information -->

            <section class="box">


                <h2>
                    <?php
                    echo e($user['full_name']);
                    ?>
                </h2>


                <p>

                    <?php
                    echo e(ucfirst($user['role']));
                    ?>

                    ·

                    <?php
                    echo e($user['department']);
                    ?>

                </p>


                <p>

                    <?php
                    echo e($user['email']);
                    ?>

                </p>


                <p>

                    <?php

                    if ($user['bio'] != '') {

                        echo nl2br(e($user['bio']));

                    } else {

                        echo 'Add your research interests in Settings.';
                    }

                    ?>

                </p>


                <a
                    class="button"
                    href="setting.php"
                >
                    Edit Profile
                </a>


                <!-- CV Download -->

                <?php

                if ($user['cv_path'] != '') {

                ?>

                    <a
                        class="button secondary"
                        href="download.php?type=cv&id=<?php echo $user['id']; ?>"
                    >
                        Download My CV
                    </a>

                <?php

                }

                ?>


            </section>



            <!-- My Papers -->

            <h2>

                My Papers

                (<?php echo count($papers); ?>)

            </h2>


            <?php

            if (empty($papers)) {

            ?>

                <p class="box empty">

                    You have not uploaded any papers yet.

                </p>

            <?php

            }

            ?>



            <?php

            foreach ($papers as $paper) {

            ?>


                <article class="box">


                    <h3>

                        <?php
                        echo e($paper['title']);
                        ?>

                    </h3>


                    <p>

                        <span class="tag">

                            <?php
                            echo e(ucfirst($paper['status']));
                            ?>

                        </span>

                    </p>


                    <!-- Download Paper -->

                    <a href="download.php?type=paper&id=<?php echo $paper['id']; ?>">

                        Download PDF

                    </a>



                    <!-- Submit Draft or Rejected Paper -->

                    <?php

                    if (
                        $paper['status'] == 'draft' ||
                        $paper['status'] == 'rejected'
                    ) {

                    ?>


                        <form
                            class="inline"
                            method="post"
                        >


                            <input
                                type="hidden"
                                name="paper_id"
                                value="<?php echo $paper['id']; ?>"
                            >


                            <button type="submit">

                                Submit for Review

                            </button>


                        </form>


                    <?php

                    }

                    ?>


                </article>


            <?php

            }

            ?>



            <!-- My Projects -->

            <h2>

                My Projects

                (<?php echo count($projects); ?>)

            </h2>


            <?php

            foreach ($projects as $project) {

            ?>


                <div class="box">


                    <a href="project_details.php?id=<?php echo $project['id']; ?>">

                        <?php
                        echo e($project['title']);
                        ?>

                    </a>


                    <span class="tag">

                        <?php
                        echo e($project['approval']);
                        ?>

                    </span>


                </div>


            <?php

            }

            ?>


        </div>


    </main>


</div>


</body>

</html>