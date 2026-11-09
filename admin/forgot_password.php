<?php
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
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_token'] = $token;
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
            $_SESSION['rate_limit_error'] = $remaining_time;
            error_log("Rate limit triggered: email=$email, remaining=$remaining_time");
        } else {
            // Check if email exists in users table
            $sql = "SELECT id, username FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $username = $user['username'];

                // Generate unique token
                $token = bin2hex(random_bytes(32));
                // Insert reset request
                $sql = "INSERT INTO password_reset_requests (user_id, email, requested_at, status, token) VALUES (?, ?, NOW(), 'pending', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $email, $token);
                if ($stmt->execute()) {
                    $success = "Your password reset request has been submitted. The Super Admin will send you a reset link with an OTP.";
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['success_modal'] = true; // Trigger success modal
                    $email = ''; // Clear email after success
                    error_log("Reset request created: email=$email, token=$token");
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
    $email = $_SESSION['reset_email'] ?? '';
    $token = $_SESSION['reset_token'] ?? '';
    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } elseif (empty($email) || empty($token)) {
        $error = "Session expired. Please start the reset process again.";
        error_log("Session expired during OTP submission: email=$email, token=$token");
    } else {
        // Verify OTP and token
        $sql = "SELECT user_id, otp_expires_at FROM password_reset_requests WHERE email = ? AND token = ? AND otp = ? AND status = 'approved'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $token, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            $expires_at = strtotime($request['otp_expires_at']);
            $current_time = time();
            if ($current_time > $expires_at) {
                $error = "This OTP has expired. Contact the Super Admin at yared1357@gmail.com.";
                // Update status to expired
                $sql = "UPDATE password_reset_requests SET status = 'expired' WHERE email = ? AND token = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $email, $token);
                $stmt->execute();
                error_log("OTP expired: email=$email, token=$token");
            } else {
                $step = 'reset_password';
                $_SESSION['reset_user_id'] = $request['user_id'];
                error_log("OTP verified: email=$email, user_id=" . $request['user_id']);
            }
        } else {
            $error = "Invalid OTP.";
            error_log("Invalid OTP attempt: email=$email, token=$token, otp=$otp");
        }
        $stmt->close();
    }
}

