<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connect.php';

// Fetch logged-in user's details
$logged_in_user_id = intval($_SESSION['user_id']);
$sql = "SELECT username, profile_picture, force_password_change FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();

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

// Redirect to change password if required
if ($user && $user['force_password_change'] == 1) {
    header("Location: change_password.php");
    exit;
}

// Handle profile picture upload
$message = null;
$message_type = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $upload = $_FILES['profile_picture'];
    if ($upload['error'] === UPLOAD_ERR_OK) {
        $target_dir = __DIR__ . '/Uploads/';
        if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true) && !is_dir($target_dir)) {
            $message = "Could not create the profile picture upload directory.";
            $message_type = "danger";
        } elseif (!is_writable($target_dir)) {
            $message = "The profile picture upload directory is not writable.";
            $message_type = "danger";
        }

        if ($message === null) {
            $file_ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            $allowed_mime_types = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ];
            $file_info = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $file_info->file($upload['tmp_name']);
            if (!isset($allowed_mime_types[$file_ext]) || $allowed_mime_types[$file_ext] !== $mime_type) {
                $message = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
                $message_type = "danger";
            } elseif ($upload['size'] > 10 * 1024 * 1024) {
                $message = "File size exceeds 10MB limit.";
                $message_type = "danger";
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $file_ext;
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($upload['tmp_name'], $target_file)) {
                    $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("si", $filename, $logged_in_user_id);
                    }
                    if ($stmt && $stmt->execute()) {
                        $message = "Profile picture updated successfully.";
                        $message_type = "success";
                        $user['profile_picture'] = $filename;
                    } else {
                        $message = "Could not save the profile picture. Please try again.";
                        $message_type = "danger";
                        error_log("Failed to update admin profile picture: " . ($stmt ? $stmt->error : $conn->error));
                        if (is_file($target_file)) {
                            unlink($target_file);
                        }
                    }
                    if ($stmt) {
                        $stmt->close();
                    }
                } else {
                    $message = "Could not save the uploaded image. Check the upload folder permissions and try again.";
                    $message_type = "danger";
                    error_log("Failed to move uploaded admin profile picture to $target_file");
                }
            }
        }
    } else {
        $message = match ($upload['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "The selected image is too large to upload.",
            UPLOAD_ERR_PARTIAL => "The image upload was interrupted. Please try again.",
            UPLOAD_ERR_NO_FILE => "Please select a file to upload or capture a photo.",
            default => "The image could not be uploaded. Please try again.",
        };
        $message_type = "danger";
    }
}

$conn->close();

