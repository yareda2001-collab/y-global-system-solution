<?php
session_start();

// Database connection
include 'connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
        error_log("Login attempt with empty fields.");
    } else {
        // Query for admin or super_admin
        $sql = "SELECT id, username, password, role, status, force_password_change FROM users WHERE username = ? AND role IN ('admin', 'super_admin')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Database error: Unable to prepare query.";
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                error_log("User found: " . print_r($user, true));

                // Check account status
                if ($user['status'] == 0) {
                    $error = "Your account is disabled. Contact a Super Admin.";
                    error_log("Login attempt with disabled account: $username");
                } else {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['force_password_change'] = (int) $user['force_password_change'] === 1;

                        // Check if password change is required
                        if ($_SESSION['force_password_change']) {
                            header("Location: change_password.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                        error_log("Password mismatch for username: $username");
                    }
                }
            } else {
                $error = "Invalid username or password.";
                error_log("No admin user found for username: $username");
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Y-Global System Solution</title>
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
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #4b5563;
        }
        .dark .password-toggle {
            color: #9ca3af;
        }
        .password-toggle:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen transition-colors duration-300">
    <div class="w-full max-w-md p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl animate-fadeIn">
        <div class="flex justify-center mb-6">
            <svg class="h-12 w-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 4.5 6v5.5c0 4.2 3.1 7.8 7.5 9.5 4.4-1.7 7.5-5.3 7.5-9.5V6L12 3Z"></path>
                <circle cx="12" cy="9" r="2" stroke-width="1.8"></circle>
                <path stroke-linecap="round" stroke-width="1.8" d="M8.8 15.5c.5-1.6 1.7-2.5 3.2-2.5s2.7.9 3.2 2.5"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">Admin Login</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm p-3 rounded-lg mb-4 text-center" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="" class="space-y-5" novalidate>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    placeholder="Enter your username" 
                    required 
                    aria-required="true"
                    class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                >
            </div>
            <div class="password-container">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    placeholder="Enter your password" 
                    required 
                    aria-required="true"
                    class="mt-1 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                >
                <i class="bi bi-eye-slash password-toggle" id="password-toggle"></i>
            </div>
            <button 
                type="submit" 
                class="group relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-sky-600 to-cyan-500 px-6 py-3.5 text-base font-bold text-white shadow-lg shadow-blue-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-500/30 focus:ring-4 focus:ring-blue-200 focus:outline-none dark:focus:ring-blue-800"
            >
                <span class="relative z-10 inline-flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16V8m0 0-3 3m3-3 3 3M5 19h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"></path>
                    </svg>
                    Login
                </span>
            </button>
        </form>
        <div class="mt-4 flex flex-row justify-center gap-2">
            <a 
                href="forgot_password.php" 
                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-gradient-to-r from-blue-600 to-sky-500 px-2 py-2.5 text-xs font-semibold text-white shadow-md transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:gap-2 sm:px-4 sm:text-sm"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 1.806-2 3.272-2 1.99 0 3.6 1.343 3.6 3 0 1.657-1.61 3-3.6 3-.38 0-.748-.05-1.09-.143L8 14l.557-2.171A2.69 2.69 0 0 1 7.9 10c0-.36.117-.7.328-1ZM12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                </svg>
                Forgot Password
            </a>
            <a 
                href="/yared/" 
                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-gray-200 bg-white px-2 py-2.5 text-xs font-semibold text-gray-700 shadow-md transition duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:bg-gray-50 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800 sm:gap-2 sm:px-4 sm:text-sm"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 10 9-7 9 7M5 9v11h14V9M9 20v-6h6v6"></path>
                </svg>
                Back to Home
            </a>
        </div>
    </div>
    <script>
        // Toggle password visibility
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        passwordToggle.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Handle Enter key for username field
        document.getElementById('username').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
            }
        });
    </script>
</body>
</html>