<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/connect.php';

$logged_in_user_id = (int) $_SESSION['user_id'];
$unread_messages = 0;
$pending_count = 0;
$notification_error = false;
$db_connect_error = $db_config_error ?? null;

if ($conn === null && $db_connect_error !== null) {
    $notification_error = true;
}

if ($conn !== null) {
    try {
        $unread_messages_sql = "SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0";
        $unread_messages_stmt = $conn->prepare($unread_messages_sql);
        if ($unread_messages_stmt === false) {
            throw new RuntimeException('Could not prepare the unread-message count query.');
        }
        $unread_messages_stmt->bind_param("i", $logged_in_user_id);
        $unread_messages_stmt->execute();
        $unread_messages_result = $unread_messages_stmt->get_result();
        $unread_messages = (int) ($unread_messages_result->fetch_assoc()['total'] ?? 0);
        $unread_messages_stmt->close();
    } catch (mysqli_sql_exception | RuntimeException $error) {
        $notification_error = true;
        error_log('Change-password page could not load unread-message count: ' . $error->getMessage());
    }

    try {
        $pending_requests_result = $conn->query("SELECT COUNT(*) AS pending_count FROM password_reset_requests WHERE status = 'pending'");
        if ($pending_requests_result === false) {
            throw new RuntimeException('Could not load the pending reset-request count.');
        }
        $pending_count = (int) ($pending_requests_result->fetch_assoc()['pending_count'] ?? 0);
    } catch (mysqli_sql_exception | RuntimeException $error) {
        $notification_error = true;
        error_log('Change-password page could not load pending reset-request count: ' . $error->getMessage());
    }
}

