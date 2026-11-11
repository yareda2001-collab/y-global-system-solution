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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }

        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }

        .dark body {
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        }

        .logout-btn {
            white-space: nowrap;
            min-width: 0;
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-6 transition-colors duration-300">
    <div class="logout-card w-full max-w-xl rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-2xl shadow-slate-300/40 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/95 dark:shadow-black/30 animate-fadeIn sm:p-8">
        <div class="mb-6 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/25">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                </svg>
            </div>
        </div>

        <h2 class="mb-3 text-center text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">You Have Been Logged Out</h2>

        <?php if ($timeout_message): ?>
            <p class="mb-6 text-center text-sm font-medium text-red-600 dark:text-red-400"><?php echo $timeout_message; ?></p>
        <?php else: ?>
            <p class="mb-6 text-center text-sm text-slate-600 dark:text-slate-300">You have successfully logged out from the admin portal.</p>
        <?php endif; ?>

        <div class="flex flex-nowrap items-center justify-center gap-2 sm:gap-3">
            <a href="login.php" class="logout-btn inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:scale-[1.02] hover:from-blue-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 sm:px-5 sm:text-base">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h12m-8 8h7a2 2 0 002-2V6a2 2 0 00-2-2h-7"/>
                </svg>
                <span>Login Again</span>
            </a>

            <a href="/" class="logout-btn inline-flex items-center justify-center gap-2 rounded-xl bg-slate-700 px-3 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-300/40 transition hover:scale-[1.02] hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 sm:px-5 sm:text-base">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h14V10"/>
                </svg>
                <span>Back to Home</span>
            </a>
        </div>
    </div>
</body>
</html>