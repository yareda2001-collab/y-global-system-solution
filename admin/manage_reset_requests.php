<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connect.php';

// Initialize message variables
$message = null;
$message_type = null;

// Fetch logged-in admin's details and permissions
$logged_in_user_id = intval($_SESSION['user_id']);
$admin_sql = "SELECT u.username, u.role, ar.can_reset_passwords 
              FROM users u 
              LEFT JOIN admin_rights ar ON u.id = ar.admin_id 
              WHERE u.id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $logged_in_user_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
$is_authorized = $logged_in_admin && ($logged_in_admin['role'] === 'super_admin' || $logged_in_admin['can_reset_passwords'] == 1);
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

// Handle OTP generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && $is_authorized) {
    $request_id = intval($_POST['request_id']);
    $otp = sprintf("%06d", mt_rand(100000, 999999)); // 6-digit OTP
    $expires_at = date('Y-m-d H:i:s', strtotime('+4 hours'));

    // Fetch user, token, and role
    $sql = "SELECT u.username, u.email, u.role, pr.token FROM users u JOIN password_reset_requests pr ON u.id = pr.user_id WHERE pr.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $email = $user['email'];
        $role = $user['role'];
        $token = $user['token'];

        // Determine the reset link dynamically
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $domain = 'yared.infinityfreeapp.com'; // Force production domain
        $base_url = ($role === 'admin' || $role === 'super_admin') 
            ? "$protocol://$domain/admin/forgot_password.php" 
            : "$protocol://$domain/forgot_password.php";
        $reset_link = $base_url . "?step=otp&email=" . urlencode($email) . "&token=" . urlencode($token);

        // Update request with OTP and status
        $sql = "UPDATE password_reset_requests SET otp = ?, otp_expires_at = ?, status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $otp, $expires_at, $request_id);
        if ($stmt->execute()) {
            $message = "OTP generated successfully. Copy the message below and send it to the user's email.";
            $message_type = "success";
            // Generate HTML OTP message for display (OTP and link hidden)
            $otp_message = "
                <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;'>
                    <div style='background-color: #1e3a8a; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h2 style='margin: 0; font-size: 24px;'>Y-Global System Solution</h2>
                    </div>
                    <div style='padding: 20px; background-color: #fff; border-radius: 0 0 8px 8px;'>
                        <p>Dear " . htmlspecialchars($username) . ",</p>
                        <p>Use the following One-Time Password (OTP) to reset your password:</p>
                        <div id='otp' style='font-size: 28px; font-weight: bold; color: #1e3a8a; text-align: center; margin: 20px 0; letter-spacing: 2px;'>[Hidden OTP]</div>
                        <p>Click the link below to enter the OTP:</p>
                        <p>[Hidden Link]</p>
                        <p>This OTP is valid for 4 hours. If you did not request a password reset, please contact us at <a href='mailto:yared1357@gmail.com'>yared1357@gmail.com</a>.</p>
                    </div>
                    <div style='text-align: center; font-size: 12px; color: #666; padding: 20px; border-top: 1px solid #e0e0e0;'>
                        <p>Copyright © " . date("Y") . " Y-Global System Solution. All Rights Reserved.</p>
                        <p>Designed by Yared Alebachewu</p>
                    </div>
                </div>
            ";
            // Generate plain text OTP message for copying (includes actual OTP and link)
            $otp_message_plain = "Dear {$username},\n\nUse this One-Time Password (OTP) to reset your password: {$otp}\n\nClick here: {$reset_link}\n\nThis OTP is valid for 4 hours. If you did not request a password reset, please contact us at yared1357@gmail.com.\n\nCopyright © " . date("Y") . " Y-Global System Solution. All Rights Reserved.\nDesigned by Yared Alebachewu";
            error_log("OTP generated: email=$email, role=$role, token=$token, otp=$otp, link=$reset_link");
        } else {
            $message = "Error generating OTP: " . $stmt->error;
            $message_type = "danger";
            error_log("Error generating OTP: " . $stmt->error);
        }
    } else {
        $message = "Invalid request ID.";
        $message_type = "danger";
        error_log("Invalid request ID: $request_id");
    }
    $stmt->close();
}

