<?php
session_start();

// Enable error reporting for debugging (comment out in production)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

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
$sql = "SELECT p.id, p.title, p.content, p.created_at, i.id as image_id, i.image_url
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
            $posts[$post_id]['images'][] = [
                'id' => $row['image_id'],
                'url' => $row['image_url']
            ];
        }
    }
}

// Handle post deletion
if (isset($_POST['delete_post'])) {
    $post_id = intval($_POST['post_id']);
    // Fetch images associated with the post
    $image_sql = "SELECT i.id, i.image_url FROM images i
                  JOIN post_images pi ON i.id = pi.image_id
                  WHERE pi.post_id = ?";
    $image_stmt = $conn->prepare($image_sql);
    $image_stmt->bind_param("i", $post_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    $images_to_check = [];
    while ($row = $image_result->fetch_assoc()) {
        $images_to_check[] = $row;
    }
    $image_stmt->close();

    // Delete post (cascades to post_images)
    $delete_sql = "DELETE FROM posts WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $post_id);
    if ($delete_stmt->execute()) {
        // Check and delete unused images
        foreach ($images_to_check as $image) {
            $check_sql = "SELECT COUNT(*) as count FROM post_images WHERE image_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $image['id']);
            $check_stmt->execute();
            $count = $check_stmt->get_result()->fetch_assoc()['count'];
            $check_stmt->close();
            if ($count == 0) {
                $delete_image_sql = "DELETE FROM images WHERE id = ?";
                $delete_image_stmt = $conn->prepare($delete_image_sql);
                $delete_image_stmt->bind_param("i", $image['id']);
                $delete_image_stmt->execute();
                $delete_image_stmt->close();
                if (file_exists($image['image_url'])) {
                    unlink($image['image_url']);
                }
            }
        }
        $_SESSION['success'] = "Post deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete post.";
    }
    $delete_stmt->close();
    header("Location: manage_blog.php");
    exit;
}

// Handle post edit
if (isset($_POST['edit_post'])) {
    $post_id = intval($_POST['post_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and content are required.";
    } else {
        $update_sql = "UPDATE posts SET title = ?, content = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $title, $content, $post_id);
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Post updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update post.";
        }
        $update_stmt->close();
    }
    header("Location: manage_blog.php");
    exit;
}

// Handle image upload
if (isset($_POST['add_image'])) {
    $post_id = intval($_POST['post_id']);
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_type, $allowed_types) || $file_size > $max_size) {
            $_SESSION['error'] = "Invalid image format or size. Use JPEG/PNG, max 2MB.";
        } else {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $insert_image_sql = "INSERT INTO images (image_url) VALUES (?)";
                $insert_image_stmt = $conn->prepare($insert_image_sql);
                $insert_image_stmt->bind_param("s", $file_path);
                $insert_image_stmt->execute();
                $image_id = $conn->insert_id;
                $insert_image_stmt->close();
                $link_sql = "INSERT INTO post_images (post_id, image_id) VALUES (?, ?)";
                $link_stmt = $conn->prepare($link_sql);
                $link_stmt->bind_param("ii", $post_id, $image_id);
                if ($link_stmt->execute()) {
                    $_SESSION['success'] = "Image added successfully.";
                } else {
                    $_SESSION['error'] = "Failed to link image to post.";
                    $delete_image_sql = "DELETE FROM images WHERE id = ?";
                    $delete_image_stmt = $conn->prepare($delete_image_sql);
                    $delete_image_stmt->bind_param("i", $image_id);
                    $delete_image_stmt->execute();
                    $delete_image_stmt->close();
                    unlink($file_path);
                }
                $link_stmt->close();
            } else {
                $_SESSION['error'] = "Failed to upload image.";
            }
        }
    } else {
        $_SESSION['error'] = "No image uploaded or upload error.";
    }
    header("Location: manage_blog.php");
    exit;
}

