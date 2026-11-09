<?php
session_start();

$timeout_message = isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity'] > 3600)
    ? "Your session has timed out due to inactivity."
    : "";

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie_params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => $cookie_params['secure'],
        'httponly' => $cookie_params['httponly'],
        'samesite' => $cookie_params['samesite'],
    ]);
}

session_destroy();
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        /* Gradient background */
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .dark body {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen transition-colors duration-300">
    <div class="w-full max-w-md p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl animate-fadeIn">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <svg class="h-12 w-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-2.76-2.24-5-5-5S2 8.24 2 11m20 0c0-2.76-2.24-5-5-5s-5 2.24-5 5m5 5v3m-5-3v3"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-4">You Have Been Logged Out</h2>
        <?php if ($timeout_message): ?>
            <p class="text-sm text-red-600 dark:text-red-400 text-center mb-4"><?php echo $timeout_message; ?></p>
        <?php else: ?>
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">You have successfully logged out.</p>
        <?php endif; ?>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a 
                href="login.php" 
                class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition duration-200"
            >
                Login Again
            </a>
            <a 
                href="/" 
                class="inline-block px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition duration-200"
            >
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>