// Fetch pending requests with role
$sql = "SELECT pr.id, pr.email, pr.requested_at, u.username, u.role FROM password_reset_requests pr JOIN users u ON pr.user_id = u.id WHERE pr.status = 'pending'";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manage Password Reset Requests - Y-Global System Solution</title>
    <meta name="description" content="Admin dashboard for managing password reset requests">
    <meta name="keywords" content="admin, dashboard, password reset, manage requests">
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
        .reset-requests-section {
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
        }
        .table-container h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-blue);
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
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
        }
        .table tbody tr:hover {
            background-color: #f1faff;
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
        .otp-message {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .btn-copy {
            background: var(--dark-blue);
            color: white;
            transition: background-color 0.3s ease;
        }
        .btn-copy:hover {
            background: #025c8a;
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
                padding: 1rem;
            }
            .table-container h2 {
                font-size: 1.8rem;
            }
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            .otp-message {
                font-size: 0.85rem;
            }
            .logo h3 {
                font-size: 1.2rem;
            }
            .header-container {
                padding: 0.5rem;
            }
            .table th:nth-child(4),
            .table td:nth-child(4),
            .table th:nth-child(5),
            .table td:nth-child(5) {
                white-space: nowrap;
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
        <section id="reset-requests" class="reset-requests-section section">
            <div class="container table-container" data-aos="fade-up">
                <h2>Manage Password Reset Requests</h2>
                <?php if ($is_authorized): ?>
                    <?php if ($message !== null): ?>
                        <div class="alert alert-<?php echo $message_type; ?> text-center mb-4">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($otp_message)): ?>
                        <div class="otp-message">
                            <p class="text-sm mb-2 italic">Note: The OTP and reset link are hidden for security. Use 'Copy Message' to copy the full message, including the OTP and link, for emailing.</p>
                            <div id="otpMessage"><?php echo $otp_message; ?></div>
                            <span id="otpMessagePlain" style="display: none;"><?php echo htmlspecialchars($otp_message_plain); ?></span>
                            <div class="mt-2">
                                <button id="copyButton" class="btn btn-copy">Copy Message</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Requested At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['requested_at']); ?></td>
                                            <td>
                                                <form method="post" action="">
                                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">Generate OTP</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No pending requests.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        You do not have permission to manage reset requests.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

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

    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Access Denied</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You do not have permission to manage reset requests.
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

        // Show message with SweetAlert2 if there's a message
        <?php if ($message !== null): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
                    title: '<?php echo $message_type === "success" ? "Success" : "Error"; ?>',
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
                    text: 'You do not have permission to manage reset requests.',
                    confirmButtonColor: '#1e3a8a'
                });
            });
        <?php endif; ?>

        // Copy OTP message
        function copyPlainText() {
            const text = document.getElementById('otpMessagePlain').textContent;
            console.log('Attempting to copy message:', text.substring(0, 50) + '...');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.write(text).then(() => {
                    showFeedback('Message copied to clipboard!', 'success');
                    console.log('Clipboard API: Message copy successful');
                }).catch(err => {
                    console.error('Clipboard API failed:', err);
                    tryFallbackCopy(text);
                });
            } else {
                console.warn('Clipboard API unavailable or not in secure context, using fallback');
                tryFallbackCopy(text);
            }
        }

        // Fallback copy method
        function tryFallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showFeedback('Message copied to clipboard!', 'success');
                console.log('Fallback: Message copy successful');
            } catch (err) {
                console.error('Fallback copy failed:', err);
                showFeedback('Failed to copy message.', 'error');
            }
            document.body.removeChild(textArea);
        }

        // Show feedback with SweetAlert2
        function showFeedback(message, type) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success' : 'Error',
                text: message,
                confirmButtonColor: '#1e3a8a'
            });
        }

        // Initialize copy button event listener
        document.addEventListener('DOMContentLoaded', function () {
            const copyButton = document.getElementById('copyButton');
            if (copyButton) {
                copyButton.addEventListener('click', copyPlainText);
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