// Handle image deletion
if (isset($_POST['delete_image'])) {
    $image_id = intval($_POST['image_id']);
    $post_id = intval($_POST['post_id']);
    $check_sql = "SELECT COUNT(*) as count FROM post_images WHERE image_id = ? AND post_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $image_id, $post_id);
    $check_stmt->execute();
    $count = $check_stmt->get_result()->fetch_assoc()['count'];
    $check_stmt->close();
    $delete_link_sql = "DELETE FROM post_images WHERE image_id = ? AND post_id = ?";
    $delete_link_stmt = $conn->prepare($delete_link_sql);
    $delete_link_stmt->bind_param("ii", $image_id, $post_id);
    if ($delete_link_stmt->execute()) {
        if ($count == 0) {
            $image_sql = "SELECT image_url FROM images WHERE id = ?";
            $image_stmt = $conn->prepare($image_sql);
            $image_stmt->bind_param("i", $image_id);
            $image_stmt->execute();
            $image_url = $image_stmt->get_result()->fetch_assoc()['image_url'];
            $image_stmt->close();
            $delete_image_sql = "DELETE FROM images WHERE id = ?";
            $delete_image_stmt = $conn->prepare($delete_image_sql);
            $delete_image_stmt->bind_param("i", $image_id);
            $delete_image_stmt->execute();
            $delete_image_stmt->close();
            if (file_exists($image_url)) {
                unlink($image_url);
            }
        }
        $_SESSION['success'] = "Image deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete image.";
    }
    $delete_link_stmt->close();
    header("Location: manage_blog.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Blogs - Y-Global Web Solution</title>
    <meta name="description" content="Manage blog posts for Y-Global System Solution">
    <meta name="keywords" content="manage blog, posts, Y-Global, web solution">
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
        .content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            min-height: 400px;
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
            min-height: 300px;
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
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .post-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            transition: transform 0.3s ease;
        }
        .post-images img:hover {
            transform: scale(1.05);
        }
        .post-images .no-image {
            font-size: 0.9rem;
            color: var(--gray-400);
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
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-edit {
            background: var(--primary-blue);
            color: var(--white);
        }
        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .btn-images {
            background: var(--warning-orange);
            color: var(--white);
        }
        .btn-images:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        .btn-delete {
            background: var(--danger-red);
            color: var(--white);
        }
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .modal-content {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        .modal-header {
            background: var(--dark-blue);
            color: var(--white);
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
        }
        .modal-body {
            padding: 24px;
        }
        .modal-body .form-group {
            margin-bottom: 20px;
        }
        .modal-body label {
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 8px;
            display: block;
        }
        .modal-body input,
        .modal-body textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray-400);
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .modal-body textarea {
            resize: vertical;
            min-height: 100px;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .image-item {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
        }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-item .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger-red);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .image-item .delete-image:hover {
            background: #dc2626;
        }
        .no-posts {
            text-align: center;
            font-size: 1.5rem;
            color: var(--gray-600);
            padding: 60px 0;
            grid-column: 1 / -1;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin: 20px 0;
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
        <section id="manage-blog-section" class="content section">
            <h2 class="section-title" data-aos="fade-up">Manage Blogs</h2>
            <div class="posts-container" data-aos="fade-up" data-aos-delay="100">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post_id => $post): ?>
                        <div class="post-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="post-images">
                                <?php if (!empty($post['images'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['images'][0]['url']); ?>" alt="Post thumbnail" loading="lazy">
                                <?php else: ?>
                                    <span class="no-image">No Image</span>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="post-meta">
                                    <i class="bi bi-calendar3"></i>
                                    Posted on <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editPostModal<?php echo $post_id; ?>" aria-label="Edit post">Edit</button>
                                    <button class="btn-images" data-bs-toggle="modal" data-bs-target="#manageImagesModal<?php echo $post_id; ?>" aria-label="Manage images">Images</button>
                                    <button class="btn-delete" onclick="confirmDelete(<?php echo $post_id; ?>)" aria-label="Delete post">Delete</button>
                                </div>
                            </div>
                            <!-- Edit Post Modal -->
                            <div class="modal fade" id="editPostModal<?php echo $post_id; ?>" tabindex="-1" aria-labelledby="editPostModalLabel<?php echo $post_id; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editPostModalLabel<?php echo $post_id; ?>">Edit Post</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="manage_blog.php" method="POST">
                                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                                <div class="form-group">
                                                    <label for="title<?php echo $post_id; ?>">Title</label>
                                                    <input type="text" id="title<?php echo $post_id; ?>" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="content<?php echo $post_id; ?>">Content</label>
                                                    <textarea id="content<?php echo $post_id; ?>" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                                </div>
                                                <button type="submit" name="edit_post" class="btn btn-primary">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Manage Images Modal -->
                            <div class="modal fade" id="manageImagesModal<?php echo $post_id; ?>" tabindex="-1" aria-labelledby="manageImagesModalLabel<?php echo $post_id; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="manageImagesModalLabel<?php echo $post_id; ?>">Manage Images for "<?php echo htmlspecialchars($post['title']); ?>"</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if (!empty($post['images'])): ?>
                                                <div class="image-grid">
                                                    <?php foreach ($post['images'] as $img): ?>
                                                        <div class="image-item">
                                                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="Post image" loading="lazy">
                                                            <form action="manage_blog.php" method="POST" style="display: inline;">
                                                                <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                                                <button type="submit" name="delete_image" class="delete-image" aria-label="Delete image" onclick="return confirm('Are you sure you want to delete this image?');">
                                                                    <i class="bi bi-x"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p>No images available.</p>
                                            <?php endif; ?>
                                            <form action="manage_blog.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                                <div class="form-group">
                                                    <label for="image<?php echo $post_id; ?>">Upload New Image</label>
                                                    <input type="file" id="image<?php echo $post_id; ?>" name="image" accept="image/jpeg,image/png" required>
                                                </div>
                                                <button type="submit" name="add_image" class="btn btn-primary">Upload Image</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-posts">No posts found. Create a new post to get started!</p>
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
        if (mobileNavToggle && navMenu) {
            mobileNavToggle.addEventListener('click', () => {
                navMenu.classList.toggle('show');
            });
        }

        // Close mobile nav when clicking a link
        if (navMenu) {
            document.querySelectorAll('.navmenu ul li a').forEach(link => {
                link.addEventListener('click', () => {
                    if (navMenu.classList.contains('show')) {
                        navMenu.classList.remove('show');
                    }
                });
            });
        }

        // Handle dropdown toggles
        document.querySelectorAll('.toggle-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', e => {
                e.preventDefault();
                const parentLi = dropdown.parentElement;
                if (!parentLi) return;
                const dropdownMenu = parentLi.querySelector('ul');
                if (dropdownMenu) {
                    dropdownMenu.classList.toggle('show');
                }
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

        // SweetAlert2 for Delete Confirmation
        function confirmDelete(postId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action will permanently delete the post and its images.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'manage_blog.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'post_id';
                    input.value = postId;
                    const inputDelete = document.createElement('input');
                    inputDelete.type = 'hidden';
                    inputDelete.name = 'delete_post';
                    inputDelete.value = '1';
                    form.appendChild(input);
                    form.appendChild(inputDelete);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Display Success/Error Messages
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo $_SESSION['success']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $_SESSION['error']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

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