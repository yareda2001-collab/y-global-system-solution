<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

// Fetch logged-in admin's details and permissions
$logged_in_user_id = intval($_SESSION['user_id']);
$admin_sql = "SELECT u.username, u.role, ar.can_manage_users 
              FROM users u 
              LEFT JOIN admin_rights ar ON u.id = ar.admin_id 
              WHERE u.id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $logged_in_user_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
$is_authorized = $logged_in_admin && ($logged_in_admin['role'] === 'super_admin' || $logged_in_admin['can_manage_users'] == 1);
$admin_stmt->close();

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

// Log admin details for debugging
error_log("Logged-in User ID: $logged_in_user_id, Role: " . ($logged_in_admin ? $logged_in_admin['role'] : 'Not found') . ", Is Authorized: " . ($is_authorized ? 'true' : 'false'));

// Handle activate/deactivate operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_id']) && $is_authorized) {
    $toggle_id = intval($_POST['toggle_id']);
    if ($toggle_id === $logged_in_user_id) {
        $message = "Cannot modify your own account.";
        $message_type = "danger";
    } else {
        $check_role_sql = "SELECT role FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_role_sql);
        $check_stmt->bind_param("i", $toggle_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $user_role = $check_result->num_rows > 0 ? $check_result->fetch_assoc()['role'] : null;
        $check_stmt->close();

        if ($user_role === 'super_admin' || ($user_role === 'admin' && !$is_authorized)) {
            $message = "You do not have permission to modify this account.";
            $message_type = "danger";
        } elseif ($user_role === null) {
            $message = "User not found.";
            $message_type = "danger";
        } else {
            $new_status = isset($_POST['activate']) ? 1 : 0;
            $update_sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $new_status, $toggle_id);
            if ($stmt->execute()) {
                $message = "User with ID $toggle_id " . ($new_status ? "activated" : "deactivated") . " successfully.";
                $message_type = "success";
            } else {
                $message = "Error updating user status: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
}

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id']) && $is_authorized) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id === $logged_in_user_id) {
        $message = "Cannot delete your own account.";
        $message_type = "danger";
    } else {
        $check_role_sql = "SELECT role FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_role_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $user_role = $check_result->num_rows > 0 ? $check_result->fetch_assoc()['role'] : null;
        $check_stmt->close();

        if ($user_role === 'super_admin' || ($user_role === 'admin' && !$is_authorized)) {
            $message = "You do not have permission to delete this account.";
            $message_type = "danger";
        } elseif ($user_role === null) {
            $message = "User not found.";
            $message_type = "danger";
        } else {
            $delete_sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message = "User with ID $delete_id deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Error deleting user: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch users, excluding the logged-in user
$sql = "SELECT id, username, email, status, created_at, profile_picture, role FROM users WHERE id != ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Fetch other admins (excluding the logged-in admin)
$other_admins_sql = "SELECT id, username, email, role, profile_picture FROM users WHERE role IN ('admin', 'super_admin') AND id != ? ORDER BY created_at DESC";
$other_admins_stmt = $conn->prepare($other_admins_sql);
$other_admins_stmt->bind_param("i", $logged_in_user_id);
$other_admins_stmt->execute();
$other_admins_result = $other_admins_stmt->get_result();
$other_admins_count = $other_admins_result->num_rows;
// Keep result open for later use in HTML

$get_profile_picture_url = static function ($filename) {
    if (!$filename) {
        return 'assets/img/default-profile.png';
    }

    $safe_filename = basename($filename);
    $admin_upload = __DIR__ . '/Uploads/' . $safe_filename;
    $url = is_file($admin_upload) ? 'Uploads/' : '../Uploads/';
    return $url . rawurlencode($safe_filename);
};

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Users - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing users">
    <meta name="keywords" content="admin, dashboard, manage users">
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
        .manage-users-section {
            padding: 4rem 0;
            background: #f3f4f6;
        }
        .table-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }
        .table-container h2, .table-container h3 {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-blue);
            font-size: 2.5rem;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }
        .table-container h2::after, .table-container h3::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, #0288d1, #03a9f4);
            border-radius: 2px;
        }
        .search-container {
            margin-bottom: 1.5rem;
        }
        .search-container input {
            max-width: 300px;
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        .search-container input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
        }
        .table {
            font-family: 'Poppins', sans-serif;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background: var(--dark-blue);
            color: white;
            font-weight: 500;
            padding: 1rem;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
            transition: background-color 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #f1faff;
        }
        .btn-activate {
            background: var(--success-green);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-activate:hover {
            background: #059669;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
            transform: translateY(-2px);
        }
        .btn-deactivate {
            background: var(--warning-orange);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-deactivate:hover {
            background: #d97706;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            transform: translateY(-2px);
        }
        .btn-delete {
            background: var(--danger-red);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            background: #dc2626;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        .action-buttons form {
            display: inline-block;
        }
        .action-buttons .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border-radius: 5px;
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary-blue);
            transition: transform 0.3s ease;
        }
        .profile-pic:hover {
            transform: scale(1.1);
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.5rem;
            border-radius: 5px;
        }
        .badge-super-admin {
            background-color: var(--danger-red);
            color: white;
        }
        .badge-admin {
            background-color: var(--primary-blue);
            color: white;
        }
        .badge-user {
            background-color: var(--gray-600);
            color: white;
        }
        .no-wrap {
            white-space: nowrap;
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
            .table-container {
                padding: 1.5rem;
            }
            .table-responsive {
                font-size: 0.9rem;
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
            .table-container {
                padding: 1rem;
            }
            .table-container h2, .table-container h3 {
                font-size: 1.8rem;
            }
            .search-container input {
                max-width: 100%;
            }
            .table-responsive {
                font-size: 0.85rem;
            }
            .table th, .table td {
                padding: 0.5rem;
            }
            .table th:nth-child(1), .table td:nth-child(1) {
                display: none;
            }
            .table th:nth-child(2) {
                white-space: nowrap; /* Prevent "Profile Picture" from wrapping in both tables */
            }
            .action-buttons .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                min-width: 70px;
            }
            .profile-pic {
                width: 30px;
                height: 30px;
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
        <section id="manage-users" class="manage-users-section section">
            <div class="container table-container" data-aos="fade-up">
                <?php if ($is_authorized): ?>
                    <!-- Display logged-in admin's role -->
                    <?php if ($logged_in_admin): ?>
                        <div class="alert alert-info text-center mb-4">
                            Welcome, <?php echo htmlspecialchars($logged_in_admin['username']); ?>! You are logged in as a <?php echo htmlspecialchars($logged_in_admin['role']); ?>.
                        </div>
                    <?php endif; ?>

                    <!-- Display success or error messages -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Search bar -->
                    <div class="search-container">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search users by username or email...">
                    </div>

                    <!-- Users Table -->
                    <h2 class="text-center mb-4">Manage Users</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Profile Picture</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr data-username="<?php echo htmlspecialchars(strtolower($row['username'])); ?>" 
                                        data-email="<?php echo htmlspecialchars(strtolower($row['email'])); ?>">
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($get_profile_picture_url($row['profile_picture']), ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="profile-pic">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $row['role'] === 'super_admin' ? 'super-admin' : ($row['role'] === 'admin' ? 'admin' : 'user'); ?>">
                                                <?php echo htmlspecialchars($row['role']); ?>
                                            </span>
                                        </td>
                                        <td class="no-wrap">
                                            <?php echo $row['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Inactive</span>'; ?>
                                        </td>
                                        <td class="no-wrap"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['created_at']))); ?></td>
                                        <td class="action-buttons">
                                            <?php if ($row['role'] !== 'super_admin' && ($logged_in_admin['role'] === 'super_admin' || $row['role'] !== 'admin')): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="toggle_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="<?php echo $row['status'] ? 'deactivate' : 'activate'; ?>" 
                                                            class="btn btn-sm <?php echo $row['status'] ? 'btn-deactivate' : 'btn-activate'; ?>">
                                                        <?php echo $row['status'] ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-delete" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#confirmDeleteModal" 
                                                        data-user-id="<?php echo $row['id']; ?>" 
                                                        data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Restricted</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Other Admins Section -->
                    <h3 class="text-center mt-5 mb-4">Other Admins (<?php echo $other_admins_count; ?>)</h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Profile Picture</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($admin = $other_admins_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($get_profile_picture_url($admin['profile_picture']), ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="profile-pic">
                                        </td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $admin['role'] === 'super_admin' ? 'super-admin' : 'admin'; ?>">
                                                <?php echo htmlspecialchars($admin['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php $other_admins_result->close(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        You do not have permission to manage users.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the user <strong id="deleteUsername"></strong>?
                </div>
                <div class="modal-footer">
                    <form method="post" id="deleteForm">
                        <input type="hidden" name="delete_id" id="deleteUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
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
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
    <script>
        AOS.init({ duration: 1000 });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tr');
            rows.forEach(row => {
                const username = row.getAttribute('data-username');
                const email = row.getAttribute('data-email');
                if (username.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Handle delete confirmation modal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-delete')) {
                const userId = e.target.getAttribute('data-user-id');
                const username = e.target.getAttribute('data-username');
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteUsername').textContent = username;
            }
        });

        // Mobile nav toggle
        document.querySelector('.mobile-nav-toggle').addEventListener('click', function() {
            const navMenu = document.querySelector('.navmenu ul');
            navMenu.classList.toggle('show');
        });

        // Dropdown toggle
        document.querySelectorAll('.toggle-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                const parentLi = this.closest('li');
                const dropdownMenu = parentLi.querySelector('ul');
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
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