// Step 3: Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'] ?? '';
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    $token = $_SESSION['reset_token'] ?? '';

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
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            // Mark request as completed
            $sql = "UPDATE password_reset_requests SET status = 'completed' WHERE email = ? AND token = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $email, $token, $user_id);
            $stmt->execute();
            $success = "Password reset successfully. Redirecting to login...";
            header("Refresh: 2; URL=login.php");
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            error_log("Password reset successful: email=$email, user_id=$user_id");
        } else {
            $error = "Error resetting password: " . $stmt->error;
            error_log("Error resetting password: " . $stmt->error);
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Y-Global System Solution</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .dark body {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
        .modal {
            backdrop-filter: blur(5px);
        }
        .reset-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
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
            border-color: #d1d5db;
            background: linear-gradient(145deg, #fff, #f3f4f6);
            color: #374151;
            box-shadow: 0 5px 14px rgba(0, 0, 0, 0.08);
        }
        .reset-action-home:hover {
            background: #f3f4f6;
            color: #111827;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        }
        .reset-action-submit {
            background: linear-gradient(100deg, #2563eb, #0284c7, #06b6d4);
            color: #fff;
            box-shadow: 0 8px 18px rgba(14, 116, 200, 0.25);
        }
        .reset-action-submit:hover {
            color: #fff;
            box-shadow: 0 11px 22px rgba(14, 116, 200, 0.34);
        }
        .reset-action:focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 3px;
        }
        @media (max-width: 360px) {
            .reset-actions {
                gap: 0.45rem;
            }
            .reset-action {
                gap: 0.3rem;
                padding: 0.6rem 0.4rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen transition-colors duration-300">
    <div class="w-full max-w-md p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl animate-fadeIn">
        <div class="flex justify-center mb-6">
            <svg class="h-12 w-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-2.76-2.24-5-5-5S2 8.24 2 11m20 0c0-2.76-2.24-5-5-5s-5 2.24-5 5m5 5v3m-5-3v3"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">
            <?php echo $step === 'email' ? 'Forgot Password' : ($step === 'otp' ? 'Enter OTP' : 'Reset Password'); ?>
        </h2>
        <?php if ($error && !isset($_SESSION['rate_limit_error']) && !isset($_SESSION['success_modal'])): ?>
            <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm p-3 rounded-lg mb-4 text-center" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success && $step === 'reset_password'): ?>
            <div class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-sm p-3 rounded-lg mb-4 text-center" role="alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($step === 'email' && !$success && !isset($_SESSION['rate_limit_error'])): ?>
            <form method="post" action="" class="space-y-5" novalidate>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="Enter your email" 
                        required 
                        aria-required="true"
                        value="<?php echo htmlspecialchars($email); ?>"
                        class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    >
                </div>
                <div class="reset-actions">
                    <a href="../index.php" class="reset-action reset-action-home">
                        <i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span>
                    </a>
                    <button type="submit" class="reset-action reset-action-submit">
                        <i class="bi bi-send" aria-hidden="true"></i><span>Submit Request</span>
                    </button>
                </div>
            </form>
        <?php elseif ($step === 'otp'): ?>
            <form method="post" action="" class="space-y-5" novalidate>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">One-Time Password (OTP)</label>
                    <input 
                        type="text" 
                        name="otp" 
                        id="otp" 
                        placeholder="Enter 6-digit OTP" 
                        required 
                        aria-required="true"
                        maxlength="6"
                        autofocus
                        class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    >
                </div>
                <div class="reset-actions">
                    <a href="../index.php" class="reset-action reset-action-home">
                        <i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span>
                    </a>
                    <button type="submit" class="reset-action reset-action-submit">
                        <i class="bi bi-shield-check" aria-hidden="true"></i><span>Verify OTP</span>
                    </button>
                </div>
            </form>
        <?php elseif ($step === 'reset_password'): ?>
            <form method="post" action="" class="space-y-5" novalidate>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your password should be</label>
                    <input 
                        type="password" 
                        name="new_password" 
                        id="new_password" 
                        placeholder="Password (minimum 6 characters)" 
                        required 
                        aria-required="true"
                        class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    >
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm your password</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirm_password" 
                        placeholder="Confirm password (minimum 6 characters)" 
                        required 
                        aria-required="true"
                        class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                    >
                </div>
                <div class="reset-actions">
                    <a href="../index.php" class="reset-action reset-action-home">
                        <i class="bi bi-house-door" aria-hidden="true"></i><span>Back to Home</span>
                    </a>
                    <button type="submit" class="reset-action reset-action-submit">
                        <i class="bi bi-key" aria-hidden="true"></i><span>Reset Password</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Rate Limit Modal -->
    <div id="rateLimitModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Active Reset Request</h3>
            <p id="rateLimitMessage" class="text-sm text-gray-600 dark:text-gray-400"></p>
            <button id="closeRateLimitModal" class="mt-4 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">OK</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Request Submitted</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($success); ?></p>
            <button id="closeSuccessModal" class="mt-4 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Close</button>
        </div>
    </div>

    <script>
        document.querySelector('form')?.addEventListener('submit', function (e) {
            <?php if ($step === 'email'): ?>
                const email = document.getElementById('email')?.value.trim();
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                }
            <?php elseif ($step === 'otp'): ?>
                const otp = document.getElementById('otp').value.trim();
                if (!otp || !/^\d{6}$/.test(otp)) {
                    e.preventDefault();
                    alert('Please enter a valid 6-digit OTP.');
                }
            <?php elseif ($step === 'reset_password'): ?>
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                if (!newPassword || !confirmPassword) {
                    e.preventDefault();
                    alert('All fields are required.');
                } else if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                } else if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                }
            <?php endif; ?>
        });

        <?php if (isset($_SESSION['rate_limit_error'])): ?>
            let timeLeft = <?php echo $_SESSION['rate_limit_error']; ?>;
            const rateLimitModal = document.getElementById('rateLimitModal');
            const rateLimitMessage = document.getElementById('rateLimitMessage');
            const closeRateLimitModal = document.getElementById('closeRateLimitModal');

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

            rateLimitModal.classList.remove('hidden');
            updateTimer();

            closeRateLimitModal.addEventListener('click', () => {
                rateLimitModal.classList.add('hidden');
                <?php unset($_SESSION['rate_limit_error']); ?>
            });
        <?php endif; ?>

        <?php if (isset($_SESSION['success_modal']) && $step === 'email'): ?>
            const successModal = document.getElementById('successModal');
            const closeSuccessModal = document.getElementById('closeSuccessModal');

            successModal.classList.remove('hidden');

            closeSuccessModal.addEventListener('click', () => {
                successModal.classList.add('hidden');
                <?php unset($_SESSION['success_modal']); ?>
                window.location.href = 'forgot_password.php'; // Refresh to show form again
            });
        <?php endif; ?>
    </script>
</body>
</html>