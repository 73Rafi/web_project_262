<?php

require __DIR__ . '/backend/common.php';

// User login করা আছে কিনা check
require_login();

// Logged in user ID
$user_id = (int) $user["id"];


// =====================================================
// 1. Published Papers Count
// =====================================================

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM papers
     WHERE user_id = $user_id
     AND status = 'approved'"
);

$row = $result->fetch_assoc();

$paper_count = $row["total"];


// =====================================================
// 2. My Projects Count
// =====================================================

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM projects
     WHERE user_id = $user_id"
);

$row = $result->fetch_assoc();

$project_count = $row["total"];


// =====================================================
// 3. Discussions Count
// =====================================================

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM discussions
     WHERE user_id = $user_id"
);

$row = $result->fetch_assoc();

$discussion_count = $row["total"];


// =====================================================
// 4. Saved Papers Count
// =====================================================

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM saved_papers
     WHERE user_id = $user_id"
);

$row = $result->fetch_assoc();

$saved_count = $row["total"];


// =====================================================
// 5. Recent Activity
// =====================================================

$activity = $conn->query(
    "SELECT *
     FROM notifications
     WHERE user_id = $user_id
     ORDER BY id DESC
     LIMIT 5"
);


// =====================================================
// 6. Latest Research Papers
// =====================================================

$recent = $conn->query(
    "SELECT id, title, authors
     FROM papers
     WHERE status = 'approved'
     ORDER BY id DESC
     LIMIT 5"
);

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Dashboard</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Your main CSS -->
    <link rel="stylesheet" href="portal.css">


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            background-color: #f4f7ff;
            font-family: Arial, sans-serif;
            color: #18233d;
        }


        /* Main Layout */

        .dashboard-shell {
            display: flex;
            min-height: 100vh;
        }


        .dashboard-main {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 35px;
        }


        /* Welcome Area */

        .welcome h1 {
            margin-bottom: 5px;
            font-size: 30px;
        }


        .welcome p {
            margin-top: 0;
            margin-bottom: 25px;
            color: #68748a;
        }


        /* Quick Action Buttons */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }


        .quick-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #1f2a44;
            text-align: center;
            font-weight: bold;
            border: 1px solid #e1e7f1;
        }


        .quick-card i {
            display: block;
            font-size: 22px;
            margin-bottom: 10px;
            color: #165dff;
        }


        .quick-card:hover {
            background-color: #f8faff;
        }


        /* Statistics */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }


        .stat-box {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e1e7f1;
        }


        .stat-box p {
            margin: 0 0 10px;
            color: #667289;
        }


        .stat-box .count {
            font-size: 25px;
            font-weight: bold;
            color: #165dff;
        }


        /* Content */

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        .box {
            background-color: white;
            padding: 22px;
            border: 1px solid #e1e7f1;
            border-radius: 10px;
        }


        .box h2 {
            margin-top: 0;
            font-size: 19px;
        }


        /* Activity */

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #eeeeee;
        }


        .activity-item:last-child {
            border-bottom: none;
        }


        .activity-item p {
            margin: 0;
        }


        .activity-item small {
            color: #8b96a9;
        }


        /* Papers */

        .paper-item {
            padding: 12px 0;
            border-bottom: 1px solid #eeeeee;
        }


        .paper-item:last-child {
            border-bottom: none;
        }


        .paper-item a {
            color: #165dff;
            text-decoration: none;
            font-weight: bold;
        }


        .paper-item a:hover {
            text-decoration: underline;
        }


        .paper-item small {
            color: #8b96a9;
        }


        /* Mobile */

        @media (max-width: 800px) {

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }


            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }


            .content-grid {
                grid-template-columns: 1fr;
            }


            .dashboard-main {
                padding: 20px;
            }

        }


        @media (max-width: 500px) {

            .quick-actions {
                grid-template-columns: 1fr;
            }


            .stats-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="dashboard-shell portal-layout">


    <!-- ========================================
         Sidebar
    ========================================= -->

    <?php
    require __DIR__ . '/backend/sidebar.php';
    ?>



    <!-- ========================================
         Main Dashboard
    ========================================= -->

    <main class="dashboard-main">


        <?php
        show_message();
        ?>


        <!-- Welcome -->

        <div class="welcome">

            <h1>
                Welcome,
                <?php echo e($user["full_name"]); ?>
            </h1>

            <p>
                Here is what is happening in your research community.
            </p>

        </div>



        <!-- ========================================
             Quick Actions
        ========================================= -->

        <div class="quick-actions">


            <a href="upload.php" class="quick-card">

                <i class="fa-solid fa-upload"></i>

                Upload Paper

            </a>


            <a href="research-exploer1.php" class="quick-card">

                <i class="fa-solid fa-magnifying-glass"></i>

                Search Research

            </a>


            <a href="Community_Forum.php" class="quick-card">

                <i class="fa-solid fa-comments"></i>

                New Discussion

            </a>


            <a href="Project.php" class="quick-card">

                <i class="fa-solid fa-diagram-project"></i>

                Browse Projects

            </a>


        </div>



        <!-- ========================================
             Statistics
        ========================================= -->

        <div class="stats-grid">


            <!-- Published Papers -->

            <div class="stat-box">

                <p>
                    Published Papers
                </p>

                <span class="count">

                    <?php
                    echo $paper_count;
                    ?>

                </span>

            </div>



            <!-- Projects -->

            <div class="stat-box">

                <p>
                    My Projects
                </p>

                <span class="count">

                    <?php
                    echo $project_count;
                    ?>

                </span>

            </div>



            <!-- Discussions -->

            <div class="stat-box">

                <p>
                    Discussions
                </p>

                <span class="count">

                    <?php
                    echo $discussion_count;
                    ?>

                </span>

            </div>



            <!-- Saved Papers -->

            <div class="stat-box">

                <p>
                    Saved Papers
                </p>

                <span class="count">

                    <?php
                    echo $saved_count;
                    ?>

                </span>

            </div>


        </div>



        <!-- ========================================
             Activity + Latest Research
        ========================================= -->

        <div class="content-grid">


            <!-- Recent Activity -->

            <section class="box">

                <h2>
                    Recent Activity
                </h2>


                <?php

                // কোনো activity না থাকলে
                if ($activity->num_rows == 0) {

                    echo "<p>No activity yet. Start by sharing your research.</p>";

                }

                ?>


                <?php

                // Activity থাকলে একটার পর একটা দেখাবে
                while ($item = $activity->fetch_assoc()) {

                ?>

                    <div class="activity-item">

                        <p>

                            <?php
                            echo e($item["message"]);
                            ?>

                        </p>

                        <small>

                            <?php
                            echo e($item["created_at"]);
                            ?>

                        </small>

                    </div>

                <?php

                }

                ?>


            </section>



            <!-- Latest Research -->

            <section class="box">

                <h2>
                    Latest Research
                </h2>


                <?php

                // কোনো approved paper না থাকলে
                if ($recent->num_rows == 0) {

                    echo "<p>No approved papers yet.</p>";

                }

                ?>


                <?php

                // Paper থাকলে একটার পর একটা দেখাবে
                while ($paper = $recent->fetch_assoc()) {

                ?>

                    <div class="paper-item">


                        <a
                            href="download.php?type=paper&id=<?php echo (int)$paper["id"]; ?>"
                        >

                            <?php
                            echo e($paper["title"]);
                            ?>

                        </a>


                        <br>


                        <small>

                            <?php
                            echo e($paper["authors"]);
                            ?>

                        </small>


                    </div>


                <?php

                }

                ?>


            </section>


        </div>


    </main>


</div>


</body>

</html>