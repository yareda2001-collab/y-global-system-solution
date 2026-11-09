<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connect.php';

// Fetch logged-in user's details and permissions
$logged_in_user_id = intval($_SESSION['user_id']);
$admin_sql = "SELECT u.role, ar.can_create_admins 
              FROM users u 
              LEFT JOIN admin_rights ar ON u.id = ar.admin_id 
              WHERE u.id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $logged_in_user_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
$is_authorized = $logged_in_admin && ($logged_in_admin['role'] === 'super_admin' || $logged_in_admin['can_create_admins'] == 1);
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

// Handle form submission
$message = null;
$message_type = null;
$created_admin_credentials = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['created_admin_credentials_flash'])) {
    $created_admin_credentials = $_SESSION['created_admin_credentials_flash'];
    unset($_SESSION['created_admin_credentials_flash']);
    header('Cache-Control: no-store, private');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_authorized) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $profile_picture = null;

    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } else {
        // Check for duplicate username and email
        $check_sql = "SELECT username, email FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $username_taken = false;
        $email_taken = false;
        while ($row = $check_result->fetch_assoc()) {
            if ($row['username'] === $username) {
                $username_taken = true;
            }
            if ($row['email'] === $email) {
                $email_taken = true;
            }
        }
        $check_stmt->close();

        if ($username_taken && $email_taken) {
            $message = "Username and email already taken.";
            $message_type = "danger";
        } elseif ($username_taken) {
            $message = "Username is already taken.";
            $message_type = "danger";
        } elseif ($email_taken) {
            $message = "Email is already taken.";
            $message_type = "danger";
        }

        // Proceed if no duplicates found
        if (!$message) {
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/Uploads/';
                // Create directory if it doesn't exist
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $message = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
                    $message_type = "danger";
                } elseif ($_FILES['profile_picture']['size'] > 10 * 1024 * 1024) { // 10MB limit
                    $message = "File size exceeds 10MB limit.";
                    $message_type = "danger";
                } else {
                    $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                        $profile_picture = $filename;
                    } else {
                        $message = "Error uploading profile picture.";
                        $message_type = "danger";
                    }
                }
            }

            // Insert new admin if no validation errors
            if (!$message) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'admin';
                $force_password_change = 1;

                // Insert new admin
                $sql = "INSERT INTO users (username, email, password, role, force_password_change, profile_picture) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssis", $username, $email, $hashed_password, $role, $force_password_change, $profile_picture);
                try {
                    if ($stmt->execute()) {
                        $message = "Admin '$username' created successfully.";
                        $message_type = "success";
                        $_SESSION['created_admin_credentials_flash'] = [
                            'username' => $username,
                            'email' => $email,
                            'password' => $password,
                        ];
                        $password = null;
                    } else {
                        $message = "Error creating admin.";
                        $message_type = "danger";
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) { // Duplicate entry error
                        // Re-check to confirm both username and email
                        $recheck_sql = "SELECT username, email FROM users WHERE username = ? OR email = ?";
                        $recheck_stmt = $conn->prepare($recheck_sql);
                        $recheck_stmt->bind_param("ss", $username, $email);
                        $recheck_stmt->execute();
                        $recheck_result = $recheck_stmt->get_result();
                        $re_username_taken = false;
                        $re_email_taken = false;
                        while ($row = $recheck_result->fetch_assoc()) {
                            if ($row['username'] === $username) {
                                $re_username_taken = true;
                            }
                            if ($row['email'] === $email) {
                                $re_email_taken = true;
                            }
                        }
                        $recheck_stmt->close();

                        if ($re_username_taken && $re_email_taken) {
                            $message = "Username and email already taken.";
                            $message_type = "danger";
                        } elseif (stripos($e->getMessage(), 'username') !== false) {
                            $message = "Username is already taken.";
                            $message_type = "danger";
                        } elseif (stripos($e->getMessage(), 'email') !== false) {
                            $message = "Email is already taken.";
                            $message_type = "danger";
                        } else {
                            $message = "Duplicate entry detected.";
                            $message_type = "danger";
                        }
                    } else {
                        $message = "Error creating admin: " . $e->getMessage();
                        $message_type = "danger";
                    }
                }
                $stmt->close();
                if ($message_type === 'success') {
                    $conn->close();
                    header('Location: create_admin.php', true, 303);
                    exit;
                }
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
    <title>Create Admin - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for creating new admin accounts">
    <meta name="keywords" content="admin, dashboard, create admin, user management">
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
        .create-admin-section {
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
        .profile-picture-preview {
            display: none;
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 10%;
            border: 2px solid var(--primary-blue);
            margin: 10px auto;
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
        .alert {
            border-radius: 8px;
        }
        .password-container {
            position: relative;
        }
        .password-container .form-control {
            padding-right: 2.5rem;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-600);
            font-size: 1.2rem;
            user-select: none;
        }
        .password-toggle:hover {
            color: var(--primary-blue);
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
                padding: 1rem;
            }
            .form-container h2 {
                font-size: 1.8rem;
            }
            .profile-picture-preview {
                max-width: 100px;
                max-height: 100px;
            }
            video {
                max-width: 90%;
            }
            .logo h3 {
                font-size: 1.2rem;
            }
            .header-container {
                padding: 0.5rem;
            }
            .password-toggle {
                font-size: 1rem;
                right: 8px;
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
            .password-toggle {
                font-size: 0.9rem;
                right: 6px;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
    <link href="assets/css/admin-create-admin.css?v=1" rel="stylesheet">
</head>
<body class="services-page admin-app-page admin-create-admin-page">
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
        <section id="create-admin" class="create-admin-section section">
            <div class="container form-container" data-aos="fade-up">
                <div class="admin-create-heading">
                    <span class="admin-create-heading-icon"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                    <h3>Create an admin account!</h3>
                     <?php if ($is_authorized): ?>
                        <a class="btn btn-outline-primary mt-3" href="manage_admin_accounts.php">
                            <i class="bi bi-person-gear me-1" aria-hidden="true"></i> Manage admin accounts
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($message !== null && $message_type !== 'success'): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?> text-center mb-4" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <?php if ($created_admin_credentials !== null): ?>
                    <section class="created-credentials" aria-labelledby="createdCredentialsTitle">
                        <div class="credentials-success">
                            <span class="credentials-success-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                            <div>
                                <h2 id="createdCredentialsTitle">Admin account created</h2>
                                <p>Share these temporary sign-in details with the new admin.</p>
                            </div>
                        </div>
                        <div class="credential-field">
                            <label for="createdUsername">Username</label>
                            <div class="credential-value-wrap">
                                <input type="text" id="createdUsername" value="<?php echo htmlspecialchars($created_admin_credentials['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                <button type="button" class="credential-copy-button" data-copy-field="createdUsername" aria-label="Copy username">
                                    <i class="bi bi-copy" aria-hidden="true"></i><span>Copy</span>
                                </button>
                            </div>
                        </div>
                        <div class="credential-field">
                            <label for="createdEmail">Email</label>
                            <div class="credential-value-wrap">
                                <input type="email" id="createdEmail" value="<?php echo htmlspecialchars($created_admin_credentials['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                <button type="button" class="credential-copy-button" data-copy-field="createdEmail" aria-label="Copy email address">
                                    <i class="bi bi-copy" aria-hidden="true"></i><span>Copy</span>
                                </button>
                            </div>
                        </div>
                        <div class="credential-field">
                            <label for="createdPassword">Temporary password</label>
                            <div class="credential-value-wrap">
                                <input type="password" id="createdPassword" value="<?php echo htmlspecialchars($created_admin_credentials['password'], ENT_QUOTES, 'UTF-8'); ?>" readonly autocomplete="new-password">
                                <button type="button" class="credential-visibility-button" id="createdPasswordToggle" aria-label="Show temporary password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="credential-copy-button" data-copy-field="createdPassword" aria-label="Copy temporary password">
                                    <i class="bi bi-copy" aria-hidden="true"></i><span>Copy</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="copy-email-button" id="copyCredentialsEmail">
                            <i class="bi bi-envelope-paper" aria-hidden="true"></i><span>Copy email message</span>
                        </button>
                        <p class="credential-copy-status" id="credentialCopyStatus" role="status" aria-live="polite"></p>
                        <div class="credential-security-note">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            <span>For security, ask them to change this temporary password immediately after signing in.</span>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($is_authorized): ?>
                    <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" autocomplete="username" value="<?php echo $created_admin_credentials === null && isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                            <div class="invalid-feedback">Please enter a username.</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" value="<?php echo $created_admin_credentials === null && isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>
                        <div class="mb-3 password-container">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle bi bi-eye-slash" id="passwordToggle" aria-label="Show password" aria-controls="password" aria-pressed="false"></button>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile picture <span class="optional-label">Optional</span></label>
                            <div class="video-container">
                                <video id="video" autoplay playsinline></video>
                            </div>
                            <input type="file" class="form-control d-none" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                            <div>
                                <label for="profile_picture" class="custom-file-upload"><i class="bi bi-image" aria-hidden="true"></i> Choose photo</label>
                                <button type="button" class="capture-photo" id="captureButton"><i class="bi bi-camera" aria-hidden="true"></i> Take photo</button>
                            </div>
                            <img id="profile-picture-preview" class="profile-picture-preview" src="#" alt="Profile Picture Preview">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-check me-1" aria-hidden="true"></i> Create admin account</button>
                        <canvas id="canvas" style="display: none;"></canvas>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        You do not have permission to use this page.
                    </div>
                <?php endif; ?>
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

    <!-- Permission Modal (kept for compatibility, though not used with SweetAlert2) -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Access Denied</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You do not have permission to create admins.
                </div>
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

        // Show message with SweetAlert2 if there's a message
        <?php if ($message !== null && $message_type !== 'success'): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to create account',
                    text: '<?php echo addslashes($message); ?>',
                    confirmButtonColor: '#1e3a8a'
                });
            });
        <?php endif; ?>

        // Show permission alert with SweetAlert2 for unauthorized admins
        <?php if (!$is_authorized): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'You do not have permission to create admins.',
                    confirmButtonColor: '#1e3a8a'
                });
            });
        <?php endif; ?>

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
                    profilePicturePreview.style.display = 'none';
                    profilePicturePreview.src = '#';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePicturePreview.src = e.target.result;
                    profilePicturePreview.style.display = 'block';
                    stopCamera();
                };
                reader.readAsDataURL(file);
            } else {
                profilePicturePreview.style.display = 'none';
                profilePicturePreview.src = '#';
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
                profilePicturePreview.style.display = 'block';
                
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

        // Password visibility toggle
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        passwordToggle.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
            this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
            this.setAttribute('aria-pressed', type === 'text' ? 'true' : 'false');
        });

        const credentialCopyStatus = document.getElementById('credentialCopyStatus');
        if (credentialCopyStatus) {
            const credentialField = id => document.getElementById(id);
            const showCopyStatus = (message, isError = false) => {
                credentialCopyStatus.textContent = message;
                credentialCopyStatus.classList.toggle('copy-error', isError);
            };
            const copyText = async text => {
                if (navigator.clipboard && window.isSecureContext) {
                    try {
                        await navigator.clipboard.writeText(text);
                        return;
                    } catch (error) {
                        // Use the selection-based fallback when clipboard permission is unavailable.
                    }
                }

                const fallback = document.createElement('textarea');
                fallback.value = text;
                fallback.setAttribute('readonly', '');
                fallback.style.position = 'fixed';
                fallback.style.opacity = '0';
                document.body.appendChild(fallback);
                fallback.select();
                let copied;
                try {
                    copied = document.execCommand('copy');
                } finally {
                    fallback.remove();
                }
                if (!copied) {
                    throw new Error('Clipboard copy was not permitted.');
                }
            };

            document.querySelectorAll('[data-copy-field]').forEach(button => {
                button.addEventListener('click', async () => {
                    const input = credentialField(button.dataset.copyField);
                    try {
                        await copyText(input.value);
                        showCopyStatus(`${button.getAttribute('aria-label').replace(/^Copy\s+/i, '')} copied.`);
                    } catch (error) {
                        showCopyStatus('Copy was blocked. Select the value and copy it manually.', true);
                    }
                });
            });

            const createdPassword = credentialField('createdPassword');
            const createdPasswordToggle = document.getElementById('createdPasswordToggle');
            createdPasswordToggle.addEventListener('click', () => {
                const isHidden = createdPassword.type === 'password';
                createdPassword.type = isHidden ? 'text' : 'password';
                createdPasswordToggle.innerHTML = `<i class="bi ${isHidden ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
                createdPasswordToggle.setAttribute('aria-label', isHidden ? 'Hide temporary password' : 'Show temporary password');
            });

            document.getElementById('copyCredentialsEmail').addEventListener('click', async () => {
                const emailMessage = [
                    'Hello,',
                    '',
                    'Your Y-Global admin account has been created.',
                    `Username: ${credentialField('createdUsername').value}`,
                    `Email: ${credentialField('createdEmail').value}`,
                    `Temporary password: ${createdPassword.value}`,
                    '',
                    'Please sign in and change your password immediately.'
                ].join('\n');

                try {
                    await copyText(emailMessage);
                    showCopyStatus('Email message copied. Paste it into your email; this does not send it.');
                } catch (error) {
                    showCopyStatus('Copy was blocked. Please copy the details above manually.', true);
                }
            });
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