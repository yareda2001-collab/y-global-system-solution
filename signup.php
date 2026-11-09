<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: users/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    unset($_SESSION['error']); // Clear OAuth errors for email signup
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Database error occurred.";
            error_log("SQL prepare error: " . $conn->error);
        } else {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Username or email already taken.";
            } else {
                $profile_picture = NULL;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['profile_picture'];
                    $allowed_types = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                    ];
                    $max_size = 2 * 1024 * 1024;
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error = match ($file['error']) {
                            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Profile picture is too large to upload.",
                            UPLOAD_ERR_PARTIAL => "Profile picture upload was interrupted. Please try again.",
                            default => "Profile picture could not be uploaded. Please try again.",
                        };
                    } else {
                        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $file_info = new finfo(FILEINFO_MIME_TYPE);
                        $mime_type = $file_info->file($file['tmp_name']);
                    }

                    if (!$error && (!isset($allowed_types[$file_ext]) || $allowed_types[$file_ext] !== $mime_type)) {
                        $error = "Only JPG, JPEG, PNG allowed.";
                    } elseif (!$error && $file['size'] > $max_size) {
                        $error = "File size must not exceed 2MB.";
                    }

                    if (!$error) {
                        $upload_dir = __DIR__ . '/admin/Uploads/';
                        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                            $error = "The profile picture upload folder could not be created.";
                        } elseif (!is_writable($upload_dir)) {
                            $error = "The profile picture upload folder is not writable.";
                        }
                        if (!$error) {
                            $file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
                            $file_path = $upload_dir . $file_name;
                            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                                $profile_picture = $file_name;
                            } else {
                                error_log("Could not move signup profile picture to $file_path");
                                $error = "Could not save the profile picture. Please check the upload folder permissions and try again.";
                            }
                        }
                    }
                }

                if (empty($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (username, email, password, profile_picture, role, status, force_password_change, created_at) VALUES (?, ?, ?, ?, 'user', 1, 0, NOW())";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        $error = "Database error occurred.";
                        error_log("SQL prepare error: " . $conn->error);
                        if ($profile_picture && is_file(__DIR__ . '/admin/Uploads/' . $profile_picture)) {
                            unlink(__DIR__ . '/admin/Uploads/' . $profile_picture);
                        }
                    } else {
                        $stmt->bind_param("ssss", $username, $email, $hashed_password, $profile_picture);
                        if ($stmt->execute()) {
                            $success = "Registration successful! <a href='login.php'>Log in</a>.";
                            error_log("Email signup success: username=$username, email=$email");
                        } else {
                            $error = "Registration failed.";
                            error_log("Email signup failed: " . $conn->error);
                            if ($profile_picture && is_file(__DIR__ . '/admin/Uploads/' . $profile_picture)) {
                                unlink(__DIR__ . '/admin/Uploads/' . $profile_picture);
                            }
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Generate Google OAuth URL
if (!isset($_SESSION['oauth2state'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth2state'] = $state;
} else {
    $state = $_SESSION['oauth2state'];
}
$authUrl = null;
if (GOOGLE_CLIENT_ID !== '' && GOOGLE_REDIRECT_URI !== '') {
    $authUrl = GOOGLE_AUTH_URL . '?' . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#033f5a">
  <link rel="manifest" href="manifest.php?v=4">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <title>Sign Up - Y-Global System Solution</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon1.png" rel="icon">
  <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=public-header-35" rel="stylesheet">
  <style>
    /* Header Styling */
    .header {
      background: linear-gradient(90deg, #033f5a 0%, #045c7f 100%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* General Navmenu Links */
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

    /* Styling for SignUp and Login Links */
    .navmenu ul li a[href="signup.php"],
    .navmenu ul li a[href="login.php"] {
      background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
      border-radius: 25px;
      padding: 8px 20px;
      margin-left: 10px;
      color: white;
      font-weight: 600;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(2, 136, 209, 0.3);
    }

    /* Hover Effect for SignUp and Login */
    .navmenu ul li a[href="signup.php"]:hover,
    .navmenu ul li a[href="login.php"]:hover {
      background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
      color: #ffffff;
    }

    /* Shine Effect on Hover */
    .navmenu ul li a[href="signup.php"]::before,
    .navmenu ul li a[href="login.php"]::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: 0.5s;
    }
    .navmenu ul li a[href="signup.php"]:hover::before,
    .navmenu ul li a[href="login.php"]:hover::before {
      left: 100%;
    }

    /* Responsive Adjustments */
    @media (max-width: 991px) {
      .navmenu ul li a {
        color: black;
      }
      .navmenu ul li a[href="signup.php"],
      .navmenu ul li a[href="login.php"] {
        background: linear-gradient(90deg, #0288d1 0%, #03a9f4 100%);
        color: white;
        width: 20%;
        display: inline-block;
        margin: 5px auto;
      }
    }
    @media (max-width: 576px) {
      .navmenu ul li a {
        padding: 5px 10px;
        font-size: 0.9rem;
      }
    }

    /* Signup Page Specific Styles */
    body {
      background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    }
    .signup-container {
      max-width: 500px;
      margin: 3rem auto;
      padding: 2rem;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .signup-container h2 {
      color: #033f5a;
      font-size: 1.8rem;
    }
    .form-control:focus {
      border-color: #033f5a;
      box-shadow: 0 0 0 0.2rem rgba(3, 63, 90, 0.25);
    }
    .btn-primary {
      background-color: #033f5a;
      border-color: #033f5a;
    }
    .btn-primary:hover {
      background-color: #022c41;
    }
    .btn-google {
      background-color: #4285F4;
      border-color: #4285F4;
      color: white;
    }
    .btn-google:hover {
      background-color: #3578E5;
    }
    .profile-picture-preview {
      display: none;
      max-width: 150px;
      max-height: 150px;
      margin: 10px auto;
      border-radius: 8px;
      border: 2px solid #033f5a;
    }
    .video-container {
      display: none;
      margin: 10px auto;
      text-align: center;
    }
    video {
      max-width: 100%;
      border-radius: 8px;
    }
    .custom-file-upload, .btn-capture {
      display: inline-block;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      font-weight: 400;
      line-height: 1.5;
      color: #fff;
      background-color: #033f5a;
      border: 1px solid #033f5a;
      border-radius: 0.375rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin: 0 5px;
    }
    .custom-file-upload:hover, .btn-capture:hover {
      background-color: #022c41;
    }
    .button-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 10px;
    }
    @media (max-width: 576px) {
      .signup-container {
        margin: 1rem;
        padding: 1.5rem;
      }
      .profile-picture-preview {
        max-width: 100px;
        max-height: 100px;
      }
      video {
        max-width: 90%;
      }
      .custom-file-upload, .btn-capture {
        width: 45%;
        text-align: center;
      }
    }
  </style>
  <link href="assets/css/auth.css?v=3" rel="stylesheet">
</head>

<body class="services-page mobile-app-page auth-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <section>
      <div class="container signup-container auth-card auth-signup-card">
        <div class="auth-heading">
          <span class="auth-heading-icon"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
          <h2>Create your account</h2>
          <p class="auth-subtitle">Join Y-Global to get started with your account.</p>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error']) && $_SESSION['error']['type'] === 'oauth'): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']['message']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="post" action="" class="needs-validation" novalidate enctype="multipart/form-data">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" autocomplete="username" placeholder="Choose a username" required>
            <div class="invalid-feedback">Please enter a username.</div>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" autocomplete="email" placeholder="you@example.com" required>
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="auth-password-field">
              <input type="password" class="form-control" id="password" name="password" minlength="6" autocomplete="new-password" placeholder="At least 6 characters" required>
              <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password" aria-controls="password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <div class="invalid-feedback">Password must be at least 6 characters.</div>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="auth-password-field">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password" placeholder="Enter your password again" required>
              <button type="button" class="auth-password-toggle" data-password-toggle="confirm_password" aria-label="Show password" aria-controls="confirm_password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <div class="invalid-feedback">Passwords must match.</div>
          </div>
          <div class="mb-3">
            <label for="profile_picture" class="form-label">Profile Picture (Optional)</label>
            <div class="video-container">
              <video id="video" autoplay playsinline></video>
            </div>
            <input type="file" class="form-control d-none" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/jpg">
            <div class="button-container">
              <label for="profile_picture" class="custom-file-upload">Choose File</label>
              <button type="button" class="btn-capture" id="captureButton">Take Photo</button>
            </div>
            <img id="profile-picture-preview" class="profile-picture-preview" src="#" alt="Preview">
            <canvas id="canvas" style="display: none;"></canvas>
          </div>
          <button type="submit" class="btn btn-primary w-100 mb-3">Sign Up</button>
        </form>
        <?php if ($authUrl !== null): ?>
          <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn btn-google w-100">
            <i class="bi bi-google"></i> Sign Up with Google
          </a>
        <?php else: ?>
          <p>Google sign-up is currently unavailable.</p>
        <?php endif; ?>
        <div class="auth-links">
          <p>Have an account? <a href="login.php">Log In</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/mobile-nav.php'; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/auth-password-toggle.js?v=1"></script>

  <script>
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

    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const profilePictureInput = document.getElementById('profile_picture');
    const profilePicturePreview = document.getElementById('profile-picture-preview');
    const captureButton = document.getElementById('captureButton');
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    let stream = null;

    confirmPasswordInput.addEventListener('input', function () {
      if (this.value !== passwordInput.value) {
        this.setCustomValidity('Passwords do not match.');
      } else {
        this.setCustomValidity('');
      }
    });

    profilePictureInput.addEventListener('change', function (event) {
      const file = event.target.files[0];
      if (file) {
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
          alert('Could not access the camera. Please ensure it is connected and you granted permission.');
          return;
        }
      } else {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = canvas.toDataURL('image/png');
        
        profilePicturePreview.src = imageData;
        profilePicturePreview.style.display = 'block';
        
        canvas.toBlob(blob => {
          const file = new File([blob], `photo_${Date.now()}.png`, { type: 'image/png' });
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          profilePictureInput.files = dataTransfer.files;
        }, 'image/png');
        
        stopCamera();
      }
    });

    function stopCamera() {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.parentElement.style.display = 'none';
        captureButton.textContent = 'Take Photo';
      }
    }

    window.addEventListener('beforeunload', stopCamera);
  </script>
</body>
</html>
<?php ob_end_flush(); ?>
