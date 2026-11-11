<?php
ob_start(); // Start output buffering
session_start();

include 'connect.php';

$step = 'email'; // Default: Ask for email
$error = '';
$success = '';
$remaining_time = null;
$email = isset($_POST['email']) ? trim($_POST['email']) : (isset($_GET['email']) ? trim($_GET['email']) : '');

// Check rate limit for active requests (4 hours = 14400 seconds)
function getRemainingTime($conn, $email) {
    $sql = "SELECT requested_at FROM password_reset_requests WHERE email = ? AND status IN ('pending', 'approved') ORDER BY requested_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $requested_at = strtotime($row['requested_at']);
        $current_time = time();
        $elapsed = $current_time - $requested_at;
        $limit = 4 * 3600; // 4 hours
        if ($elapsed < $limit) {
            return $limit - $elapsed;
        }
    }
    return 0;
}

// Handle OTP link validation
if (isset($_GET['step']) && $_GET['step'] === 'otp' && !empty($email) && isset($_GET['token'])) {
    $token = trim($_GET['token']);
    error_log("Validating OTP link: email=$email, token=$token");
    $sql = "SELECT id, status FROM password_reset_requests WHERE email = ? AND token = ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $step = 'otp';
        $_SESSION['pw_reset_email'] = $email;
        $_SESSION['pw_reset_token'] = $token;
        error_log("OTP step activated for email=$email");
    } else {
        $error = "Invalid or expired reset link. Please submit a new request.";
        error_log("Invalid reset link: email=$email, token=$token, rows=" . $result->num_rows);
    }
    $stmt->close();
}

// Step 1: Handle email submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check rate limit for active requests first
        $remaining_time = getRemainingTime($conn, $email);
        if ($remaining_time > 0) {
            $_SESSION['pw_reset_rate_limit_error'] = $remaining_time;
            error_log("Rate limit triggered: email=$email, remaining=$remaining_time");
        } else {
            // Check if email exists in users table
            $sql = "SELECT id, username, role FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $username = $user['username'];
                $role = $user['role'];

                // Store role for later redirect
                $_SESSION['pw_reset_role'] = $role;

                // Generate unique token
                $token = bin2hex(random_bytes(32));
                // Insert reset request
                $sql = "INSERT INTO password_reset_requests (user_id, email, requested_at, status, token) VALUES (?, ?, NOW(), 'pending', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $email, $token);
                if ($stmt->execute()) {
                    $success = "Your password reset request has been submitted. The Super Admin will send you a reset link with an OTP.";
                    $_SESSION['pw_reset_email'] = $email;
                    $_SESSION['pw_reset_success_modal'] = true;
                    $email = '';
                    error_log("Reset request created: email=$email, user_id=$user_id, token=$token, role=$role");
                } else {
                    $error = "Error submitting request: " . $stmt->error;
                    error_log("Error inserting reset request: " . $stmt->error);
                }
            } else {
                $error = "No account found with this email.";
                error_log("No user found for email: $email");
            }
            $stmt->close();
        }
    }
}

// Step 2: Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);
    $email = $_SESSION['pw_reset_email'] ?? '';
    $token = $_SESSION['pw_reset_token'] ?? '';
    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } elseif (empty($email) || empty($token)) {
        $error = "Session expired. Please start the reset process again.";
        error_log("Session expired during OTP submission: email=$email, token=$token");
    } else {
        // Check if the reset request exists and is approved
        $sql = "SELECT user_id, otp, otp_expires_at FROM password_reset_requests WHERE email = ? AND token = ? AND status = 'approved'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            $stored_otp = $request['otp'];
            $expires_at = strtotime($request['otp_expires_at']);
            $current_time = time();

            // Check if OTP has expired
            if ($current_time > $expires_at) {
                $error = "This OTP has expired. Contact the Super Admin at yared1357@gmail.com.";
                // Update status to expired
                $sql = "UPDATE password_reset_requests SET status = 'expired' WHERE email = ? AND token = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $email, $token);
                $stmt->execute();
                error_log("OTP expired: email=$email, token=$token");
            }
            // Check if OTP is correct
            elseif ($otp !== $stored_otp) {
                $error = "The OTP you entered is incorrect.";
                error_log("Incorrect OTP attempt: email=$email, token=$token, otp=$otp");
            }
            // OTP is valid and not expired
            else {
                $step = 'reset_password';
                $_SESSION['pw_reset_user_id'] = $request['user_id'];
                error_log("OTP verified: email=$email, user_id=" . $request['user_id']);
            }
        } else {
            $error = "Invalid or expired reset link. Please submit a new request.";
            error_log("Invalid reset link during OTP submission: email=$email, token=$token, otp=$otp");
        }
        $stmt->close();
    }
}