// Determine profile picture URL
$profile_picture_filename = basename($user['profile_picture'] ?? '');
$profile_picture_url = $profile_picture_filename && $profile_picture_filename !== 'default-profile.png'
    ? (is_file(__DIR__ . '/Uploads/' . $profile_picture_filename)
        ? 'Uploads/' . rawurlencode($profile_picture_filename)
        : '../Uploads/' . rawurlencode($profile_picture_filename))
    : '../assets/img/default-profile.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Profile Settings - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing profile settings">
    <meta name="keywords" content="admin, dashboard, profile settings">
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
            padding: 60px 20px;
            padding-bottom: 20px;
        }
        @media(max-width:768px){
             .main {
                margin: 10px;
            }
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
        .profile-settings-section {
            padding: 4rem 0;
            background: #f3f4f6;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .form-container h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-blue);
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
        }
        .profile-pic-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10%;
            border: 2px solid var(--primary-blue);
            display: block;
            margin: 0 auto 1rem;
        }
        .custom-file-upload, .capture-photo {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #fff;
            background-color: var(--dark-blue);
            border: 1px solid var(--dark-blue);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 5px;
        }
        .custom-file-upload:hover, .capture-photo:hover {
            background-color: #025c8a;
        }
        .video-container {
            display: none;
            margin: 10px auto;
            text-align: center;
        }
        video {
            max-width: 100%;
            border-radius: 8px;
            border: 2px solid var(--dark-blue);
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: var(--dark-blue);
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
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
            .form-container {
                padding: 1.5rem;
            }
            video {
                max-width: 95%;
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
            .form-container h2 {
                font-size: 1.8rem;
            }
            .profile-pic-preview {
                width: 100px;
                height: 100px;
            }
            video {
                max-width: 90%;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
    <link href="assets/css/admin-account.css?v=1" rel="stylesheet">
</head>
<body class="services-page admin-app-page admin-account-page admin-profile-settings-page">
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
        <section id="profile-settings" class="profile-settings-section section">
            <div class="container form-container" data-aos="fade-up">
                <div class="admin-account-heading">
                    <span class="admin-account-heading-icon"><i class="bi bi-person-gear" aria-hidden="true"></i></span>
                    <h2>Profile settings</h2>
                    <p>Personalize your admin account with a profile photo that is easy to recognize.</p>
                </div>
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($profile_picture_url); ?>" alt="Profile Picture" class="profile-pic-preview" id="profile-picture-preview">
                    <p class="admin-profile-preview-label">Your current profile picture</p>
                </div>
                <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label d-block text-center">Choose how to add your photo</label>
                        <div class="video-container">
                            <video id="video" autoplay playsinline></video>
                        </div>
                        <input type="file" class="form-control visually-hidden" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <div class="d-flex justify-content-center flex-wrap">
                            <label for="profile_picture" class="custom-file-upload"><i class="bi bi-image" aria-hidden="true"></i> Choose a photo</label>
                            <button type="button" class="capture-photo" id="captureButton"><i class="bi bi-camera" aria-hidden="true"></i> Take a photo</button>
                        </div>
                        <small class="form-text text-muted d-block text-center mt-2">JPG, PNG, or GIF · Maximum file size 10 MB</small>
                        <div class="invalid-feedback">Please select an image file or capture a photo.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save profile picture</button>
                    <canvas id="canvas" style="display: none;"></canvas>
                </form>
            </div>
        </section>
    </main>

    <!-- Message Modal (kept for compatibility, though not used with SweetAlert2) -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="messageModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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

        // Show message with SweetAlert2 if there's a message
        <?php if (isset($message)): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
                    title: '<?php echo $message_type === "success" ? "Success" : "Error"; ?>',
                    text: '<?php echo addslashes($message); ?>',
                    confirmButtonColor: '#1e3a8a'
                });
            });
        <?php endif; ?>

        // Client-side form validation
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    const fileInput = form.querySelector('#profile_picture');
                    if (!fileInput.files.length && !form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Profile picture handling (file upload and webcam capture)
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const profilePictureInput = document.getElementById('profile_picture');
        const profilePicturePreview = document.getElementById('profile-picture-preview');
        const captureButton = document.getElementById('captureButton');
        let stream = null;

        // File upload preview
        profilePictureInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'File size exceeds 10MB limit.',
                        confirmButtonColor: '#1e3a8a'
                    });
                    profilePictureInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePicturePreview.src = e.target.result;
                    stopCamera();
                };
                reader.readAsDataURL(file);
            }
        });

        // Start camera or capture photo
        captureButton.addEventListener('click', async () => {
            if (!stream) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: { ideal: 'user' } }
                    });
                    video.srcObject = stream;
                    video.parentElement.style.display = 'block';
                    captureButton.textContent = 'Capture Photo';
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not access the camera. Please ensure it is connected and you granted permission.',
                        confirmButtonColor: '#1e3a8a'
                    });
                    return;
                }
            } else {
                // Capture photo
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = canvas.toDataURL('image/png');
                
                // Display preview
                profilePicturePreview.src = imageData;
                
                // Convert to file and assign to input
                canvas.toBlob(blob => {
                    const file = new File([blob], `photo_${Date.now()}.png`, { type: 'image/png' });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    profilePictureInput.files = dataTransfer.files;
                }, 'image/png');
                
                // Stop camera
                stopCamera();
            }
        });

        // Function to stop camera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                video.parentElement.style.display = 'none';
                captureButton.textContent = 'Take Photo';
            }
        }

        // Stop camera on page unload
        window.addEventListener('beforeunload', stopCamera);

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