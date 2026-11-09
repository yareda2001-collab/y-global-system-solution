<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../connect.php';
$user_id = $_SESSION['user_id'];
$error = $success = '';
$sql = "SELECT username, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$current_picture = $user ? ($user['profile_picture'] ?? '') : '';
$stmt->close();
$conn->close();

$safe_picture = basename($current_picture);
$admin_picture_path = __DIR__ . '/../admin/Uploads/' . $safe_picture;
$legacy_picture_path = __DIR__ . '/../Uploads/' . $safe_picture;
if ($safe_picture && is_file($admin_picture_path)) {
    $profile_picture_url = '../admin/Uploads/' . rawurlencode($safe_picture);
} elseif ($safe_picture && is_file($legacy_picture_path)) {
    $profile_picture_url = '../Uploads/' . rawurlencode($safe_picture);
} else {
    $profile_picture_url = '../assets/img/default-profile.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#033f5a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="../manifest.php?v=6">
    <link rel="apple-touch-icon" href="../assets/img/pwa-192.png">
    <title>User Dashboard - Y-Global System Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="../assets/img/favicon1.png" rel="icon">
    <link href="../assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/user-sidebar.css?v=9" rel="stylesheet">
    <link href="../assets/css/user-footer.css?v=3" rel="stylesheet">
    <link href="../assets/css/user-dashboard.css?v=2" rel="stylesheet">
    <link href="../assets/css/user-mobile.css?v=4" rel="stylesheet">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="mobile-app-page user-portal-page user-dashboard-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">
            <a href="dashboard.php" class="logo d-flex align-items-center me-auto">
                <span class="user-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="user-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="update_password.php">Update Password</a></li>
                    <li><a href="update_profile_picture.php">Update Profile Picture</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <li><button class="logout-btn" onclick="confirmLogout()">Log Out</button></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/user-sidebar.php'; ?>
    <main class="main">
        <section class="user-section">
            <div class="container user-container user-dashboard" data-aos="fade-up">
                <section class="dashboard-welcome" aria-labelledby="dashboardWelcomeTitle">
                    <div class="dashboard-welcome-copy">
                        <span class="dashboard-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> YOUR ACCOUNT</span>
                        <h1 id="dashboardWelcomeTitle">Welcome back,<br><span><?php echo htmlspecialchars($user['username']); ?></span></h1>
                        <p>Your personal space to manage your profile, preferences, and conversations.</p>
                        <a class="dashboard-primary-action" href="settings.php">Manage account <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dashboard-profile-wrap">
                        <span class="dashboard-profile-label">Your profile</span>
                        <?php if ($current_picture && $profile_picture_url !== '../assets/img/default-profile.png'): ?>
                            <img src="<?php echo htmlspecialchars($profile_picture_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="profile-pic-preview">
                        <?php else: ?>
                            <div class="profile-pic-alt" aria-label="No profile picture uploaded">
                                <i class="bi bi-person" aria-hidden="true"></i>
                            </div>
                        <?php endif; ?>
                        <a href="update_profile_picture.php">Edit photo <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                </section>
                <section class="dashboard-shortcuts" aria-labelledby="dashboardShortcutsTitle">
                    <div class="dashboard-section-heading">
                        <div>
                            <span class="dashboard-section-kicker">QUICK ACCESS</span>
                            <h2 id="dashboardShortcutsTitle">Your account</h2>
                        </div>
                        <p>Everything you need, one click away.</p>
                    </div>
                    <div class="nav-links">
                        <a href="messages.php" class="dashboard-action dashboard-action-messages">
                            <span class="dashboard-action-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                            <span class="dashboard-action-copy"><strong>Messages</strong><small>View your conversations</small></span>
                            <i class="bi bi-arrow-up-right dashboard-action-arrow" aria-hidden="true"></i>
                        </a>
                        <a href="settings.php" class="dashboard-action dashboard-action-settings">
                            <span class="dashboard-action-icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
                            <span class="dashboard-action-copy"><strong>Settings</strong><small>Update your account details</small></span>
                            <i class="bi bi-arrow-up-right dashboard-action-arrow" aria-hidden="true"></i>
                        </a>
                        <a href="update_password.php" class="dashboard-action dashboard-action-security">
                            <span class="dashboard-action-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                            <span class="dashboard-action-copy"><strong>Password &amp; security</strong><small>Keep your account protected</small></span>
                            <i class="bi bi-arrow-up-right dashboard-action-arrow" aria-hidden="true"></i>
                        </a>
                        <a href="update_profile_picture.php" class="dashboard-action dashboard-action-profile">
                            <span class="dashboard-action-icon"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></span>
                            <span class="dashboard-action-copy"><strong>Profile picture</strong><small>Make your profile yours</small></span>
                            <i class="bi bi-arrow-up-right dashboard-action-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                </section>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/user-mobile-nav.php'; ?>
    <footer id="footer" class="footer dark-background">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6 footer-about">
                    <a href="dashboard.php" class="logo d-flex align-items-center">
                        <span class="sitename">Y-Global System Solution</span>
                    </a>
                    <div class="footer-contact pt-3">
                        <p>Ethiopia, AddisAbaba</p>
                        <p class="mt-3"><strong>Phone:</strong> <span>+251 926-862-255</span></p>
                        <p><strong>Email:</strong> <span>yared1357@gmail.com</span></p>
                    </div>
                    <div class="social-links d-flex mt-4">
                        <a href="https://www.facebook.com/profile.php?id=100057756135221"><i class="bi bi-facebook"></i></a>
                        <a href="mailto:yared1357@gmail.com"><i class="bi bi-envelope"></i></a>
                        <a href="https://www.linkedin.com/in/yared-alebachewu-84b20b252/"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="update_password.php">Update Password</a></li>
                        <li><a href="update_profile_picture.php">Update Profile Picture</a></li>
                        <li><a href="settings.php">Settings</a></li>
                        <li><a href="messages.php">Messages</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Our Services</h4>
                    <ul>
                        <li>Website Designing</li>
                        <li>Graphics Designing</li>
                        <li>Video Editing</li>
                        <li>Logo Designing</li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12 footer-newsletter">
                    <h4>From Developer</h4>
                    <p>I am always ready to work with you and to satisfy your needs. We are very dedicated for our customers orders and we work hard to create everything as our customers need.</p>
                    <p>Working with us always makes happier and very satisfied and makes your organization and profession very competitive internationally.</p>
                </div>
            </div>
        </div>
        <div class="container copyright text-center mt-4">
            <p><span>Copyright© <?php echo date("Y"); ?></span> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/aos/aos.js"></script>
    <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="../assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="../assets/vendor/php-email-form/validate.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/user-sidebar.js?v=3"></script>
    <script src="../assets/js/pwa-install.js?v=2"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
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
</body>
</html>