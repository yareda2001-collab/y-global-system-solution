<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

// Fetch logged-in admin's details
$logged_in_user_id = intval($_SESSION['user_id']);
$admin_sql = "SELECT username, role FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_sql);
if ($admin_stmt) {
    $admin_stmt->bind_param("i", $logged_in_user_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
    $is_super_admin = $logged_in_admin && $logged_in_admin['role'] === 'super_admin';
    $admin_stmt->close();
} else {
    error_log("Failed to prepare admin query: " . $conn->error);
    $logged_in_admin = null;
    $is_super_admin = false;
}

// Fetch unread messages for notification
$unread_messages_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0";
$unread_messages_stmt = $conn->prepare($unread_messages_sql);
if ($unread_messages_stmt) {
    $unread_messages_stmt->bind_param("i", $logged_in_user_id);
    $unread_messages_stmt->execute();
    $unread_messages_result = $unread_messages_stmt->get_result();
    $unread_messages = $unread_messages_result->fetch_assoc()['total'];
    $unread_messages_stmt->close();
} else {
    error_log("Failed to prepare unread messages query: " . $conn->error);
    $unread_messages = 0;
}

// Count pending requests for notification bell
$sql = "SELECT COUNT(*) as pending_count FROM password_reset_requests WHERE status = 'pending'";
$result = $conn->query($sql);
$pending_count = $result->num_rows > 0 ? $result->fetch_assoc()['pending_count'] : 0;

// Fetch posts with images
$sql = "SELECT p.id, p.title, p.content, p.created_at, i.image_url
        FROM posts p
        LEFT JOIN post_images pi ON p.id = pi.post_id
        LEFT JOIN images i ON pi.image_id = i.id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
if (!$result) {
    error_log("Failed to fetch posts: " . $conn->error);
    $posts = [];
} else {
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $post_id = $row['id'];
        if (!isset($posts[$post_id])) {
            $posts[$post_id] = [
                'title' => $row['title'],
                'content' => $row['content'],
                'created_at' => $row['created_at'],
                'images' => []
            ];
        }
        if ($row['image_url']) {
            $posts[$post_id]['images'][] = $row['image_url'];
        }
    }
}

$conn->close();