// Step 3: Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['pw_reset_email'] ?? '';
    $user_id = $_SESSION['pw_reset_user_id'] ?? 0;
    $token = $_SESSION['pw_reset_token'] ?? '';
    $role = $_SESSION['pw_reset_role'] ?? 'user';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (empty($email) || $user_id === 0 || empty($token)) {
        $error = "Session expired. Please start the reset process again.";
        error_log("Session expired during password reset: email=$email, user_id=$user_id");
    } else {
        // Verify user_id exists in users table
        $sql = "SELECT id FROM users WHERE id = ? AND email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = "Invalid user account. Please start the reset process again.";
            error_log("Invalid user_id during reset: user_id=$user_id, email=$email");
            $stmt->close();
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql = "UPDATE users SET password = ?, force_password_change = 0 WHERE id = ? AND email = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Database error: Unable to prepare statement.";
                error_log("Prepare failed: " . $conn->error);
                $stmt->close();
            } else {
                $stmt->bind_param("sis", $hashed_password, $user_id, $email);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        // Mark request as completed
                        $sql = "UPDATE password_reset_requests SET status = 'completed' WHERE email = ? AND token = ? AND user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $email, $token, $user_id);
                        $stmt->execute();
                        $success = "Password reset successfully. You will be redirected shortly.";
                        // Destroy session to prevent Super Admin login
                        session_unset();
                        session_destroy();
                        session_start();
                        $_SESSION['pw_reset_success'] = true;
                        $_SESSION['pw_reset_role'] = $role;
                        header("Refresh: 2; URL=index.php");
                        error_log("Password reset successful: email=$email, user_id=$user_id, role=$role, hashed_password=$hashed_password");
                    } else {
                        $error = "Failed to update password: No user found with ID $user_id and email $email.";
                        error_log("No rows affected: user_id=$user_id, email=$email, hashed_password=$hashed_password");
                    }
                } else {
                    $error = "Error resetting password: " . $stmt->error;
                    error_log("Error resetting password: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
ob_end_flush(); // End output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#033f5a">
    <link rel="manifest" href="manifest.php?v=6">
    <link rel="apple-touch-icon" href="assets/img/pwa-192.png?v=4">
    <title>Forgot Password - Y-Global System Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/img/favicon1.png" rel="icon">
    <link href="assets/img/pwa-192.png?v=4" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="assets/css/main.css?v=public-header-36" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .reset-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .reset-container h2 {
            font-family: 'Poppins', sans-serif;
            color: #033f5a;
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
            border-color: #022c41;
        }
        .modal-content {
            border-radius: 10px;
        }
        .reset-container {
            border: 1px solid rgba(220, 232, 239, 0.9);
            border-radius: 20px;
            box-shadow: 0 20px 55px rgba(27, 55, 77, 0.12);
        }
        .reset-container h2 {
            font-size: clamp(1.45rem, 5vw, 1.8rem);
            font-weight: 700;
            letter-spacing: -0.035em;
        }
        .reset-container .form-label {
            color: #3b5666;
            font-size: 0.86rem;
            font-weight: 600;
        }
        .reset-container .form-control {
            min-height: 48px;
            border-color: #dce7ec;
            border-radius: 11px;
            background-color: #fbfdfe;
            font-size: 0.9rem;
        }
        .reset-container .form-control:focus {
            border-color: #6db8ca;
            box-shadow: 0 0 0 3px rgba(22, 131, 160, 0.12);
        }
        .reset-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .reset-action {
            display: inline-flex;
            min-width: 0;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.65rem 0.75rem;
            border: 1px solid transparent;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 650;
            line-height: 1.2;
            text-align: center;
            text-decoration: none;
            transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease;
        }
        .reset-action:hover {
            transform: translateY(-2px);
        }
        .reset-action-home {
            border-color: #d1dce3;
            background: linear-gradient(145deg, #fff, #f3f7f9);
            color: #3b5666;
            box-shadow: 0 5px 14px rgba(22, 53, 72, 0.08);
        }
        .reset-action-home:hover {
            background: #edf4f7;
            color: #19384a;
            box-shadow: 0 8px 18px rgba(22, 53, 72, 0.12);
        }
        .reset-action-submit {
            border: 0;
            background: linear-gradient(100deg, #2563eb, #0284c7, #06b6d4);
            color: #fff;
            box-shadow: 0 8px 18px rgba(14, 116, 200, 0.25);
        }
        .reset-action-submit:hover {
            background: linear-gradient(100deg, #1d4ed8, #0369a1, #0891b2);
            color: #fff;
            box-shadow: 0 11px 22px rgba(14, 116, 200, 0.34);
        }
        .reset-action:focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 3px;
        }
        .reset-container .reset-back-login {
            margin: 0.85rem 0 0;
            font-size: 0.82rem;
        }
        @media (max-width: 576px) {
            .reset-section {
                padding: 16px 0 20px;
            }
            .reset-container {
                width: calc(100% - 32px);
                margin: 0 auto;
                padding: 1.25rem;
            }
            .reset-actions {
                gap: 0.55rem;
            }
            .reset-action {
                gap: 0.35rem;
                padding: 0.6rem 0.5rem;
                font-size: 0.74rem;
            }
        }
        @media (max-width: 360px) {
            .reset-actions {
                gap: 0.4rem;
            }
            .reset-action {
                gap: 0.25rem;
                padding: 0.55rem 0.35rem;
                font-size: 0.68rem;
            }
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
        .navmenu ul li a[href="signup.php"]:hover,
        .navmenu ul li a[href="login.php"]:hover {
            background: linear-gradient(90deg, #0277bd 0%, #0288d1 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(2, 136, 209, 0.5);
            color: #ffffff;
        }
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
    </style>
</head>
<body class="mobile-app-page">
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main">
        <section class="reset-section">
            <div class="container reset-container" data-aos="fade-up">
                <h2 class="text-center mb-4">
                    <?php echo $step === 'email' ? 'Forgot Password' : ($step === 'otp' ? 'Enter OTP' : 'Reset Password'); ?>
                </h2>
                <?php if ($error && !isset($_SESSION['pw_reset_rate_limit_error']) && !isset($_SESSION['pw_reset_success_modal'])): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success && $step === 'reset_password'): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($step === 'email' && !$success && !isset($_SESSION['pw_reset_rate_limit_error'])): ?>
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email); ?>">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="reset-actions">
                            <a href="index.php" class="reset-action reset-action-home"><i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span></a>
                            <button type="submit" class="reset-action reset-action-submit"><i class="bi bi-send" aria-hidden="true"></i><span>Submit Request</span></button>
                        </div>
                    </form>
                <?php elseif ($step === 'otp'): ?>
                    <form method="post" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <div class="mb-3">
                            <label for="otp" class="form-label">One-Time Password (OTP)</label>
                            <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter 6-digit OTP" required maxlength="6" autofocus>
                            <div class="invalid-feedback">Please enter a valid 6-digit OTP.</div>
                        </div>
                        <div class="reset-actions">
                            <a href="index.php" class="reset-action reset-action-home"><i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span></a>
                            <button type="submit" class="reset-action reset-action-submit"><i class="bi bi-shield-check" aria-hidden="true"></i><span>Verify OTP</span></button>
                        </div>
                    </form>
                <?php elseif ($step === 'reset_password'): ?>
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Password (minimum 6 characters)" required>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password (minimum 6 characters)" required>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                        <div class="reset-actions">
                            <a href="index.php" class="reset-action reset-action-home"><i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span></a>
                            <button type="submit" class="reset-action reset-action-submit"><i class="bi bi-key" aria-hidden="true"></i><span>Reset Password</span></button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Rate Limit Modal -->
    <div class="modal fade" id="rateLimitModal" tabindex="-1" aria-labelledby="rateLimitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rateLimitModalLabel">Active Reset Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="rateLimitMessage" class="text-muted"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Request Submitted</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><?php echo htmlspecialchars($success); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="closeSuccessModal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/mobile-nav.php'; ?>
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
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

                    <?php if ($step === 'email'): ?>
                        const email = document.getElementById('email')?.value.trim();
                        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            event.preventDefault();
                            alert('Please enter a valid email address.');
                        }
                    <?php elseif ($step === 'otp'): ?>
                        const otp = document.getElementById('otp')?.value.trim();
                        if (!otp || !/^\d{6}$/.test(otp)) {
                            event.preventDefault();
                            alert('Please enter a valid 6-digit OTP.');
                        }
                    <?php elseif ($step === 'reset_password'): ?>
                        const newPassword = document.getElementById('new_password')?.value;
                        const confirmPassword = document.getElementById('confirm_password')?.value;
                        if (!newPassword || !confirmPassword) {
                            event.preventDefault();
                            alert('All fields are required.');
                        } else if (newPassword !== confirmPassword) {
                            event.preventDefault();
                            alert('Passwords do not match.');
                        } else if (newPassword.length < 6) {
                            event.preventDefault();
                            alert('Password must be at least 6 characters long.');
                        }
                    <?php endif; ?>
                }, false);
            });
        })();

        // Rate Limit Modal
        <?php if (isset($_SESSION['pw_reset_rate_limit_error'])): ?>
            let timeLeft = <?php echo $_SESSION['pw_reset_rate_limit_error']; ?>;
            const rateLimitModal = new bootstrap.Modal(document.getElementById('rateLimitModal'));
            const rateLimitMessage = document.getElementById('rateLimitMessage');

            function updateTimer() {
                if (timeLeft <= 0) {
                    rateLimitMessage.textContent = 'You can now request a new reset.';
                    return;
                }
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;
                rateLimitMessage.textContent = `You have an active reset request. You can request again in ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}. Contact the Super Admin at yared1357@gmail.com for assistance.`;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }

            rateLimitModal.show();
            updateTimer();

            document.getElementById('rateLimitModal').addEventListener('hidden.bs.modal', () => {
                <?php unset($_SESSION['pw_reset_rate_limit_error']); ?>
            });
        <?php endif; ?>

        // Success Modal
        <?php if (isset($_SESSION['pw_reset_success_modal']) && $step === 'email'): ?>
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();

            document.getElementById('closeSuccessModal').addEventListener('click', () => {
                successModal.hide();
                <?php unset($_SESSION['pw_reset_success_modal']); ?>
                window.location.href = 'forgot_password.php';
            });
        <?php endif; ?>

        // Mobile navigation toggle
        document.querySelector('.mobile-nav-toggle').addEventListener('click', function () {
            document.querySelector('.navmenu').classList.toggle('active');
        });
    </script>
</body>
</html>