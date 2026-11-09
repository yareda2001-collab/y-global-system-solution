<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../connect.php';
$user_id = $_SESSION['user_id'];
$error = $success = '';

$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
        exit;
    } else {
        $check_sql = "SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            error_log("Could not prepare account settings uniqueness check: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Could not check your account details. Please try again.']);
        } else {
            $check_stmt->bind_param("ssi", $username, $email, $user_id);
            if (!$check_stmt->execute()) {
                error_log("Could not check account settings uniqueness: " . $check_stmt->error);
                echo json_encode(['success' => false, 'error' => 'Could not check your account details. Please try again.']);
            } else {
                $duplicate_username = false;
                $duplicate_email = false;
                $duplicates = $check_stmt->get_result();
                while ($duplicate = $duplicates->fetch_assoc()) {
                    $duplicate_username = $duplicate_username || strcasecmp($duplicate['username'], $username) === 0;
                    $duplicate_email = $duplicate_email || strcasecmp($duplicate['email'], $email) === 0;
                }

                if ($duplicate_username || $duplicate_email) {
                    if ($duplicate_username && $duplicate_email) {
                        $error = "That username and email are already in use. Please choose different details.";
                    } elseif ($duplicate_username) {
                        $error = "That username is already in use. Please choose a unique username.";
                    } else {
                        $error = "That email is already registered. Please use a different email.";
                    }
                    echo json_encode([
                        'success' => false,
                        'error' => $error,
                        'duplicate_username' => $duplicate_username,
                        'duplicate_email' => $duplicate_email,
                    ]);
                } else {
                    $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if (!$update_stmt) {
                        error_log("Could not prepare account settings update: " . $conn->error);
                        echo json_encode(['success' => false, 'error' => 'Could not update your settings. Please try again.']);
                    } else {
                        $update_stmt->bind_param("ssi", $username, $email, $user_id);
                        if ($update_stmt->execute()) {
                            $_SESSION['username'] = $username;
                            echo json_encode(['success' => true, 'message' => 'Your account details were updated successfully.']);
                        } else {
                            error_log("Could not update account settings: " . $update_stmt->error);
                            echo json_encode(['success' => false, 'error' => 'Could not update your settings. Please try again.']);
                        }
                        $update_stmt->close();
                    }
                }
            }
            $check_stmt->close();
        }
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
    <title>Settings - Y-Global System Solution</title>
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
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .main {
            flex: 1;
        }
        .header {
            background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .navmenu ul li a {
            color: white;
            font-weight: 500;
            padding: 10px 15px;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .navmenu ul li a:hover {
            color: #00c4ff;
            transform: translateY(-2px);
        }
        .navmenu ul li a.active {
            color: #00c4ff;
            border-bottom: 3px solid #00c4ff;
        }
        .user-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, #0288d1 0%, #03a9f4 100%);
            position: relative;
            overflow: hidden;
        }
        .user-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .user-container {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2), 0 0 20px rgba(2, 136, 209, 0.2);
            text-align: center;
            animation: fadeIn 0.5s ease-in;
            position: relative;
            z-index: 1;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .user-container h2 {
            font-weight: 600;
            color: #033f5a;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            position: relative;
            display: inline-block;
        }
        .user-container h2::after {
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
        .mb-3 label {
            font-weight: 500;
            color: #033f5a;
            font-size: 1.1rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #0288d1;
            box-shadow: 0 0 8px rgba(2, 136, 209, 0.3);
            background: #ffffff;
        }
        .form-control:hover {
            border-color: #0288d1;
        }
        .btn-primary {
            background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
            box-shadow: 0 0 15px rgba(2, 136, 209, 0.5);
            transform: translateY(-2px);
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }
        .btn-primary:hover::before {
            left: 100%;
        }
        .alert {
            margin-bottom: 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            padding: 0.75rem;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .footer {
            background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
            padding: 2rem 0;
        }
        .footer a {
            color: #00c4ff;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .footer a:hover {
            color: #ffffff;
            transform: translateY(-2px);
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
            .user-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            .user-container h2 {
                font-size: 1.8rem;
            }
            .form-control {
                padding: 0.5rem;
            }
            .btn-primary {
                padding: 0.5rem;
            }
            .navmenu ul li a {
                color: black;
            }
            .navmenu ul li .logout-btn {
                width: 30%;
                padding: 6px 12px;
                text-align: center;
                background-color: red;
                color: white;
            }
        }
        .user-section {
            min-height: calc(100vh - 78px);
            padding: clamp(2rem, 6vw, 4.5rem) 0;
            background:
                radial-gradient(circle at 12% 18%, rgba(35, 198, 218, 0.18), transparent 25rem),
                linear-gradient(135deg, #eff9fc 0%, #e8f1ff 100%);
        }
        .user-section::before {
            display: none;
        }
        .user-container {
            max-width: 760px;
            padding: clamp(1.5rem, 5vw, 3.25rem);
            border: 1px solid rgba(15, 70, 105, 0.08);
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(19, 62, 96, 0.14);
        }
        .settings-heading {
            margin-bottom: 2rem;
        }
        .settings-heading-icon {
            display: grid;
            width: 58px;
            height: 58px;
            margin: 0 auto 1rem;
            place-items: center;
            border: 1px solid rgba(8, 119, 154, 0.14);
            border-radius: 18px;
            background: linear-gradient(145deg, #e1f8fb, #eaf1ff);
            color: #08779a;
            font-size: 1.5rem;
        }
        .user-container .settings-heading h1 {
            margin: 0 0 0.6rem;
            color: #123952;
            font-size: clamp(1.7rem, 4vw, 2.25rem);
            font-weight: 750;
        }
        .user-container .settings-heading h1::after {
            display: none;
        }
        .settings-heading p {
            max-width: 32rem;
            margin: 0 auto;
            color: #6b7c8e;
            line-height: 1.65;
        }
        .settings-form {
            max-width: 520px;
            margin: 0 auto;
            text-align: left;
        }
        .settings-field {
            margin-bottom: 1.25rem;
        }
        .settings-field label {
            margin-bottom: 0.5rem;
            color: #25445c;
            font-size: 0.9rem;
            font-weight: 650;
        }
        .settings-input-wrap {
            position: relative;
        }
        .settings-input-wrap > i {
            position: absolute;
            top: 50%;
            left: 1rem;
            z-index: 2;
            transform: translateY(-50%);
            color: #6a8498;
            font-size: 1.05rem;
            pointer-events: none;
        }
        .settings-input-wrap .form-control {
            min-height: 54px;
            padding: 0.8rem 1rem 0.8rem 2.9rem;
            border: 1px solid #dbe5ed;
            border-radius: 14px;
            background: #f9fbfd;
            color: #17364b;
        }
        .settings-input-wrap .form-control:focus {
            border-color: #168db3;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(22, 141, 179, 0.12);
        }
        .settings-input-wrap .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }
        .settings-field .invalid-feedback {
            margin-top: 0.4rem;
            font-size: 0.8rem;
        }
        .settings-submit {
            display: flex;
            min-height: 54px;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            margin-top: 0.5rem;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(110deg, #08779a, #0b9cc0);
            box-shadow: 0 10px 22px rgba(8, 119, 154, 0.2);
            font-weight: 700;
            transition: transform 180ms ease, box-shadow 180ms ease;
        }
        .settings-submit:hover {
            transform: translateY(-2px);
            background: linear-gradient(110deg, #066783, #0784a5);
            box-shadow: 0 14px 26px rgba(8, 119, 154, 0.25);
        }
        @media (max-width: 576px) {
            .user-section {
                min-height: 0;
                padding: 1.5rem 0.65rem 2rem;
            }
            .user-container {
                margin: 0;
                padding: 1.5rem 1.15rem;
                border-radius: 22px;
            }
            .settings-heading {
                margin-bottom: 1.5rem;
            }
            .settings-heading-icon {
                width: 50px;
                height: 50px;
                border-radius: 16px;
                font-size: 1.3rem;
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
                <div class="settings-heading">
                    <span class="settings-heading-icon"><i class="bi bi-person-gear" aria-hidden="true"></i></span>
                    <h1>Account settings</h1>
                    <p>Keep your profile details up to date so people can recognize and contact you.</p>
                </div>
                <form method="post" action="" id="settingsForm" class="settings-form needs-validation" novalidate>
                    <div class="settings-field">
                        <label for="username" class="form-label">Username</label>
                        <div class="settings-input-wrap">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" class="form-control" id="username" name="username" autocomplete="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="invalid-feedback">Please enter a unique username.</div>
                    </div>
                    <div class="settings-field">
                        <label for="email" class="form-label">Email</label>
                        <div class="settings-input-wrap">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address that is not already registered.</div>
                    </div>
                    <button type="submit" class="btn btn-primary settings-submit w-100">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i><span>Save account details</span>
                    </button>
                </form>
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

        document.getElementById('settingsForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            [usernameInput, emailInput].forEach(input => {
                input.classList.remove('is-invalid');
                input.setCustomValidity('');
                const feedback = input.closest('.settings-field').querySelector('.invalid-feedback');
                feedback.style.display = 'none';
            });
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                [usernameInput, emailInput].forEach(input => {
                    if (!input.validity.valid) {
                        input.classList.add('is-invalid');
                        input.closest('.settings-field').querySelector('.invalid-feedback').style.display = 'block';
                    }
                });
                return;
            }

            const formData = new FormData(this);
            formData.append('ajax', '1');

            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('danger', 'Invalid email format.');
                return;
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    this.classList.remove('was-validated');
                    showAlert('success', result.message);
                } else {
                    if (result.duplicate_username) {
                        usernameInput.classList.add('is-invalid');
                        const feedback = usernameInput.closest('.settings-field').querySelector('.invalid-feedback');
                        feedback.textContent = 'That username is already in use. Please choose another.';
                        feedback.style.display = 'block';
                    }
                    if (result.duplicate_email) {
                        emailInput.classList.add('is-invalid');
                        const feedback = emailInput.closest('.settings-field').querySelector('.invalid-feedback');
                        feedback.textContent = 'That email is already registered. Please use another email.';
                        feedback.style.display = 'block';
                    }
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
            document.querySelector('.user-container').insertBefore(alertDiv, document.getElementById('settingsForm'));
            setTimeout(() => alertDiv.remove(), 3000);
        }

        document.querySelectorAll('.settings-field .form-control').forEach(input => {
            input.addEventListener('input', () => {
                input.classList.remove('is-invalid');
                const feedback = input.closest('.settings-field').querySelector('.invalid-feedback');
                feedback.style.display = 'none';
            });
        });

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