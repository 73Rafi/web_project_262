<?php
require __DIR__ . '/backend/common.php';
$paper_count = $conn->query("SELECT COUNT(*) AS total FROM papers WHERE status = 'approved'")->fetch_assoc()['total'];
$member_count = $conn->query('SELECT COUNT(*) AS total FROM users WHERE is_active = 1')->fetch_assoc()['total'];
$project_count = $conn->query("SELECT COUNT(*) AS total FROM projects WHERE approval = 'approved' AND status <> 'Completed'")->fetch_assoc()['total'];
$papers = $conn->query("SELECT title, authors FROM papers WHERE status = 'approved' ORDER BY id DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$projects = $conn->query("SELECT id, title, department FROM projects WHERE approval = 'approved' AND status <> 'Completed' ORDER BY id DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Research Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="portal.css"></head>

<body>

    <section class="hero-section">
        <div class="hero-container">
        
            <div class="hero-badge">
                <span>🏛️ UNITED INTERNATIONAL UNIVERSITY</span>
            </div>

        
            <h1 class="hero-heading">
                Where UIU Research<br>
                <span>Comes Alive</span>
            </h1>

            <!-- Hero Subtitle -->
            <p class="hero-subtext">
                Discover groundbreaking papers, join research projects, and connect with <strong><?= number_format($member_count) ?>+
                    researchers</strong> across every discipline at UIU.
            </p>

            <!-- Action Buttons -->
            <div class="hero-buttons">
                <a href="research-exploer1.php" class="btn-white">
                    <span>🔍</span> Explore Research
                </a>
                <a href="register.php" class="btn-outline">
                    <span>➕</span> Join for Free
                </a>
            </div>

            <!-- Stats Counter -->
            <div class="hero-stats">
                <div class="stat-box">
                    <h3><?= number_format($paper_count) ?></h3>
                    <p>Papers Published</p>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-box">
                    <h3><?= number_format($member_count) ?></h3>
                    <p>Active Researchers</p>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-box">
                    <h3><?= number_format($project_count) ?></h3>
                    <p>Active Projects</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= CATEGORY PILLS BAR ================= -->
    <div class="category-bar">
        <div class="container pills-wrapper">
            <span class="pill-label">Popular Disciplines:</span>
            <div class="pills-list">
                <a href="research-exploer1.php" class="pill pill-purple">🤖 Artificial Intelligence</a>
                <a href="research-exploer1.php" class="pill pill-blue">💻 Computer Science</a>
                <a href="research-exploer1.php" class="pill pill-pink">📊 Data Science</a>
                <a href="research-exploer1.php" class="pill pill-orange">⚡ Electrical Engineering</a>
                <a href="research-exploer1.php" class="pill pill-green">🧬 Biotechnology</a>
                <a href="research-exploer1.php" class="pill pill-cyan">🔬 Physics</a>
            </div>
        </div>
    </div>

    <!-- ================= HOW IT WORKS ================= -->
    <section class="section how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title-box">
                <span class="sub-badge">HOW IT WORKS</span>
                <h2>Everything you need to advance your research</h2>
            </div>

            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon icon-blue">🔍</div>
                    <span class="step-num">01</span>
                    <h3>Discover Research</h3>
                    <p>Explore thousands of papers across all UIU departments using smart filters for keywords, authors,
                        and faculty.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon icon-purple">👥</div>
                    <span class="step-num">02</span>
                    <h3>Find Collaborators</h3>
                    <p>Connect with faculty, students, and researchers who share your domain interests for joint project
                        funding.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon icon-green">📤</div>
                    <span class="step-num">03</span>
                    <h3>Share Your Work</h3>
                    <p>Upload and publish your research papers to the UIU community to gain reads, citations, and peer
                        feedback.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card">
                    <div class="feature-icon icon-orange">💬</div>
                    <span class="step-num">04</span>
                    <h3>Join the Conversation</h3>
                    <p>Ask questions, share insights, and collaborate in cross-discipline scientific discussions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= TRENDING PAPERS ================= -->
    <section class="section section-gray" id="papers"><div class="container live-content"><h2>Latest Research Papers</h2><p><a href="research-exploer1.php">Browse all papers →</a></p><div class="grid"><?php if (!$papers): ?><p>No published papers yet.</p><?php endif; ?><?php foreach ($papers as $paper): ?><article class="box"><h3><?= e($paper['title']) ?></h3><p><?= e($paper['authors']) ?></p><a href="research-exploer1.php?q=<?= urlencode($paper['title']) ?>">View Paper</a></article><?php endforeach; ?></div></div></section>

    <!-- ================= JOIN ACTIVE PROJECTS ================= -->
    <section class="section"><div class="container live-content"><h2>Join Active Projects</h2><p><a href="Project.php">Browse projects →</a></p><div class="grid"><?php if (!$projects): ?><p>No active projects yet.</p><?php endif; ?><?php foreach ($projects as $project): ?><article class="box"><h3><?= e($project['title']) ?></h3><p><?= e($project['department']) ?></p><a href="project_details.php?id=<?= $project['id'] ?>">View Project</a></article><?php endforeach; ?></div></div></section>

    <!-- ================= TESTIMONIALS / COMMUNITY VOICES ================= -->
    

    <!-- ================= EXPLORE EVERY DISCIPLINE ================= -->
    <section class="section">
        <div class="container">
            <div class="section-title-box">
                <span class="sub-badge">BROWSE BY TOPIC</span>
                <h2>Explore Every Discipline</h2>
            </div>

            <div class="disciplines-grid">
                <!-- 1 -->
                <a href="research-exploer1.php" class="discipline-card disc-purple">
                    <span class="disc-icon">🤖</span>
                    <h3>Artificial Intelligence</h3>
                </a>
                <!-- 2 -->
                <a href="research-exploer1.php" class="discipline-card disc-blue">
                    <span class="disc-icon">💻</span>
                    <h3>Computer Science</h3>
                </a>
                <!-- 3 -->
                <a href="research-exploer1.php" class="discipline-card disc-red">
                    <span class="disc-icon">📊</span>
                    <h3>Data Science</h3>
                </a>
                <!-- 4 -->
                <a href="research-exploer1.php" class="discipline-card disc-orange">
                    <span class="disc-icon">⚡</span>
                    <h3>Electrical Engineering</h3>
                </a>
                <!-- 5 -->
                <a href="research-exploer1.php" class="discipline-card disc-green">
                    <span class="disc-icon">🧬</span>
                    <h3>Biotechnology</h3>
                </a>
                <!-- 6 -->
                <a href="research-exploer1.php" class="discipline-card disc-cyan">
                    <span class="disc-icon">🔬</span>
                    <h3>Physics</h3>
                </a>
                <!-- 7 -->
                <a href="research-exploer1.php" class="discipline-card disc-teal">
                    <span class="disc-icon">🌱</span>
                    <h3>Environmental Science</h3>
                </a>
                <!-- 8 -->
                <a href="research-exploer1.php" class="discipline-card disc-coral">
                    <span class="disc-icon">⚙️</span>
                    <h3>Nanotechnology</h3>
                </a>
            </div>
        </div>
    </section>

    <!-- ================= RESOURCES & GUIDES ================= -->
    

    <!-- ================= CTA BANNER ================= -->
    <section class="section cta-section">
        <div class="container text-center">
            <div class="cta-icon-box">🎓</div>
            <h2>Start your research journey today</h2>
            <p class="cta-desc">Join UIU's research community for free. Discover papers, collaborate on projects, and
                make your mark on the academic world.</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn-primary-large">Create Free Account</a>
                <a href="signIn.php" class="btn-outline-dark">Sign In</a>
            </div>
            <p class="cta-note">Free for all UIU students, faculty &amp; researchers. Open to alumni by request.</p>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="site-footer">
        <div class="container footer-content">
            <div class="footer-logo">
                <div class="logo-icon-small">UIU</div>
                <div class="footer-logo-text">
                    <strong>UIU Research Portal</strong>
                    <span>United International University</span>
                </div>
            </div>

            <div class="footer-links">
                <a href="research-exploer1.php">Research</a>
                <a href="Project.php">Projects</a>
                <a href="#how-it-works">About</a>
                <a href="register.php">Join Free</a>
                <a href="admin_index.php">Admin</a>
            </div>

            <div class="footer-socials">
                
                
                
                
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2024 United International University. All rights reserved. Built for UIU Research Community.</p>
        </div>
    </footer>

</body>

</html>