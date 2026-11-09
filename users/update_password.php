<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../connect.php';
$user_id = $_SESSION['user_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    } elseif ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'New password and confirm password do not match.']);
        exit;
    } elseif (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters.']);
        exit;
    } else {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error updating password.']);
            }
            $update_stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit;
}

$conn->close();
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
    <title>Update Password - Y-Global System Solution</title>
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
    <link href="../assets/css/user-mobile.css?v=4" rel="stylesheet">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        }
        .main {
            flex: 1;
        }
        .header {
            background-color: #033f5a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .navmenu ul li a {
            color: white;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .navmenu ul li a:hover {
            color: #00c4ff;
        }
        .navmenu ul li a.active {
            color: #00c4ff;
            border-bottom: 2px solid #00c4ff;
        }
        .user-section {
            position: relative;
            overflow: hidden;
            min-height: calc(100vh - 78px);
            padding: clamp(2.5rem, 7vw, 5.5rem) 1rem;
            background:
                radial-gradient(circle at 12% 18%, rgba(35, 198, 218, 0.2), transparent 28rem),
                linear-gradient(135deg, #eff9fc 0%, #e8f1ff 100%);
        }
        .user-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(15, 70, 105, 0.08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(19, 62, 96, 0.15);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .password-layout {
            display: block;
            padding: clamp(2rem, 5vw, 3.5rem);
        }
        .password-form-panel {
            width: min(100%, 560px);
            margin: 0 auto;
        }
        .password-form-heading {
            margin-bottom: 1.7rem;
            text-align: center;
        }
        .password-form-heading h2 {
            margin: 0 0 0.5rem;
            color: #123952;
            font-size: clamp(1.45rem, 2.5vw, 1.9rem);
            font-weight: 750;
        }
        .password-form-heading p {
            margin: 0;
            color: #6b7c8e;
            font-size: 0.92rem;
        }
        .password-field {
            margin-bottom: 1.15rem;
            text-align: left;
        }
        .password-field label {
            margin-bottom: 0.5rem;
            color: #25445c;
            font-size: 0.86rem;
            font-weight: 650;
        }
        .password-input-wrap {
            position: relative;
        }
        .password-input-wrap .form-control {
            min-height: 54px;
            padding: 0.8rem 3.25rem 0.8rem 1rem;
            border: 1px solid #dbe5ed;
            border-radius: 14px;
            background: #f9fbfd;
            color: #17364b;
            transition: border-color 180ms ease, box-shadow 180ms ease, background 180ms ease;
        }
        .password-input-wrap .form-control:focus {
            border-color: #168db3;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(22, 141, 179, 0.12);
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            display: grid;
            width: 36px;
            height: 36px;
            transform: translateY(-50%);
            place-items: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #537087;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .password-toggle:hover,
        .password-toggle:focus-visible {
            outline: none;
            background: #eaf5f9;
            color: #08779a;
        }
        .password-submit {
            display: flex;
            min-height: 54px;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            margin-top: 0.45rem;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(110deg, #08779a, #0b9cc0);
            box-shadow: 0 10px 22px rgba(8, 119, 154, 0.22);
            font-weight: 700;
            transition: transform 180ms ease, box-shadow 180ms ease;
        }
        .password-submit:hover {
            transform: translateY(-2px);
            background: linear-gradient(110deg, #066783, #0784a5);
            box-shadow: 0 14px 26px rgba(8, 119, 154, 0.27);
        }
        .password-form-panel .alert {
            border-radius: 12px;
        }
        .footer {
            background-color: #033f5a;
            padding: 2rem 0;
        }
        .footer a {
            color: #00c4ff;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: #ffffff;
        }
        .footer h4 {
            color: white;
            font-weight: 600;
        }
        .footer p, .footer li {
            color: #d1d5db;
        }
        .logout-btn {
            background-color: red;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #cc0000;
        }
        @media (max-width: 576px) {
            .user-section {
                min-height: 0;
                padding: 1.25rem 0.65rem 2rem;
            }
            .user-container {
                margin: 0;
                border-radius: 22px;
            }
            .password-layout {
                padding: 1.5rem 1.25rem;
            }
            .password-form-panel {
                padding: 0;
            }
            .password-form-heading {
                margin-bottom: 1.25rem;
            }
        }
        @media (min-width: 577px) and (max-width: 767px) {
            .user-section {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body class="mobile-app-page user-portal-page">
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
                    <li><a href="messages.php">Messages</a></li>
                    <li><button class="logout-btn" onclick="confirmLogout()">Log Out</button></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/user-sidebar.php'; ?>
    <main class="main">
        <section class="user-section">
            <div class="container user-container" data-aos="fade-up">
                <div class="password-layout">
                    <div class="password-form-panel">
                        <div class="password-form-heading">
                            <h2>Update password</h2>
                            <p>Enter your current password, then choose a new one.</p>
                        </div>
                        <form method="post" action="" id="passwordForm" class="needs-validation" novalidate>
                            <div class="password-field">
                                <label for="current_password" class="form-label">Current password</label>
                                <div class="password-input-wrap">
                                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" placeholder="Enter your current password" required>
                                    <button type="button" class="bi bi-eye-slash password-toggle" id="toggleCurrent" aria-label="Show current password" aria-pressed="false"></button>
                                </div>
                                <div class="invalid-feedback">Please enter your current password.</div>
                            </div>
                            <div class="password-field">
                                <label for="new_password" class="form-label">New password</label>
                                <div class="password-input-wrap">
                                    <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="6" placeholder="At least 6 characters" required>
                                    <button type="button" class="bi bi-eye-slash password-toggle" id="toggleNew" aria-label="Show new password" aria-pressed="false"></button>
                                </div>
                                <div class="invalid-feedback">New password must be at least 6 characters.</div>
                            </div>
                            <div class="password-field">
                                <label for="confirm_password" class="form-label">Confirm new password</label>
                                <div class="password-input-wrap">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="6" placeholder="Enter your new password again" required>
                                    <button type="button" class="bi bi-eye-slash password-toggle" id="toggleConfirm" aria-label="Show confirmation password" aria-pressed="false"></button>
                                </div>
                                <div class="invalid-feedback">Please confirm your new password.</div>
                            </div>
                            <button type="submit" class="btn btn-primary password-submit w-100">
                                <i class="bi bi-shield-check" aria-hidden="true"></i><span>Update password</span>
                            </button>
                        </form>
                    </div>
                </div>
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
                        <li><a href="logout.php">Logout</a></li>
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

        AOS.init({ duration: 1000 });

        // Password visibility toggle
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            toggle.addEventListener('click', () => {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                toggle.classList.toggle('bi-eye');
                toggle.classList.toggle('bi-eye-slash');
                const isVisible = type === 'text';
                toggle.setAttribute('aria-pressed', String(isVisible));
                toggle.setAttribute('aria-label', `${isVisible ? 'Hide' : 'Show'} ${input.labels[0].textContent.toLowerCase()}`);
            });
        }

        togglePasswordVisibility('current_password', 'toggleCurrent');
        togglePasswordVisibility('new_password', 'toggleNew');
        togglePasswordVisibility('confirm_password', 'toggleConfirm');

        // Form submission with AJAX
        document.getElementById('passwordForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const formData = new FormData(this);
            formData.append('ajax', '1');

            // Client-side password validation
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            if (newPassword !== confirmPassword) {
                showAlert('danger', 'New password and confirm password do not match.');
                return;
            }
            if (newPassword.length < 6) {
                showAlert('danger', 'New password must be at least 6 characters.');
                return;
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    this.reset();
                } else {
                    showAlert('danger', result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An unexpected error occurred.');
            }
        });

        function showAlert(type, message) {
            document.querySelector('.alert')?.remove();
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            document.querySelector('.user-container').insertBefore(alertDiv, document.getElementById('passwordForm'));
            setTimeout(() => alertDiv.remove(), 3000);
        }

        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
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
    </script>
</body>
</html>