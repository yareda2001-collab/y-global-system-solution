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

// Initialize message variables
$message = '';
$message_type = '';

$logged_in_user_id = intval($_SESSION['user_id']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $link = trim($_POST['link']);
    $description = trim($_POST['description']);
    $images = $_FILES['images'];

    // Validate inputs
    if (empty($title) || strlen($title) > 100) {
        $message = "Title must be between 1 and 100 characters.";
        $message_type = 'error';
    } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
        $message = "Invalid project link.";
        $message_type = 'error';
    } elseif (strlen($description) > 5000) {
        $message = "Description must be less than 5000 characters.";
        $message_type = 'error';
    } elseif (empty($images['name'][0])) {
        $message = "At least one image is required.";
        $message_type = 'error';
    } else {
        // Handle file uploads
        $targetDir = __DIR__ . '/images/';
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Unable to create the portfolio image directory.");
            }
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB
        $uploaded_images = [];
        $error = false;

        foreach ($images['name'] as $key => $name) {
            if ($images['error'][$key] !== UPLOAD_ERR_OK) {
                $message = "File upload failed for image " . ($key + 1) . ": Error code " . $images['error'][$key];
                $message_type = 'error';
                $error = true;
                break;
            }
            $file_type = mime_content_type($images['tmp_name'][$key]);
            if (!in_array($file_type, $allowed_types)) {
                $message = "Only JPEG, PNG, and GIF images are allowed for image " . ($key + 1) . ".";
                $message_type = 'error';
                $error = true;
                break;
            }
            if ($images['size'][$key] > $max_size) {
                $message = "Image " . ($key + 1) . " size exceeds 10MB limit.";
                $message_type = 'error';
                $error = true;
                break;
            }
            $imageName = basename($name);
            $storedPath = 'images/' . time() . '_' . $imageName;
            $targetFile = $targetDir . substr($storedPath, strlen('images/'));
            if (!move_uploaded_file($images['tmp_name'][$key], $targetFile)) {
                $message = "Failed to move uploaded image " . ($key + 1) . ".";
                $message_type = 'error';
                $error = true;
                break;
            }
            $uploaded_images[] = $storedPath;
        }

        if (!$error) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Insert into portfolio table
                $stmt = $conn->prepare("INSERT INTO portfolio (title, link, description) VALUES (?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sss", $title, $link, $description);
                if (!$stmt->execute()) {
                    throw new Exception("Error creating portfolio: " . $stmt->error);
                }
                $portfolio_id = $stmt->insert_id;
                $stmt->close();

                // Insert images into portfolio_images table and link to portfolio
                foreach ($uploaded_images as $image_path) {
                    $stmt = $conn->prepare("INSERT INTO portfolio_images (image_url) VALUES (?)");
                    $stmt->bind_param("s", $image_path);
                    if (!$stmt->execute()) {
                        throw new Exception("Error saving image: " . $stmt->error);
                    }
                    $image_id = $stmt->insert_id;
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO portfolio_image_mappings (portfolio_id, image_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $portfolio_id, $image_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error linking image to portfolio: " . $stmt->error);
                    }
                    $stmt->close();
                }

                $conn->commit();
                $message = "Portfolio created successfully!";
                $message_type = 'success';
                $_POST = [];
            } catch (Exception $e) {
                $conn->rollback();
                // Clean up uploaded files if transaction fails
                foreach ($uploaded_images as $file) {
                    $uploadedFile = __DIR__ . '/' . $file;
                    if (file_exists($uploadedFile)) {
                        unlink($uploadedFile);
                    }
                }
                $message = $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Create Portfolio - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for creating portfolio entries">
    <meta name="keywords" content="admin, dashboard, portfolio, create portfolio">
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
            padding: 80px 20px 40px;
            margin-top: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 200px);
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
        .container1 {
            max-width: 600px;
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
        }
        h3 {
            text-align: center;
            color: var(--dark-blue);
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 8px;
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid var(--gray-400);
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="url"]:focus,
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group .helper-text {
            font-size: 0.85rem;
            color: var(--gray-400);
            margin-top: 5px;
        }
        .form-group .error-text {
            font-size: 0.85rem;
            color: var(--danger-red);
            margin-top: 5px;
            display: none;
        }
        .form-group input[type="submit"] {
            background-color: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 auto;
            width: 100%;
        }
        .form-group input[type="submit"]:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }
        .form-group input[type="submit"]:active {
            transform: translateY(0);
        }
        .form-group input[type="submit"].loading {
            background-color: #60a5fa;
            cursor: not-allowed;
        }
        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid var(--white);
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .form-group input[type="submit"].loading .spinner {
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .image-preview {
            margin-top: 10px;
            max-width: 100%;
            border-radius: 8px;
            display: none;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            object-fit: cover;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .message.success {
            background-color: #e6f4ea;
            color: var(--success-green);
            border: 1px solid var(--success-green);
        }
        .message.error {
            background-color: #fdeded;
            color: var(--danger-red);
            border: 1px solid var(--danger-red);
        }
        .message i {
            font-size: 1.2rem;
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
            .container1 {
                padding: 20px;
            }
            h3 {
                font-size: 1.75rem;
            }
            .form-group input[type="text"],
            .form-group input[type="url"],
            .form-group textarea,
            .form-group input[type="file"] {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            .form-group input[type="submit"] {
                padding: 10px 20px;
                font-size: 0.95rem;
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
            .form-group label {
                font-size: 1rem;
            }
            .form-group .helper-text,
            .form-group .error-text {
                font-size: 0.8rem;
            }
            h3 {
                font-size: 1.5rem;
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
        <div class="container1" data-aos="fade-up" data-aos-duration="1000">
            <h3>Create Portfolio</h3>
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form id="createPortfolioForm" method="post" action="" enctype="multipart/form-data" onsubmit="handleSubmit(event)">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" placeholder="Enter the portfolio title" required aria-describedby="title-error">
                    <span class="error-text" id="title-error">Title must be between 1 and 100 characters.</span>
                </div>
                <div class="form-group">
                    <label for="link">Project Link</label>
                    <input type="url" id="link" name="link" value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link']) : ''; ?>" placeholder="Enter the project URL (e.g., https://example.com)" required aria-describedby="link-error">
                    <span class="error-text" id="link-error">Please enter a valid URL.</span>
                </div>
                <div class="form-group">
                    <label for="images">Images (Select multiple)</label>
                    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif" multiple required onchange="previewImages(event)">
                    <span class="helper-text">Supported formats: JPEG, PNG, GIF. Max size: 10MB each. Hold Ctrl/Cmd to select multiple files.</span>
                    <div class="image-preview" id="image-preview" role="region" aria-label="Preview of selected images"></div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe your project (optional)" aria-describedby="description-error"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <span class="error-text" id="description-error">Description must be less than 5000 characters.</span>
                </div>
                <div class="form-group">
                    <input type="submit" id="submitBtn" value="Create Portfolio">
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
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
        AOS.init();

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

        function previewImages(event) {
            const imagePreview = document.getElementById('image-preview');
            imagePreview.innerHTML = '';
            const files = event.target.files;
            if (files.length > 0) {
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '150px';
                        img.style.maxHeight = '150px';
                        img.style.borderRadius = '8px';
                        img.style.objectFit = 'cover';
                        imagePreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
                imagePreview.style.display = 'flex';
            } else {
                imagePreview.style.display = 'none';
            }
        }

        function validateForm() {
            let isValid = true;

            // Title validation
            const title = document.getElementById('title').value.trim();
            const titleError = document.getElementById('title-error');
            if (title.length === 0 || title.length > 100) {
                titleError.style.display = 'block';
                isValid = false;
            } else {
                titleError.style.display = 'none';
            }

            // Link validation
            const link = document.getElementById('link').value.trim();
            const linkError = document.getElementById('link-error');
            const urlPattern = /^(https?:\/\/)?([\w-]+(\.[\w-]+)+)(\/[\w-]*)*\/?$/;
            if (!urlPattern.test(link)) {
                linkError.style.display = 'block';
                isValid = false;
            } else {
                linkError.style.display = 'none';
            }

            // Description validation
            const description = document.getElementById('description').value.trim();
            const descriptionError = document.getElementById('description-error');
            if (description.length > 5000) {
                descriptionError.style.display = 'block';
                isValid = false;
            } else {
                descriptionError.style.display = 'none';
            }

            // Image validation
            const images = document.getElementById('images').files;
            if (images.length === 0) {
                const imageError = document.createElement('span');
                imageError.className = 'error-text';
                imageError.id = 'images-error';
                imageError.textContent = 'At least one image is required.';
                imageError.style.display = 'block';
                document.getElementById('images').parentNode.appendChild(imageError);
                isValid = false;
            } else {
                const existingError = document.getElementById('images-error');
                if (existingError) existingError.remove();
            }

            return isValid;
        }

        function handleSubmit(event) {
            event.preventDefault();

            if (!validateForm()) {
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            document.getElementById('createPortfolioForm').submit();
        }

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