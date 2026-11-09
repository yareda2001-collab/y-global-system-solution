<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

// Check if connection is valid
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

$logged_in_user_id = intval($_SESSION['user_id']);

// Fetch unread messages for notification bell
$unread_messages_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0";
$unread_messages_stmt = $conn->prepare($unread_messages_sql);
$unread_messages_stmt->bind_param("i", $logged_in_user_id);
$unread_messages_stmt->execute();
$unread_messages_result = $unread_messages_stmt->get_result();
$unread_messages = $unread_messages_result->fetch_assoc()['total'];
$unread_messages_stmt->close();

// Count pending reset requests for notification bell
$pending_requests_sql = "SELECT COUNT(*) as pending_count FROM password_reset_requests WHERE status = 'pending'";
$pending_requests_result = $conn->query($pending_requests_sql);
$pending_count = $pending_requests_result->num_rows > 0 ? $pending_requests_result->fetch_assoc()['pending_count'] : 0;

// Handle deletion
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        $errorMessage = "Invalid portfolio ID.";
    } else {
        $conn->begin_transaction();
        try {
            // Fetch image IDs
            $stmt = $conn->prepare("SELECT image_id FROM portfolio_image_mappings WHERE portfolio_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image_ids = [];
            while ($row = $result->fetch_assoc()) {
                $image_ids[] = $row['image_id'];
            }
            $stmt->close();

            // Delete portfolio
            $stmt = $conn->prepare("DELETE FROM portfolio WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting portfolio: " . $stmt->error);
            }
            $stmt->close();

            // Delete image files and records
            foreach ($image_ids as $image_id) {
                $stmt = $conn->prepare("SELECT image_url FROM portfolio_images WHERE id = ?");
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (file_exists($row['image_url'])) {
                        unlink($row['image_url']);
                    }
                }
                $stmt->close();
            }

            $conn->commit();
            $successMessage = "Portfolio deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = $e->getMessage();
        }
    }
}

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title']);
    $link = trim($_POST['link']);
    $description = trim($_POST['description']);
    $delete_images = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];
    $images = $_FILES['images'];

    if ($id === false || $id <= 0) {
        $errorMessage = "Invalid portfolio ID.";
    } elseif (empty($title) || strlen($title) > 100) {
        $errorMessage = "Title must be between 1 and 100 characters.";
    } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
        $errorMessage = "Invalid project link.";
    } elseif (strlen($description) > 5000) {
        $errorMessage = "Description must be less than 5000 characters.";
    } else {
        $conn->begin_transaction();
        try {
            // Update portfolio
            $stmt = $conn->prepare("UPDATE portfolio SET title = ?, link = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $link, $description, $id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating portfolio: " . $stmt->error);
            }
            $stmt->close();

            // Handle image deletions
            foreach ($delete_images as $image_id) {
                $image_id = filter_var($image_id, FILTER_VALIDATE_INT);
                if ($image_id) {
                    $stmt = $conn->prepare("SELECT image_url FROM portfolio_images WHERE id = ?");
                    $stmt->bind_param("i", $image_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        if (file_exists($row['image_url'])) {
                            unlink($row['image_url']);
                        }
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("DELETE FROM portfolio_image_mappings WHERE image_id = ? AND portfolio_id = ?");
                    $stmt->bind_param("ii", $image_id, $id);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("DELETE FROM portfolio_images WHERE id = ?");
                    $stmt->bind_param("i", $image_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // Handle new image uploads
            $targetDir = __DIR__ . '/images/';
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                    throw new RuntimeException("Unable to create the portfolio image directory.");
                }
            }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 10 * 1024 * 1024; // 10MB
            $uploaded_images = [];

            if (!empty($images['name'][0])) {
                foreach ($images['name'] as $key => $name) {
                    if ($images['error'][$key] === UPLOAD_ERR_OK) {
                        $file_type = mime_content_type($images['tmp_name'][$key]);
                        if (!in_array($file_type, $allowed_types)) {
                            throw new Exception("Only JPEG, PNG, and GIF images are allowed for image " . ($key + 1) . ".");
                        }
                        if ($images['size'][$key] > $max_size) {
                            throw new Exception("Image " . ($key + 1) . " size exceeds 10MB limit.");
                        }
                        $imageName = basename($name);
                        $storedPath = 'images/' . time() . '_' . $imageName;
                        $targetFile = $targetDir . substr($storedPath, strlen('images/'));
                        if (!move_uploaded_file($images['tmp_name'][$key], $targetFile)) {
                            throw new Exception("Failed to upload image " . ($key + 1) . ".");
                        }
                        $uploaded_images[] = $storedPath;
                    }
                }
            }

            // Insert new images
            foreach ($uploaded_images as $image_path) {
                $stmt = $conn->prepare("INSERT INTO portfolio_images (image_url) VALUES (?)");
                $stmt->bind_param("s", $image_path);
                if (!$stmt->execute()) {
                    throw new Exception("Error saving image: " . $stmt->error);
                }
                $image_id = $stmt->insert_id;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO portfolio_image_mappings (portfolio_id, image_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $id, $image_id);
                if (!$stmt->execute()) {
                    throw new Exception("Error linking image to portfolio: " . $stmt->error);
                }
                $stmt->close();
            }

            $conn->commit();
            $successMessage = "Portfolio updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            foreach ($uploaded_images as $file) {
                $uploadedFile = __DIR__ . '/' . $file;
                if (file_exists($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }
            $errorMessage = $e->getMessage();
        }
    }
}

// Fetch portfolios and their images
$sql = "SELECT p.id, p.title, p.link, p.description, GROUP_CONCAT(pi.image_url) as images, GROUP_CONCAT(pi.id) as image_ids
        FROM portfolio p
        LEFT JOIN portfolio_image_mappings pim ON p.id = pim.portfolio_id
        LEFT JOIN portfolio_images pi ON pim.image_id = pi.id
        GROUP BY p.id
        ORDER BY p.id DESC";
$result = $conn->query($sql);
$portfolioCount = $result->num_rows;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Portfolio - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing portfolio items">
    <meta name="keywords" content="admin, dashboard, manage portfolio">
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
            color: var(--white);
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
            color: var(--white);
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
        .navmenu ul li a[href="signup.php"],
        .navmenu ul li a[href="login.php"] {
            background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
            border-radius: 25px;
            padding: 8px 20px;
            margin-left: 10px;
            color: white;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(2, 136, 209, 0.3);
        }
        .navmenu ul li a[href="signup.php"]:hover,
        .navmenu ul li a[href="login.php"]:hover {
            background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
            color: #ffffff;
        }
        .navmenu ul li a[href="signup.php"]::before,
        .navmenu ul li a[href="login.php"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }
        .navmenu ul li a[href="signup.php"]:hover::before,
        .navmenu ul li a[href="login.php"]:hover::before {
            left: 100%;
        }
        .dropdown ul li a {
            color: var(--white);
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        .dropdown ul li a:hover {
            background-color: var(--primary-blue);
        }
        .btn-getstarted {
            background: var(--danger-red);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
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
            padding: 80px 20px;
        }
        .footer {
            margin-top: auto;
            background-color: var(--dark-blue);
            color: var(--white);
            padding: 1.5rem 0;
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
        }
        .content {
            max-width: 800px;
            margin: auto;
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .portfolio-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .portfolio-item .swiper {
            max-width: 100%;
            height: auto;
            min-height: 150px;
            margin-bottom: 10px;
        }
        .portfolio-item .swiper-slide {
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .portfolio-item .swiper-slide img {
            width: 100%;
            max-height: 300px;
            height: auto;
            object-fit: contain;
            border-radius: 5px;
        }
        .portfolio-item p {
            margin: 10px 0;
            color: var(--gray-600);
        }
        .form-control {
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .btn-custom {
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: var(--primary-blue);
            border: none;
            color: var(--white);
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .btn-warning {
            background-color: var(--warning-orange);
            border: none;
            color: var(--white);
        }
        .btn-warning:hover {
            background-color: #d97706;
        }
        .btn-danger {
            background-color: var(--danger-red);
            border: none;
            color: var(--white);
        }
        .btn-danger:hover {
            background-color: #dc2626;
        }
        h1, h3 {
            margin-bottom: 20px;
            color: var(--dark-blue);
            text-align: center;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: var(--dark-blue);
            color: var(--white);
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-body form .form-control {
            margin-bottom: 15px;
        }
        .modal-body form button {
            width: 100%;
            margin-top: 10px;
        }
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .image-preview img {
            max-width: 100px;
            max-height: 100px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 5px;
        }
        .image-preview label {
            position: relative;
            display: inline-block;
        }
        .image-preview input[type="checkbox"] {
            position: absolute;
            top: 5px;
            right: 5px;
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
                background: var(--primary-blue);
                box-shadow: none;
                margin-left: 1rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px;
            }
            .content {
                padding: 15px;
            }
            .portfolio-item {
                padding: 10px;
            }
            .portfolio-item .swiper {
                min-height: 120px;
            }
            .button-group {
                flex-direction: column;
                gap: 5px;
            }
            .btn-custom {
                width: 100%;
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
            h1, h3 {
                font-size: 1.5rem;
            }
            .portfolio-item .swiper {
                min-height: 100px;
            }
            .image-preview img {
                max-width: 80px;
                max-height: 80px;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
    <link href="assets/css/admin-portfolio.css?v=2" rel="stylesheet">
</head>
<body class="services-page admin-app-page portfolio-admin-page">
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
        <div class="content portfolio-management" data-aos="fade-up" data-aos-duration="1000">
            <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <section class="portfolio-hero" aria-labelledby="portfolioTitle">
                <div class="portfolio-hero-copy">
                    <span class="portfolio-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> YOUR WORK, BEAUTIFULLY ORGANIZED</span>
                    <h1 id="portfolioTitle">Portfolio library</h1>
                    <p>Manage the projects that showcase your best work. Update details, refresh images, or open a live project.</p>
                </div>
                <a class="portfolio-create-link" href="createportfolio.php">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add project</span>
                </a>
                <div class="portfolio-hero-art" aria-hidden="true">
                    <i class="bi bi-briefcase"></i>
                </div>
            </section>

            <section class="portfolio-library" aria-labelledby="portfolioLibraryTitle">
                <div class="portfolio-toolbar">
                    <div class="portfolio-library-heading">
                        <span class="portfolio-section-kicker">PROJECTS</span>
                        <div class="portfolio-heading-row">
                            <h2 id="portfolioLibraryTitle">Your portfolio</h2>
                            <span class="portfolio-count" id="portfolioCount"><?php echo $portfolioCount; ?> <?php echo $portfolioCount === 1 ? 'project' : 'projects'; ?></span>
                        </div>
                    </div>
                    <?php if ($portfolioCount > 0): ?>
                        <label class="portfolio-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="portfolioSearch" placeholder="Search projects" aria-label="Search portfolio projects">
                            <kbd>/</kbd>
                        </label>
                    <?php endif; ?>
                </div>

                <?php if ($portfolioCount === 0): ?>
                    <div class="portfolio-empty-state">
                        <span class="portfolio-empty-icon"><i class="bi bi-images" aria-hidden="true"></i></span>
                        <h3>Your portfolio is ready for its first project</h3>
                        <p>Add a project with images and a link to start building your showcase.</p>
                        <a class="portfolio-create-link" href="createportfolio.php"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add your first project</span></a>
                    </div>
                <?php else: ?>
                    <div class="portfolio-grid" id="portfolioGrid">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $images = $row['images'] ? explode(',', $row['images']) : [];
                            $image_ids = $row['image_ids'] ? explode(',', $row['image_ids']) : [];
                            ?>
                            <article class="portfolio-item" data-portfolio-card>
                                <div class="portfolio-media">
                                    <?php if (!empty($images)): ?>
                                        <div class="swiper">
                                            <div class="swiper-wrapper">
                                                <?php foreach ($images as $image): ?>
                                                    <div class="swiper-slide">
                                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($row['title']); ?> project image" loading="lazy">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if (count($images) > 1): ?>
                                                <div class="swiper-button-prev" aria-label="Previous image"></div>
                                                <div class="swiper-button-next" aria-label="Next image"></div>
                                                <div class="swiper-pagination"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="portfolio-no-image"><i class="bi bi-image" aria-hidden="true"></i><span>No project images</span></div>
                                    <?php endif; ?>
                                    <span class="portfolio-image-count"><i class="bi bi-images" aria-hidden="true"></i> <?php echo count($images); ?> <?php echo count($images) === 1 ? 'image' : 'images'; ?></span>
                                </div>

                                <div class="portfolio-item-content">
                                    <div class="portfolio-item-heading">
                                        <span class="portfolio-project-label">PROJECT</span>
                                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                    </div>
                                    <p class="portfolio-description"><?php echo htmlspecialchars($row['description'] ?: 'No description provided.'); ?></p>
                                    <a class="portfolio-project-link" href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank" rel="noopener noreferrer">
                                        <span><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Visit live project</span>
                                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                    <div class="portfolio-actions">
                                        <button type="button" class="btn btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span>
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-custom" onclick="return confirm('Are you sure you want to delete this portfolio?');">
                                            <i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span>
                                        </a>
                                    </div>
                                </div>
                            </article>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div>
                                                <span class="portfolio-section-kicker">PROJECT DETAILS</span>
                                                <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Edit portfolio</h5>
                                            </div>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="manageportfolio.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="title<?php echo $row['id']; ?>" class="form-label">Title</label>
                                                    <input type="text" name="title" id="title<?php echo $row['id']; ?>" required placeholder="Title" class="form-control" value="<?php echo htmlspecialchars($row['title']); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="link<?php echo $row['id']; ?>" class="form-label">Project Link</label>
                                                    <input type="url" name="link" id="link<?php echo $row['id']; ?>" required placeholder="Project Link" class="form-control" value="<?php echo htmlspecialchars($row['link']); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Current Images</label>
                                                    <div class="image-preview">
                                                        <?php foreach (array_combine($image_ids, $images) as $image_id => $image): ?>
                                                            <label>
                                                                <input type="checkbox" name="delete_images[]" value="<?php echo $image_id; ?>">
                                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Current project image">
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <small class="form-text text-muted">Check images to remove them.</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="images<?php echo $row['id']; ?>" class="form-label">Add New Images (Optional)</label>
                                                    <input type="file" name="images[]" id="images<?php echo $row['id']; ?>" accept="image/jpeg,image/png,image/gif" multiple class="form-control">
                                                    <small class="form-text text-muted">Select multiple images to add.</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="description<?php echo $row['id']; ?>" class="form-label">Description</label>
                                                    <textarea name="description" id="description<?php echo $row['id']; ?>" placeholder="Description" class="form-control" rows="4"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Save changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <p class="portfolio-search-empty" id="portfolioSearchEmpty" hidden>No projects match your search.</p>
                <?php endif; ?>
            </section>
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

        // Initialize each project gallery independently.
        document.querySelectorAll('.portfolio-item .swiper').forEach(swiperContainer => {
            const slideCount = swiperContainer.querySelectorAll('.swiper-slide').length;
            const swiperOptions = { loop: slideCount > 1, watchOverflow: true };

            if (slideCount > 1) {
                swiperOptions.pagination = {
                    el: swiperContainer.querySelector('.swiper-pagination'),
                    clickable: true,
                };
                swiperOptions.navigation = {
                    nextEl: swiperContainer.querySelector('.swiper-button-next'),
                    prevEl: swiperContainer.querySelector('.swiper-button-prev'),
                };
                swiperOptions.autoplay = {
                    delay: 3000,
                    disableOnInteraction: false,
                };
            }

            new Swiper(swiperContainer, swiperOptions);
        });

        const portfolioSearch = document.getElementById('portfolioSearch');
        const portfolioCards = Array.from(document.querySelectorAll('[data-portfolio-card]'));
        const portfolioCount = document.getElementById('portfolioCount');
        const portfolioSearchEmpty = document.getElementById('portfolioSearchEmpty');

        const filterPortfolio = () => {
            const query = portfolioSearch.value.trim().toLocaleLowerCase();
            let visibleCount = 0;

            portfolioCards.forEach(card => {
                const matches = card.textContent.toLocaleLowerCase().includes(query);
                card.hidden = !matches;
                if (matches) visibleCount++;
            });

            portfolioCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'project' : 'projects'}`;
            portfolioSearchEmpty.hidden = visibleCount > 0;
        };

        portfolioSearch?.addEventListener('input', filterPortfolio);
        document.addEventListener('keydown', event => {
            const target = event.target;
            const isTyping = target instanceof HTMLElement &&
                (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

            if (event.key === '/' && !isTyping && portfolioSearch) {
                event.preventDefault();
                portfolioSearch.focus();
            }
        });

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