function wrapText($text, $limit) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    $breakpoint = strrpos(substr($text, 0, $limit), ' ');
    if ($breakpoint === false) {
        $breakpoint = $limit;
    }
    return substr($text, 0, $breakpoint) . '...';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Blog - Y-Global Web Solution</title>
    <meta name="description" content="View the latest blog posts from Y-Global System Solution">
    <meta name="keywords" content="blog, posts, Y-Global, web solution">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/img/favicon1.png" rel="icon">
    <link href="assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
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
            --white: #ffffff;
            --background: #f3f4f6;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        html {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: var(--gray-600);
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
            padding: 0.75rem 1rem;
            flex-wrap: wrap;
        }
        .logo h3 {
            color: var(--white);
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
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
            margin: 0 0.5rem;
        }
        .navmenu ul li a {
            color: var(--white);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
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
            min-width: 160px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .dropdown ul li a {
            color: var(--white);
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .dropdown ul li a:hover {
            background-color: var(--primary-blue);
        }
        .btn-getstarted {
            background: var(--danger-red);
            color: var(--white);
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }
        .btn-getstarted:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .mobile-nav-toggle {
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }
        .main {
            flex: 1;
            padding: 80px 20px 10px;
            margin-top: 90px;
        }
        .footer {
            margin-top: auto;
            background-color: var(--dark-blue);
            color: var(--white);
            padding: 1.5rem 0;
        }
        .footer .copyright p {
            margin: 0;
            font-size: 0.9rem;
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
            color: var(--white);
            border-radius: 50%;
            padding: 4px 7px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        h2.section-title {
            text-align: center;
            color: var(--dark-blue);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            position: relative;
        }
        h2.section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary-blue);
            margin: 10px auto;
            border-radius: 2px;
        }
        .posts-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            padding: 20px 0;
        }
        .post-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .post-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .post-images {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            background: #f0f0f0;
        }
        .post-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            transition: transform 0.3s ease;
            display: block;
        }
        .post-images img:hover {
            transform: scale(1.05);
        }
        .post-images .swiper {
            width: 100%;
            height: 100%;
        }
        .post-images .swiper-slide {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .swiper-button-prev,
        .swiper-button-next {
            color: var(--white);
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }
        .swiper-button-prev:hover,
        .swiper-button-next:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        .swiper-button-prev::after,
        .swiper-button-next::after {
            font-size: 18px;
        }
        .swiper-pagination {
            bottom: 10px;
        }
        .swiper-pagination-bullet {
            background: var(--white);
            opacity: 0.7;
        }
        .swiper-pagination-bullet-active {
            background: var(--primary-blue);
            opacity: 1;
        }
        .post-content {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .post-content h3 {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin: 0 0 12px;
            line-height: 1.4;
        }
        .post-meta {
            font-size: 0.85rem;
            color: var(--gray-400);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .post-meta i {
            font-size: 1rem;
            color: var(--gray-400);
        }
        .post-excerpt {
            font-size: 0.95rem;
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 16px;
            transition: opacity 0.3s ease, max-height 0.5s ease;
            overflow: hidden;
            max-height: 100px;
        }
        .post-excerpt.short-content {
            display: block;
        }
        .post-excerpt.full-content {
            display: none;
            max-height: none;
        }
        .post-excerpt.expanded {
            display: block;
            max-height: none;
            opacity: 1;
        }
        .post-excerpt.collapsed {
            opacity: 0.7;
        }
        .read-toggle {
            color: var(--primary-blue);
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.3s ease;
            display: inline-block;
            margin-top: 8px;
        }
        .read-toggle:hover {
            color: blue;
            text-decoration: none;
        }
        .read-toggle[aria-expanded="true"] .read-more-text,
        .read-toggle[aria-expanded="false"] .read-less-text {
            display: inline;
        }
        .read-toggle[aria-expanded="true"] .read-less-text,
        .read-toggle[aria-expanded="false"] .read-more-text {
            display: none;
        }
        .no-posts {
            text-align: center;
            font-size: 1.2rem;
            color: var(--gray-400);
            padding: 40px 0;
            grid-column: 1 / -1;
        }
        @media (max-width: 991px) {
            .posts-container {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .post-card {
                max-width: 500px;
                margin: 0 auto;
            }
        }
        @media (max-width: 1200px) {
            .navmenu ul li {
                margin: 0 0.3rem;
            }
            .navmenu ul li a {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
            }
            .btn-getstarted {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
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
                background: var(--primary-blue);
                box-shadow: none;
                margin-left: 1rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px 30px;
            }
            h2.section-title {
                font-size: 2rem;
            }
            .post-images {
                aspect-ratio: 4 / 3;
            }
            .post-content h3 {
                font-size: 1.4rem;
            }
            .post-excerpt {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 576px) {
            .logo h3 {
                font-size: 1.2rem;
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
            h2.section-title {
                font-size: 1.8rem;
            }
            .post-content h3 {
                font-size: 1.3rem;
            }
            .post-excerpt {
                font-size: 0.85rem;
            }
            .post-images {
                aspect-ratio: 4 / 3;
            }
            .post-content {
                padding: 16px;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
</head>
<body class="services-page admin-app-page">
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
        <section id="blog-section" class="content section">
            <h2 class="section-title" data-aos="fade-up">Our Latest Blogs</h2>
            <div class="posts-container" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post_id => $post): ?>
                        <div class="post-card" data-aos="fade-up" data-aos-delay="200">
                            <?php if (!empty($post['images'])): ?>
                                <div class="post-images">
                                    <?php if (count($post['images']) > 1): ?>
                                        <div class="swiper">
                                            <div class="swiper-wrapper">
                                                <?php foreach ($post['images'] as $image): ?>
                                                    <div class="swiper-slide">
                                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Post image" loading="lazy">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="swiper-button-prev"></div>
                                            <div class="swiper-button-next"></div>
                                            <div class="swiper-pagination"></div>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($post['images'][0]); ?>" alt="Post image" loading="lazy">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="post-content">
                                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="post-meta">
                                    <i class="bi bi-calendar3"></i>
                                    Posted on <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?>
                                </div>
                                <div class="post-excerpt short-content" id="short-content-<?php echo $post_id; ?>">
                                    <?php echo htmlspecialchars(wrapText($post['content'], 150)); ?>
                                </div>
                                <div class="post-excerpt full-content" id="full-content-<?php echo $post_id; ?>">
                                    <?php echo htmlspecialchars($post['content']); ?>
                                </div>
                                <?php if (strlen($post['content']) > 150): ?>
                                    <button class="read-toggle" 
                                            aria-expanded="false" 
                                            aria-controls="full-content-<?php echo $post_id; ?>" 
                                            data-post-id="<?php echo $post_id; ?>">
                                        <span class="read-more-text">Read More</span>
                                        <span class="read-less-text">Read Less</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-posts">No posts found.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php include __DIR__ . '/../includes/admin-mobile-nav.php'; ?>
<footer id="footer" class="footer" style="background-color: #4b5563; color: white;">
        <div class="container copyright text-center">
            <p>Copyright © <?php echo date("Y"); ?> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> All Rights Reserved</p>
        </div>
    </footer>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
        mobileNavToggle.addEventListener('click', () => {
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
            dropdown.addEventListener('click', e => {
                e.preventDefault();
                const parentLi = dropdown.parentElement;
                const dropdownMenu = parentLi.querySelector('ul');
                dropdownMenu.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', e => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown ul').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        // Read More/Less Functionality
        document.querySelectorAll('.read-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const postId = button.getAttribute('data-post-id');
                const shortContent = document.getElementById(`short-content-${postId}`);
                const fullContent = document.getElementById(`full-content-${postId}`);
                const isExpanded = button.getAttribute('aria-expanded') === 'true';

                if (isExpanded) {
                    fullContent.style.display = 'none';
                    shortContent.style.display = 'block';
                    shortContent.classList.add('collapsed');
                    button.setAttribute('aria-expanded', 'false');
                } else {
                    shortContent.style.display = 'none';
                    fullContent.style.display = 'block';
                    fullContent.classList.add('expanded');
                    button.setAttribute('aria-expanded', 'true');
                }
            });

            // Keyboard accessibility
            button.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });

        // Initialize Swiper for multiple images
        document.querySelectorAll('.post-images .swiper').forEach(swiperEl => {
            new Swiper(swiperEl, {
                loop: true,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                speed: 600,
                spaceBetween: 0,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
            });
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