<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

// Fetch logged-in admin's details
$logged_in_user_id = intval($_SESSION['user_id']);
$admin_sql = "SELECT username, role FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $logged_in_user_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
$is_super_admin = $logged_in_admin && $logged_in_admin['role'] === 'super_admin';
$admin_stmt->close();

// Fetch unread messages for notification bell
$unread_messages_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0";
$unread_messages_stmt = $conn->prepare($unread_messages_sql);
$unread_messages_stmt->bind_param("i", $logged_in_user_id);
$unread_messages_stmt->execute();
$unread_messages_result = $unread_messages_stmt->get_result();
$unread_messages = $unread_messages_result->fetch_assoc()['total'];
$unread_messages_stmt->close();

// Count pending requests for notification bell
$sql = "SELECT COUNT(*) as pending_count FROM password_reset_requests WHERE status = 'pending'";
$result = $conn->query($sql);
$pending_count = $result->num_rows > 0 ? $result->fetch_assoc()['pending_count'] : 0;

// Fetch dashboard statistics
$total_users_sql = "SELECT COUNT(*) as total FROM users";
$total_users_result = $conn->query($total_users_sql);
$total_users = $total_users_result->fetch_assoc()['total'];

$active_users_sql = "SELECT COUNT(*) as active FROM users WHERE status = 1";
$active_users_result = $conn->query($active_users_sql);
$active_users = $active_users_result->fetch_assoc()['active'];

$posts_sql = "SELECT COUNT(*) as total FROM posts";
$posts_result = $conn->query($posts_sql);
$total_posts = $posts_result->fetch_assoc()['total'];

$portfolio_sql = "SELECT COUNT(*) as total FROM portfolio";
$portfolio_result = $conn->query($portfolio_sql);
$total_portfolio = $portfolio_result->fetch_assoc()['total'];

