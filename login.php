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
$timeout_duration = 3600; // 1 hour

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    $error = "Session timed out. Please log in again.";
}

// Handle password-based login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $sql = "SELECT id, username, email, password, status, role FROM users WHERE username = ? AND role = 'user' AND provider IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['status'] == 0) {
                $error = "Account inactive. Contact yared1357@gmail.com.";
                error_log("Inactive account login attempt: username=$username");
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                error_log("Password login success: username=$username, user_id=" . $user['id']);
                header("Location: users/dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
                error_log("Failed password login: username=$username");
            }
        } else {
            $sql = "SELECT role FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = "Admins must use admin login. Contact yared1357@gmail.com.";
                error_log("Admin login attempt on user page: username=$username");
            } else {
                $error = "Invalid username or password.";
                error_log("No user found: username=$username");
            }
        }
        $stmt->close();
    }
}

// Generate Google OAuth URL
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;
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
  <link rel="manifest" href="manifest.php?v=6">
  <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
  <title>Login - Y-Global System Solution</title>
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
  <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">
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

    /* Login Page Specific Styles */
    body {
      background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    }
    .login-container {
      max-width: 400px;
      margin: 3rem auto;
      padding: 2rem;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .login-container h2 {
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
  </style>
  <link href="assets/css/auth.css?v=2" rel="stylesheet">
</head>

<body class="services-page mobile-app-page auth-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
    <section>
      <div class="container login-container auth-card">
        <div class="auth-heading">
          <span class="auth-heading-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
          <h2>Welcome back</h2>
          <p class="auth-subtitle">Log in to continue to your Y-Global account.</p>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="post" action="" class="needs-validation" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" autocomplete="username" placeholder="Enter your username" required>
            <div class="invalid-feedback">Please enter your username.</div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="auth-password-field">
              <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
              <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password" aria-controls="password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <div class="invalid-feedback">Please enter your password.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100 mb-3">Log In</button>
        </form>
        <?php if ($authUrl !== null): ?>
          <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn btn-google w-100">
            <i class="bi bi-google"></i> Login with Google
          </a>
        <?php else: ?>
          <p>Google sign-in is currently unavailable.</p>
        <?php endif; ?>
        <div class="auth-links">
          <p><a href="forgot_password.php">Forgot Password?</a></p>
          <p>Need an account? <a href="signup.php">Sign Up</a></p>
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
  </script>
</body>
</html>
<?php ob_end_flush(); ?>
