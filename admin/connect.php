<?php
require_once __DIR__ . '/../includes/load-env.php';

$connectionProfile = getenv('APP_DB_PROFILE') ?: 'local';
if ($connectionProfile === 'hosting') {
    require __DIR__ . '/connect1.php';
} elseif ($connectionProfile === 'local') {
    $servername = getenv('LOCAL_DB_HOST');
    $username = getenv('LOCAL_DB_USER');
    $password = getenv('LOCAL_DB_PASS');
    $database = getenv('LOCAL_DB_NAME');

    $conn = null;
    $db_config_error = null;

    if ($servername === false || $username === false || $password === false || $database === false) {
        $db_config_error = 'Database configuration is incomplete. Set the LOCAL_DB_* values in your local .env file.';
        error_log($db_config_error);
    } else {
        $conn = mysqli_connect($servername, $username, $password, $database);
        if (!$conn) {
            $db_config_error = 'Database connection failed.';
            error_log('Local database connection failed: ' . mysqli_connect_error());
        }
    }
} else {
    $conn = null;
    $db_config_error = 'Database profile is invalid. Set APP_DB_PROFILE to local or hosting.';
    error_log($db_config_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$passwordChangeAllowedScripts = ['change_password.php', 'logout.php'];
$adminSessionActive = ($_SESSION['admin_logged_in'] ?? false) === true
    && isset($_SESSION['user_id']);
$passwordChangeRequired = !empty($_SESSION['force_password_change']);

if ($adminSessionActive && $conn instanceof mysqli) {
    try {
        $forcePasswordStmt = $conn->prepare(
            "SELECT force_password_change FROM users WHERE id = ? AND role IN ('admin', 'super_admin')"
        );
        if ($forcePasswordStmt === false) {
            throw new RuntimeException('Could not prepare the forced-password-change check.');
        }

        $adminUserId = (int) $_SESSION['user_id'];
        $forcePasswordStmt->bind_param('i', $adminUserId);
        $forcePasswordStmt->execute();
        $adminPasswordState = $forcePasswordStmt->get_result()->fetch_assoc();
        $forcePasswordStmt->close();

        $passwordChangeRequired = (int) ($adminPasswordState['force_password_change'] ?? 0) === 1;
        if ($passwordChangeRequired) {
            $_SESSION['force_password_change'] = true;
        } else {
            unset($_SESSION['force_password_change']);
        }
    } catch (mysqli_sql_exception | RuntimeException $error) {
        error_log('Could not verify admin forced-password-change status: ' . $error->getMessage());
        $passwordChangeRequired = true;
        $_SESSION['force_password_change'] = true;
    }
}

if (
    $adminSessionActive
    && $passwordChangeRequired
    && !in_array($currentAdminScript, $passwordChangeAllowedScripts, true)
) {
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (stripos($acceptHeader, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Password change required.',
            'redirect' => 'change_password.php',
        ]);
        exit;
    }

    header('Location: change_password.php');
    exit;
}
?>
