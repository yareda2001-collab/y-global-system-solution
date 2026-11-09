<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

// Handle permission updates
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_super_admin) {
    $admin_id = intval($_POST['admin_id']);
    $can_create_admins = isset($_POST['can_create_admins']) ? 1 : 0;
    $can_reset_passwords = isset($_POST['can_reset_passwords']) ? 1 : 0;
    $can_manage_users = isset($_POST['can_manage_users']) ? 1 : 0;

    // Check if admin_id exists and is not a super admin
    $check_sql = "SELECT role FROM users WHERE id = ? AND role != 'super_admin'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        // Upsert admin rights
        $sql = "INSERT INTO admin_rights (admin_id, can_create_admins, can_reset_passwords, can_manage_users) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                can_create_admins = ?, can_reset_passwords = ?, can_manage_users = ?, updated_at = CURRENT_TIMESTAMP";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiiii", $admin_id, $can_create_admins, $can_reset_passwords, $can_manage_users, $can_create_admins, $can_reset_passwords, $can_manage_users);
        if ($stmt->execute()) {
            $message = "Permissions updated successfully.";
            $message_type = "success";
        } else {
            $message = "Error updating permissions: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Invalid admin ID or super admin cannot be modified.";
        $message_type = "error";
    }
    $check_stmt->close();
}

// Fetch all admins (excluding super admins) with their rights
$admins_sql = "SELECT u.id, u.username, u.email, u.role, ar.can_create_admins, ar.can_reset_passwords, ar.can_manage_users 
               FROM users u 
               LEFT JOIN admin_rights ar ON u.id = ar.admin_id 
               WHERE u.role = 'admin'";
$admins_result = $conn->query($admins_sql);
if (!$admins_result) {
    $message = "Error fetching admins: " . $conn->error;
    $message_type = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Admin Rights - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing admin rights">
    <meta name="keywords" content="admin, dashboard, admin rights, manage permissions">
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
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 1rem;
        }
        .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--success-green);
            border-color: var(--success-green);
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
        /* Prevent wrapping for specific headers */
        .table th:nth-child(3),
        .table th:nth-child(4),
        .table th:nth-child(5) {
            white-space: nowrap;
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
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px;
            }
            .dashboard-container {
                margin: 1rem;
                padding: 1rem;
            }
            .logo h3 {
                font-size: 1.2rem;
            }
            .header-container {
                padding: 0.5rem;
            }
            .form-check-input {
                width: 2em;
                height: 1em;
            }
            .search-container input {
                max-width: 100%;
            }
            .table th, .table td {
                padding: 0.4rem;
                font-size: 0.85rem;
            }
            .table th:nth-child(3),
            .table th:nth-child(4),
            .table th:nth-child(5) {
                font-size: 0.75rem;
            }
            .table {
                min-width: 600px;
            }
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
            .table th, .table td {
                padding: 0.3rem;
                font-size: 0.8rem;
            }
            .table th:nth-child(3),
            .table th:nth-child(4),
            .table th:nth-child(5) {
                font-size: 0.7rem;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
</head>
<body class="dashboard-page admin-app-page">
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
            <?php if ($is_super_admin): ?>
                <h2 class="text-center mb-4" style="color: #1e3a8a;">Manage Admin Rights</h2>
                <?php if ($message): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: '<?php echo $message_type; ?>',
                                title: '<?php echo $message_type === "success" ? "Success" : "Error"; ?>',
                                text: '<?php echo addslashes($message); ?>',
                                confirmButtonColor: '#1e3a8a',
                                confirmButtonText: 'OK'
                            });
                        });
                    </script>
                <?php endif; ?>
                <!-- Search Container -->
                <div class="search-container">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by username or email..." aria-label="Search">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Create / Manage Admins</th>
                                <th>Reset Passwords</th>
                                <th>Manage Users</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="adminTable">
                            <?php if ($admins_result && $admins_result->num_rows > 0): ?>
                                <?php while ($admin = $admins_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="can_create_admins" form="form-<?php echo $admin['id']; ?>" <?php echo $admin['can_create_admins'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="can_reset_passwords" form="form-<?php echo $admin['id']; ?>" <?php echo $admin['can_reset_passwords'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="can_manage_users" form="form-<?php echo $admin['id']; ?>" <?php echo $admin['can_manage_users'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td>
                                            <form id="form-<?php echo $admin['id']; ?>" method="post" action="">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" style="background: #1e3a8a; border-color: #1e3a8a;">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No admins found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: 'This page is only accessible to super admins.',
                            confirmButtonColor: '#1e3a8a',
                            confirmButtonText: 'OK'
                        });
                    });
                </script>
            <?php endif; ?>
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

        // Auto Search Functionality
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('#adminTable tr');

            searchInput.addEventListener('input', function () {
                const searchValue = this.value.toLowerCase().trim();

                tableRows.forEach(row => {
                    const username = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    if (searchValue === '' || username.includes(searchValue) || email.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show "No admins found" row if no matches
                const noAdminsRow = document.querySelector('#adminTable tr td[colspan="6"][class="text-center"]');
                if (noAdminsRow) {
                    noAdminsRow.parentElement.style.display = searchValue === '' || [...tableRows].some(row => row.style.display !== 'none') ? '' : '';
                }
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