// Handle password change
$message = null;
$message_type = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "danger";
    } else {
        // Verify current password
        if ($conn !== null) {
            try {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                if ($stmt === false) {
                    throw new RuntimeException('Could not prepare the password verification query.');
                }
                $stmt->bind_param("i", $logged_in_user_id);
                $stmt->execute();
                $user_data = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                    $message = "Current password is incorrect.";
                    $message_type = "danger";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
                    if ($stmt === false) {
                        throw new RuntimeException('Could not prepare the password update query.');
                    }
                    $stmt->bind_param("si", $hashed_password, $logged_in_user_id);
                    $stmt->execute();
                    $stmt->close();

                    unset($_SESSION['force_password_change']);
                    header("Location: dashboard.php");
                    exit;
                }
            } catch (mysqli_sql_exception | RuntimeException $error) {
                error_log('Admin password change failed: ' . $error->getMessage());
                $message = "The password could not be changed because of a server error. Please try again.";
                $message_type = "danger";
            }
        } else {
            $message = "The database is not configured yet. Add the LOCAL_DB_* values in your .env file and try again.";
            $message_type = "danger";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Change Password - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for changing password">
    <meta name="keywords" content="admin, dashboard, change password">
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
        .change-password-section {
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
        /* PASSWORD EYE ICON STYLES */
        .password-input-group {
            position: relative;
        }
        .password-input-group input {
            padding-right: 45px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-400);
            font-size: 1.1rem;
            z-index: 10;
            transition: color 0.3s ease;
        }
        .password-toggle:hover {
            color: var(--primary-blue);
        }
        .password-toggle.show {
            color: var(--success-green);
        }
        /* EMPTY FIELD ANIMATION */
        .empty-field-shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
    <link href="assets/css/admin-account.css?v=1" rel="stylesheet">
</head>
<body class="services-page admin-app-page admin-account-page admin-change-password-page">
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
    <?php if ($conn !== null) { $conn->close(); } ?>

    <main class="main">
        <section id="change-password" class="change-password-section section">
            <div class="container form-container" data-aos="fade-up">
                <div class="admin-account-heading">
                    <span class="admin-account-heading-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <h2>Change password</h2>
                    <p>Update your password to keep your admin account secure.</p>
                </div>
                <?php if ($db_connect_error !== null): ?>
                    <div class="alert alert-warning" role="alert">
                        <strong>Local setup needed:</strong> <?php echo htmlspecialchars($db_connect_error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <?php if ($notification_error): ?>
                    <div class="alert alert-warning" role="status">Some notification counts could not be loaded. You can still change your password.</div>
                <?php endif; ?>
                <?php if ($message !== null): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="needs-validation" novalidate id="passwordForm">
                    <!-- Current Password Field -->
                    <div class="mb-3 password-field">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="password-input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password" 
                                   required 
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle bi bi-eye position-absolute top-50 end-0 translate-middle-y me-2" id="toggleCurrent" aria-label="Show current password" onclick="togglePassword('current_password', 'toggleCurrent')"></button>
                        </div>
                        <div class="invalid-feedback">Enter your current password.</div>
                    </div>

                    <!-- New Password Field -->
                    <div class="mb-3 password-field">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="password-input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password" 
                                   required 
                                   minlength="6"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle bi bi-eye position-absolute top-50 end-0 translate-middle-y me-2" id="toggleNew" aria-label="Show new password" onclick="togglePassword('new_password', 'toggleNew')"></button>
                        </div>
                        <div class="invalid-feedback">Enter a new password with at least 6 characters.</div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-3 password-field">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="password-input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle bi bi-eye position-absolute top-50 end-0 translate-middle-y me-2" id="toggleConfirm" aria-label="Show password confirmation" onclick="togglePassword('confirm_password', 'toggleConfirm')"></button>
                        </div>
                        <div class="invalid-feedback">Confirm your new password.</div>
                    </div>

                    <div class="admin-password-hint"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Choose a password with at least 6 characters.</span></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> Update password</button>
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

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="assets/vendor/aos/aos.js"></script>
    <script defer src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script defer src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script defer src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script defer src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script defer src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script defer src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script defer src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // AOS Initialization
            if (typeof AOS !== 'undefined') {
                AOS.init({ duration: 1000 });
            }

            // Mobile Nav Toggle
            const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
            const navMenu = document.querySelector('.navmenu ul');
            if (mobileNavToggle && navMenu) {
                mobileNavToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('show');
                });
            }

            // Close mobile nav when clicking a link
            const navLinks = document.querySelectorAll('.navmenu ul li a');
            if (navLinks && navMenu) {
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (navMenu.classList.contains('show')) {
                            navMenu.classList.remove('show');
                        }
                    });
                });
            }

            // Handle dropdown toggles
            const dropdownToggles = document.querySelectorAll('.toggle-dropdown');
            if (dropdownToggles) {
                dropdownToggles.forEach(dropdown => {
                    dropdown.addEventListener('click', function(e) {
                        e.preventDefault();
                        const parentLi = this.parentElement;
                        const dropdownMenu = parentLi.querySelector('ul');
                        if (dropdownMenu) {
                            dropdownMenu.classList.toggle('show');
                        }
                    });
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    const dropdownMenus = document.querySelectorAll('.dropdown ul');
                    dropdownMenus.forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });

            // Show message with SweetAlert2 if there's a message
            <?php if (isset($message)): ?>
                Swal.fire({
                    icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
                    title: '<?php echo $message_type === "success" ? "Success" : "Error"; ?>',
                    text: '<?php echo addslashes($message); ?>',
                    confirmButtonColor: '#1e3a8a'
                });
            <?php endif; ?>

            // ENTER KEY NAVIGATION & EMPTY VALIDATION
            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const form = document.getElementById('passwordForm');

            // Current Password ENTER
            currentPassword.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentPassword.value.trim() === '') {
                        // SHAKE ANIMATION + ALERT
                        currentPassword.parentElement.parentElement.classList.add('empty-field-shake');
                        setTimeout(() => {
                            currentPassword.parentElement.parentElement.classList.remove('empty-field-shake');
                        }, 500);
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot be empty!',
                            text: 'Please enter your current password.',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        currentPassword.focus();
                    } else {
                        // GO TO NEW PASSWORD
                        newPassword.focus();
                    }
                }
            });

            // New Password ENTER
            newPassword.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (newPassword.value.trim() === '') {
                        // SHAKE ANIMATION + ALERT
                        newPassword.parentElement.parentElement.classList.add('empty-field-shake');
                        setTimeout(() => {
                            newPassword.parentElement.parentElement.classList.remove('empty-field-shake');
                        }, 500);
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot be empty!',
                            text: 'Please enter your new password.',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        newPassword.focus();
                    } else {
                        // GO TO CONFIRM PASSWORD
                        confirmPassword.focus();
                    }
                }
            });

            // Confirm Password ENTER
            confirmPassword.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (confirmPassword.value.trim() === '') {
                        // SHAKE ANIMATION + ALERT
                        confirmPassword.parentElement.parentElement.classList.add('empty-field-shake');
                        setTimeout(() => {
                            confirmPassword.parentElement.parentElement.classList.remove('empty-field-shake');
                        }, 500);
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot be empty!',
                            text: 'Please confirm your new password.',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        confirmPassword.focus();
                    } else {
                        // SUBMIT FORM
                        form.dispatchEvent(new Event('submit'));
                    }
                }
            });

            // Client-side form validation
            (function () {
                'use strict';
                const forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });

        // PASSWORD TOGGLE FUNCTION
        function togglePassword(inputId, toggleId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(toggleId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
                toggleIcon.classList.add('show');
                toggleIcon.setAttribute('aria-label', toggleIcon.getAttribute('aria-label').replace('Show', 'Hide'));
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
                toggleIcon.classList.remove('show');
                toggleIcon.setAttribute('aria-label', toggleIcon.getAttribute('aria-label').replace('Hide', 'Show'));
            }
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