$contact_sql = "SELECT COUNT(*) as total FROM contact";
$contact_result = $conn->query($contact_sql);
$total_contacts = $contact_result->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Admin Dashboard - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing system statistics and user activities">
    <meta name="keywords" content="admin, dashboard, statistics, user management">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/img/favicon1.png" rel="icon">
    <link href="assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link href="assets/css/admin-dashboard.css?v=4" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --dark-blue: #1e3a8a;
            --light-blue: #93c5fd;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
            --gray-600: #4b5563;
            --gray-400: #9ca3af;
        }
        html {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        .header {
            background-color: var(--dark-blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .header-container {
            width: 98%;
            max-width: 1920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            flex-wrap: wrap;
        }
        .logo h3 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
            white-space: nowrap;
        }
        .navmenu {
            flex-grow: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .navmenu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
            align-items: center;
        }
        .navmenu ul li {
            margin: 0 0.3rem;
        }
        .navmenu ul li a {
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.6rem;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .navmenu ul li a:hover {
            color: var(--light-blue);
            transform: translateY(-2px);
        }
        .navmenu ul li a.active {
            color: var(--light-blue);
            border-bottom: 3px solid var(--light-blue);
        }
        .dropdown ul {
            background-color: #2d4b9e;
            border-radius: 8px;
            padding: 0.5rem 0;
            min-width: 150px;
        }
        .dropdown ul li a {
            color: white;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        .dropdown ul li a:hover {
            background-color: #3b82f6;
        }
        .btn-getstarted {
            background: var(--danger-red);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .btn-getstarted:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .mobile-nav-toggle {
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }
        .main {
            flex: 1;
            padding: 80px 20px;
        }
        .footer {
            margin-top: auto;
            background-color: var(--dark-blue);
        }
        .notification-bell {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }
        .notification-bell .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--danger-red);
            color: white;
            border-radius: 50%;
            padding: 4px 7px;
            font-size: 0.7rem;
        }
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            background: white;
            border-radius:16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        .stat-card h4 {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            height: 390px;
        }
        .chart-container h4 {
            color: var(--dark-blue);
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        #userStatusChartContainer {
            max-width: 250px;
            margin: 0 auto;
        }
        @media (max-width: 1200px) {
            .navmenu ul li {
                margin: 0 0.2rem;
            }
            .navmenu ul li a {
                font-size: 0.85rem;
                padding: 0.4rem 0.5rem;
            }
            .btn-getstarted {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
        }
        @media (max-width: 992px) {
            .header-container {
                justify-content: space-between;
                align-items: center;
            }
            .logo {
                order: 1;
            }
            .mobile-nav-toggle {
                display: block;
                order: 2;
                margin-right: 0.5rem;
            }
            .navmenu {
                order: 3;
                display: flex;
                align-items: center;
            }
            .btn-getstarted {
                margin-left: 0.5rem;
                order: 4;
            }
            .navmenu ul {
                display: none;
            }
            .navmenu ul.show {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background: #2d4b9e;
                padding: 1rem;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
            }
            .navmenu ul li {
                margin: 0.5rem 0;
            }
            .navmenu ul li a {
                font-size: 1rem;
                padding: 0.5rem;
            }
            .dropdown ul {
                position: static;
                background: #3b82f6;
                box-shadow: none;
                margin-left: 1rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px;
            }
            .dashboard-container {
                padding: 1rem;
                margin: 1rem;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-card i {
                font-size: 2rem;
            }
            .stat-card .stat-value {
                font-size: 1.5rem;
            }
            .chart-container {
                height: 380px;
                padding: 1rem;
            }
            #userStatusChartContainer {
                max-width: 200px;
            }
            .logo h3 {
                font-size: 1.2rem;
            }
            .header-container {
                padding: 0.5rem;
            }
        }
        @media (max-width: 576px) {
            .logo h3 {
                font-size: 1rem;
            }
            .mobile-nav-toggle {
                font-size: 1.3rem;
            }
            .navmenu ul.show {
                top: 50px;
            }
            .btn-getstarted {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
</head>
<body class="dashboard-page admin-dashboard-page admin-app-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <span class="admin-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="admin-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="admin_messages.php">Chat <span class="notification-bell"><i class="bi bi-bell"></i><?php if ($unread_messages > 0): ?><span class="badge"><?php echo $unread_messages; ?></span><?php endif; ?></span></a></li>
                    <li><a href="manage_reset_requests.php">Reset <span class="notification-bell"><i class="bi bi-bell"></i><?php if ($pending_count > 0): ?><span class="badge"><?php echo $pending_count; ?></span><?php endif; ?></span></a></li>
                    <li><a href="messages.php">Contact messages</a></li>
                    <li><a href="create_admin.php">Admins</a></li>
                    <li><a href="manage_admin_rights.php">Rights</a></li>
                    <li class="dropdown"><a href="blog.php"><span>Blog</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="create_post.php">Create Blog</a></li>
                            <li><a href="blog.php">View Blog</a></li>
                            <li><a href="manage_blog.php">Manage Blog</a></li>
                        </ul>
                    </li>
                    <li class="dropdown"><a href="createportfolio.php"><span>Portfolio</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="createportfolio.php">Create Portfolio</a></li>
                            <li><a href="manageportfolio.php">Manage Portfolio</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php">Users</a></li>
                    <li><a href="change_password.php">Password</a></li>
                    <li><a href="profile_settings.php">Profile</a></li>
                </ul>
                <button type="button" class="mobile-nav-toggle bi bi-list" aria-label="Open admin menu" aria-controls="admin-sidebar" aria-expanded="false"></button>
                 <button class="btn-getstarted" onclick="confirmLogout()" style="background-color: var(--danger-red);">Log Out</button>
            </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <main class="main">
        <div class="dashboard-container" data-aos="fade-up">
            <?php if ($logged_in_admin): ?>
                <section class="admin-welcome" aria-labelledby="adminWelcomeTitle">
                    <div class="admin-welcome-copy">
                        <span class="admin-welcome-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> Y-GLOBAL CONTROL CENTER</span>
                        <h1 id="adminWelcomeTitle">Welcome back,<br><span><?php echo htmlspecialchars($logged_in_admin['username']); ?></span></h1>
                        <p>Here’s an overview of your platform and the activity you manage.</p>
                        <span class="admin-role-chip"><i class="bi bi-shield-check" aria-hidden="true"></i> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $logged_in_admin['role']))); ?></span>
                    </div>
                    <div class="admin-welcome-art" aria-hidden="true">
                        <span class="admin-welcome-orbit admin-welcome-orbit-one"></span>
                        <span class="admin-welcome-orbit admin-welcome-orbit-two"></span>
                        <span class="admin-welcome-art-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="admin-welcome-art-caption">Platform overview</span>
                    </div>
                </section>
                <div class="admin-section-heading">
                    <div>
                        <span class="admin-section-kicker">AT A GLANCE</span>
                        <h2>Platform overview</h2>
                    </div>
                    <span class="admin-date-chip"><i class="bi bi-calendar3" aria-hidden="true"></i> <?php echo date('F j, Y'); ?></span>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center mb-4">
                    Unable to fetch admin details.
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-users" href="index.php" aria-label="View all users">
                        <span class="stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Total Users</h4><div class="stat-value"><?php echo $total_users; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-active" href="index.php" aria-label="View users and account statuses">
                        <span class="stat-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Active Users</h4><div class="stat-value"><?php echo $active_users; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-check2" aria-hidden="true"></i></span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-posts" href="manage_blog.php" aria-label="Manage blog posts">
                        <span class="stat-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Total Posts</h4><div class="stat-value"><?php echo $total_posts; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-messages" href="admin_messages.php" aria-label="Open user messages">
                        <span class="stat-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Unread Messages</h4><div class="stat-value"><?php echo $unread_messages; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-portfolio" href="manageportfolio.php" aria-label="Manage portfolio items">
                        <span class="stat-icon"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Portfolio Items</h4><div class="stat-value"><?php echo $total_portfolio; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a class="stat-card stat-contacts" href="messages.php" aria-label="View contact requests">
                        <span class="stat-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <div class="stat-copy"><h4>Contact Requests</h4><div class="stat-value"><?php echo $total_contacts; ?></div></div>
                        <span class="stat-accent"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                    </a>
                </div>
            </div>

            <div class="admin-section-heading admin-analytics-heading">
                <div>
                    <span class="admin-section-kicker">INSIGHTS</span>
                    <h2>Analytics</h2>
                </div>
                <p>Understand your users and content at a glance.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-heading">
                            <span class="chart-heading-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                            <div><h4>User status</h4><p>Active and inactive accounts</p></div>
                        </div>
                        <div id="userStatusChartContainer">
                            <canvas id="userStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-heading">
                            <span class="chart-heading-icon chart-content-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span>
                            <div><h4>Content overview</h4><p>Posts, portfolio, and contact activity</p></div>
                        </div>
                        <canvas id="contentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/admin-mobile-nav.php'; ?>
    <footer id="footer" class="footer" style="background-color: #4b5563; color: white;">
        <div class="container copyright text-center mt-4">
            <p><span>Copyright © <?php echo date("Y"); ?></span> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        AOS.init({ duration: 1000 });

        // Mobile Nav Toggle
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const navMenu = document.querySelector('.navmenu ul');
        mobileNavToggle.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });

        // Close mobile nav when clicking a link
        document.querySelectorAll('.navmenu ul li a').forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu.classList.contains('show')) {
                    navMenu.classList.remove('show');
                }
            });
        });

        // Handle dropdown toggles
        document.querySelectorAll('.toggle-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                const parentLi = this.parentElement;
                const dropdownMenu = parentLi.querySelector('ul');
                dropdownMenu.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown ul').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        // User Status Chart (Doughnut)
        const userStatusChart = new Chart(document.getElementById('userStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active Users', 'Inactive Users'],
                datasets: [{
                    data: [<?php echo $active_users; ?>, <?php echo $total_users - $active_users; ?>],
                    backgroundColor: ['#0e9fb7', '#d2e0e6'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Poppins',
                                size: 12
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        bodyFont: {
                            family: 'Poppins',
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
                spacing: 5
            }
        });

        // Content Overview Chart (Bar)
        const contentChart = new Chart(document.getElementById('contentChart'), {
            type: 'bar',
            data: {
                labels: ['Posts', 'Portfolio', 'Contacts'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $total_posts; ?>, <?php echo $total_portfolio; ?>, <?php echo $total_contacts; ?>],
                    backgroundColor: ['#0e9fb7', '#43a982', '#e9a94f'],
                    borderColor: '#fff',
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        bodyFont: {
                            family: 'Poppins',
                            size: 12
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });


         // Logout Confirmation Dialog
        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to log out of your account?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Log Out',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'animate-fadeIn',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
<script src="assets/js/admin-sidebar.js?v=1"></script>
</body>
</html>