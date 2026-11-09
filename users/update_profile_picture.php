<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../connect.php';
$user_id = $_SESSION['user_id'];
$error = $success = '';
$sql = "SELECT profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_picture = $stmt->get_result()->fetch_assoc()['profile_picture'] ?? '';
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $upload = $_FILES['profile_picture'] ?? null;
    if ($upload && $upload['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];
        $max_size = 5 * 1024 * 1024;
        $file_ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $file_info->file($upload['tmp_name']);
        if (!isset($allowed_types[$file_ext]) || $allowed_types[$file_ext] !== $file_type) {
            echo json_encode(['success' => false, 'error' => 'Only JPEG, PNG, or GIF files are allowed.']);
            exit;
        } elseif ($upload['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'File size must be less than 5MB.']);
            exit;
        } else {
            $upload_dir = dirname(__DIR__) . '/admin/Uploads/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                echo json_encode(['success' => false, 'error' => 'The profile picture upload folder could not be created.']);
                exit;
            }
            if (!is_writable($upload_dir)) {
                error_log("Profile picture upload folder is not writable: $upload_dir");
                echo json_encode(['success' => false, 'error' => 'The profile picture upload folder is not writable.']);
                exit;
            }
            $new_filename = 'user_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($upload['tmp_name'], $destination)) {
                $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $new_filename, $user_id);
                }
                if ($update_stmt && $update_stmt->execute()) {
                    $old_filename = basename($current_picture);
                    if ($old_filename && is_file($upload_dir . $old_filename)) {
                        unlink($upload_dir . $old_filename);
                    }
                    echo json_encode(['success' => true, 'new_path' => '../admin/Uploads/' . rawurlencode($new_filename)]);
                } else {
                    error_log("Could not update user profile picture: " . ($update_stmt ? $update_stmt->error : $conn->error));
                    echo json_encode(['success' => false, 'error' => 'Could not save your profile picture. Please try again.']);
                    if (is_file($destination)) {
                        unlink($destination);
                    }
                }
                if ($update_stmt) {
                    $update_stmt->close();
                }
            } else {
                error_log("Could not move uploaded user profile picture to $destination");
                echo json_encode(['success' => false, 'error' => 'Could not save the uploaded image. Check the upload folder permissions and try again.']);
            }
        }
    } else {
        $upload_error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = match ($upload_error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected image is too large to upload.',
            UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please select a file to upload or capture a photo.',
            default => 'The image could not be uploaded. Please try again.',
        };
        echo json_encode(['success' => false, 'error' => $error_message]);
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
    <title>Update Profile Picture - Y-Global System Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="../assets/img/favicon1.png" rel="icon">
    <link href="../assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/user-sidebar.css?v=8" rel="stylesheet">
    <link href="../assets/css/user-footer.css?v=1" rel="stylesheet">
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
            display: grid;
            min-height: calc(100vh - 78px);
            padding: clamp(2rem, 6vw, 4.5rem) 1rem;
            place-items: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 12% 18%, rgba(35, 198, 218, 0.18), transparent 25rem),
                linear-gradient(135deg, #eff9fc 0%, #e8f1ff 100%);
        }
        .user-container {
            max-width: 760px;
            margin: 0 auto;
            padding: clamp(1.5rem, 5vw, 3.25rem);
            border: 1px solid rgba(15, 70, 105, 0.08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(19, 62, 96, 0.14);
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-heading {
            margin-bottom: 2rem;
        }
        .profile-heading-icon {
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
        .user-container .profile-heading h1 {
            margin: 0 0 0.6rem;
            color: #123952;
            font-size: clamp(1.7rem, 4vw, 2.25rem);
            font-weight: 750;
        }
        .profile-heading p {
            max-width: 34rem;
            margin: 0 auto;
            color: #6b7c8e;
            line-height: 1.65;
        }
        #profile-pic-container {
            position: relative;
            width: 176px;
            height: 176px;
            margin: 0 auto 1.5rem;
            padding: 6px;
            border-radius: 50%;
            background: linear-gradient(140deg, #16b8c5, #2879c7);
            box-shadow: 0 12px 30px rgba(24, 132, 171, 0.23);
        }
        #profile-pic-container::after {
            position: absolute;
            right: 7px;
            bottom: 8px;
            width: 22px;
            height: 22px;
            border: 4px solid #fff;
            border-radius: 50%;
            background: #29b77a;
            content: "";
        }
        .profile-pic-preview {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 4px solid #fff;
            border-radius: 50%;
            background: #eaf3f7;
        }
        .profile-form {
            max-width: 500px;
            margin: 0 auto;
            text-align: left;
        }
        .profile-form-label {
            display: block;
            margin-bottom: 0.65rem;
            color: #25445c;
            font-size: 0.9rem;
            font-weight: 650;
            text-align: center;
        }
        .profile-upload-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
        }
        .custom-file-upload, .capture-photo {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            margin: 0;
            padding: 0.7rem 1.1rem;
            border: 1px solid #d6e5ec;
            border-radius: 14px;
            background: #fff;
            color: #164762;
            font: 600 0.9rem 'Poppins', sans-serif;
            cursor: pointer;
            transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .custom-file-upload:hover, .capture-photo:hover {
            transform: translateY(-2px);
            border-color: #1596ad;
            background: #f0fbfd;
            box-shadow: 0 8px 18px rgba(16, 104, 133, 0.1);
        }
        #profile_picture:focus-visible + .profile-upload-actions .custom-file-upload {
            outline: 3px solid rgba(21, 150, 173, 0.35);
            outline-offset: 3px;
        }
        .btn-primary {
            display: inline-flex;
            min-height: 52px;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            margin-top: 0.65rem;
            border-radius: 14px;
            background: linear-gradient(110deg, #087f9d, #126ac0);
            border: none;
            font-weight: 650;
            box-shadow: 0 9px 20px rgba(10, 112, 156, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            background: linear-gradient(110deg, #066c87, #105ba6);
            box-shadow: 0 12px 24px rgba(10, 112, 156, 0.26);
        }
        .alert {
            margin-bottom: 1.25rem;
            border-radius: 14px;
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
        .video-container {
            display: none;
            width: min(100%, 360px);
            margin: 1rem auto;
            overflow: hidden;
            border: 1px solid #d9e8ef;
            border-radius: 20px;
            background: #102b3b;
            text-align: center;
        }
        video {
            display: block;
            width: 100%;
            max-width: 100%;
            max-height: 360px;
            object-fit: cover;
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
                min-height: auto;
                padding: 1.75rem 0.75rem;
            }
            .user-container {
                width: 100%;
                padding: 1.5rem 1.15rem;
                border-radius: 22px;
            }
            .profile-pic-preview {
                width: 100%;
                height: 100%;
            }
            #profile-pic-container {
                width: 150px;
                height: 150px;
                margin-bottom: 1.25rem;
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
            .profile-upload-actions {
                gap: 0.55rem;
            }
            .custom-file-upload, .capture-photo {
                flex: 1 1 145px;
                padding-inline: 0.7rem;
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
                <div class="profile-heading">
                    <span class="profile-heading-icon"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                    <h1>Update profile picture</h1>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <div id="profile-pic-container">
                    <?php if ($current_picture): ?>
                        <?php
                        $safe_picture = basename($current_picture);
                        $picture_url = is_file(__DIR__ . '/../admin/Uploads/' . $safe_picture)
                            ? '../admin/Uploads/' . rawurlencode($safe_picture)
                            : '../Uploads/' . rawurlencode($safe_picture);
                        ?>
                        <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Current Profile Picture" class="profile-pic-preview" id="profilePic">
                    <?php else: ?>
                        <img src="../assets/img/default-profile.png" alt="Default Profile Picture" class="profile-pic-preview" id="profilePic">
                    <?php endif; ?>
                </div>
                <form method="post" action="" enctype="multipart/form-data" id="profileForm" class="profile-form needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label profile-form-label">Choose how to add your photo</label>
                        <div class="video-container">
                            <video id="video" autoplay playsinline></video>
                        </div>
                        <input type="file" class="form-control visually-hidden" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <div class="profile-upload-actions">
                            <label for="profile_picture" class="custom-file-upload"><i class="bi bi-image" aria-hidden="true"></i> Choose a photo</label>
                            <button type="button" class="capture-photo" id="captureButton"><i class="bi bi-camera" aria-hidden="true"></i> Take a photo</button>
                        </div>
                        <div class="invalid-feedback">Please select an image file or capture a photo.</div>
                        <p class="text-center text-secondary small mb-0">JPEG, PNG, or GIF · Maximum file size 5 MB</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i> Save profile picture</button>
                    <canvas id="canvas" style="display: none;"></canvas>
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

        // Form submission with AJAX
        document.getElementById('profileForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const formData = new FormData(this);
            if (capturedPhoto) {
                formData.set('profile_picture', capturedPhoto, capturedPhoto.name);
            }
            formData.append('ajax', '1');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    const profilePic = document.getElementById('profilePic');
                    profilePic.src = result.new_path + '?t=' + new Date().getTime();
                    profilePictureInput.value = '';
                    capturedPhoto = null;
                    document.querySelector('.alert-success')?.remove();
                    const successDiv = document.createElement('div');
                    successDiv.className = 'alert alert-success';
                    successDiv.textContent = 'Profile picture updated successfully.';
                    document.querySelector('.user-container').insertBefore(successDiv, document.getElementById('profile-pic-container'));
                    setTimeout(() => successDiv.remove(), 3000);
                } else {
                    document.querySelector('.alert-danger')?.remove();
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.textContent = result.error;
                    document.querySelector('.user-container').insertBefore(errorDiv, document.getElementById('profile-pic-container'));
                    setTimeout(() => errorDiv.remove(), 3000);
                }
            } catch (error) {
                console.error('Error:', error);
                document.querySelector('.alert-danger')?.remove();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = 'An unexpected error occurred.';
                document.querySelector('.user-container').insertBefore(errorDiv, document.getElementById('profile-pic-container'));
                setTimeout(() => errorDiv.remove(), 3000);
            }
        });

        // Client-side form validation
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

        // Profile picture handling (file upload and webcam capture)
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const profilePictureInput = document.getElementById('profile_picture');
        const profilePicturePreview = document.getElementById('profilePic');
        const captureButton = document.getElementById('captureButton');
        let stream = null;
        let capturedPhoto = null;

        // File upload preview
        profilePictureInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                capturedPhoto = null;
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
                    await video.play();
                    video.parentElement.style.display = 'block';
                    captureButton.textContent = 'Capture Photo';
                } catch (error) {
                    console.error('Error accessing camera:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.textContent = 'Could not access the camera. Please ensure it is connected and you granted permission.';
                    document.querySelector('.user-container').insertBefore(errorDiv, document.getElementById('profile-pic-container'));
                    setTimeout(() => errorDiv.remove(), 3000);
                    return;
                }
            } else {
                // Capture photo
                if (!video.videoWidth || !video.videoHeight) {
                    return;
                }
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
                if (!blob) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.textContent = 'Could not process the captured photo. Please try again.';
                    document.querySelector('.user-container').insertBefore(errorDiv, document.getElementById('profile-pic-container'));
                    return;
                }

                capturedPhoto = new File([blob], `photo_${Date.now()}.png`, { type: 'image/png' });
                profilePicturePreview.src = URL.createObjectURL(blob);
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
                captureButton.innerHTML = '<i class="bi bi-camera" aria-hidden="true"></i> Take a photo';
            }
        }

        // Stop camera on page unload
        window.addEventListener('beforeunload', stopCamera);
    </script>
</body>
</html>