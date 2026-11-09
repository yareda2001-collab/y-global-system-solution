<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

$logged_in_user_id = intval($_SESSION['user_id']);
$message = '';
$message_type = '';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch unread messages for notification bell
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image_ids = [];

    // Handle multiple image uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB

        foreach ($_FILES['images']['name'] as $key => $image_name) {
            if ($_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
                $file_type = mime_content_type($_FILES['images']['tmp_name'][$key]);
                $file_size = $_FILES['images']['size'][$key];

                // Generate unique filename to avoid conflicts
                $file_extension = pathinfo($image_name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $unique_name;

                // Validate file type and size
                if (!in_array($file_type, $allowed_types)) {
                    $message = 'Only JPEG, PNG, and GIF images are allowed.';
                    $message_type = 'error';
                    break;
                } elseif ($file_size > $max_size) {
                    $message = 'Image size exceeds 10MB limit.';
                    $message_type = 'error';
                    break;
                } else {
                    // Upload image
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                        // Insert into images table
                        $stmt = $conn->prepare("INSERT INTO images (image_url) VALUES (?)");
                        if ($stmt) {
                            $stmt->bind_param("s", $target_file);
                            if ($stmt->execute()) {
                                $image_ids[] = $conn->insert_id;
                            } else {
                                $message = 'Error saving image to database: ' . $stmt->error;
                                $message_type = 'error';
                                $stmt->close();
                                break;
                            }
                            $stmt->close();
                        } else {
                            $message = 'Error preparing image insert query: ' . $conn->error;
                            $message_type = 'error';
                            break;
                        }
                    } else {
                        $message = "Error uploading image '$image_name'.";
                        $message_type = 'error';
                        break;
                    }
                }
            }
        }
    }

    // Insert post into database if no errors
    if (empty($message)) {
        $conn->begin_transaction();
        try {
            // Insert post
            $stmt = $conn->prepare("INSERT INTO posts (title, content) VALUES (?, ?)");
            if (!$stmt) {
                throw new Exception("Error preparing post insert query: " . $conn->error);
            }
            $stmt->bind_param("ss", $title, $content);
            $stmt->execute();
            $post_id = $conn->insert_id;
            $stmt->close();

            // Link images to post
            if (!empty($image_ids)) {
                $stmt = $conn->prepare("INSERT INTO post_images (post_id, image_id) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Error preparing post_images insert query: " . $conn->error);
                }
                foreach ($image_ids as $image_id) {
                    $stmt->bind_param("ii", $post_id, $image_id);
                    if (!$stmt->execute()) {
                        error_log("Failed to link image ID $image_id to post ID $post_id: " . $stmt->error);
                    }
                }
                $stmt->close();
            }

            $conn->commit();
            $message = 'Post created successfully.';
            $message_type = 'success';
            $_POST = [];
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error creating post: ' . $e->getMessage();
            $message_type = 'error';
        }
    }

    // Clean up uploaded files if post creation failed
    if ($message_type === 'error' && !empty($image_ids)) {
        foreach ($image_ids as $image_id) {
            // Select image URL
            $stmt = $conn->prepare("SELECT image_url FROM images WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (file_exists($row['image_url'])) {
                        unlink($row['image_url']);
                    }
                }
                $stmt->close();
            } else {
                error_log("Failed to prepare SELECT query for image ID $image_id: " . $conn->error);
            }

            // Delete image from database
            $stmt = $conn->prepare("DELETE FROM images WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                $stmt->close();
            } else {
                error_log("Failed to prepare DELETE query for image ID $image_id: " . $conn->error);
            }
        }
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Create Post - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for creating blog posts">
    <meta name="keywords" content="admin, dashboard, blog, create post">
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
        }
        html {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fa;
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
            border: none;
            cursor: pointer;
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
        .container1 {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        h2 {
            text-align: center;
            color: #033f5a;
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
            color: #333;
            margin-bottom: 8px;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group .helper-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
        }
        .form-group .error-text {
            font-size: 0.85rem;
            color: #dc3545;
            margin-top: 5px;
            display: none;
        }
        .form-group input[type="submit"] {
            background-color: #007bff;
            color: #fff;
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
        }
        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .form-group input[type="submit"]:active {
            transform: translateY(0);
        }
        .form-group input[type="submit"].loading {
            background-color: #4a90e2;
            cursor: not-allowed;
        }
        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
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
        .image-preview-container {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-preview {
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
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        .message.error {
            background-color: #fdeded;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }
        .message i {
            font-size: 1.2rem;
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
            .container1 {
                padding: 20px;
            }
            h2 {
                font-size: 1.75rem;
            }
            .form-group input[type="text"],
            .form-group textarea,
            .form-group input[type="file"] {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            .form-group input[type="submit"] {
                padding: 10px 20px;
                font-size: 0.95rem;
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
            .form-group label {
                font-size: 1rem;
            }
            .form-group .helper-text,
            .form-group .error-text {
                font-size: 0.8rem;
            }
            .image-preview {
                max-width: 100px;
                max-height: 100px;
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
        <section id="starter-section" class="starter-section section">
            <div class="container1" data-aos="fade-up" data-aos-duration="1000">
                <h2>Create Post</h2>
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <form id="createPostForm" method="post" action="" enctype="multipart/form-data" onsubmit="handleSubmit(event)">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" placeholder="Enter the post title" required aria-describedby="title-error">
                        <span class="error-text" id="title-error">Title must be between 1 and 100 characters.</span>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" placeholder="Write your post content here" required aria-describedby="content-error"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <span class="error-text" id="content-error">Content must be between 1 and 5000 characters.</span>
                    </div>
                    <div class="form-group">
                        <label for="images">Images (Optional, select multiple)</label>
                        <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif" multiple onchange="previewImages(event)">
                        <span class="helper-text">Supported formats: JPEG, PNG, GIF. Max size: 10MB each. Hold Ctrl (Windows) or Cmd (Mac) to select multiple images.</span>
                        <div class="image-preview-container" id="image-preview-container"></div>
                    </div>
                    <div class="form-group">
                        <input type="submit" id="submitBtn" value="Create Post">
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </section>
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
            const imagePreviewContainer = document.getElementById('image-preview-container');
            imagePreviewContainer.innerHTML = '';
            const files = event.target.files;

            if (files && files.length > 0) {
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'image-preview';
                        img.alt = 'Preview of selected image';
                        imagePreviewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
                imagePreviewContainer.style.display = 'flex';
            } else {
                imagePreviewContainer.style.display = 'none';
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

            // Content validation
            const content = document.getElementById('content').value.trim();
            const contentError = document.getElementById('content-error');
            if (content.length === 0 || content.length > 5000) {
                contentError.style.display = 'block';
                isValid = false;
            } else {
                contentError.style.display = 'none';
            }

            // Image validation (optional, but check size/type if present)
            const imageInput = document.getElementById('images');
            const files = imageInput.files;
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 10 * 1024 * 1024; // 10MB

            for (let file of files) {
                if (!allowedTypes.includes(file.type)) {
                    alert(`File ${file.name} is not a supported type. Only JPEG, PNG, and GIF are allowed.`);
                    isValid = false;
                } else if (file.size > maxSize) {
                    alert(`File ${file.name} exceeds 10MB limit.`);
                    isValid = false;
                }
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

            document.getElementById('createPostForm').submit();
        }

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