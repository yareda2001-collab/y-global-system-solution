<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'connect.php';

// Validate state parameter
if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Invalid state or code. Please try signing up with Google again.'];
    error_log('OAuth error: Invalid state or code. GET state: ' . ($_GET['state'] ?? 'none') . ', Session state: ' . ($_SESSION['oauth2state'] ?? 'none'));
    unset($_SESSION['oauth2state']);
    header('Location: signup.php');
    exit;
}
unset($_SESSION['oauth2state']); // Clear state after validation

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '' || GOOGLE_REDIRECT_URI === '') {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Google sign-in is not configured. Please try again later.'];
    error_log('OAuth error: Google credentials are not configured.');
    header('Location: signup.php');
    exit;
}

// Exchange code for access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Failed to connect to Google servers. Please try again later.'];
    error_log('OAuth cURL error: ' . $curl_error);
    header('Location: signup.php');
    exit;
}

$tokenData = json_decode($response, true);
if (isset($tokenData['error']) || !isset($tokenData['access_token'])) {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Failed to obtain access token. Please try again.'];
    error_log('Google token exchange failed.');
    header('Location: signup.php');
    exit;
}

$accessToken = $tokenData['access_token'];

// Fetch user info
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userResponse = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Failed to retrieve user info from Google. Please try again.'];
    error_log('Userinfo cURL error: ' . $curl_error);
    header('Location: signup.php');
    exit;
}

$user = json_decode($userResponse, true);
if (isset($user['error']) || !isset($user['sub']) || !isset($user['email'])) {
    $_SESSION['error'] = ['type' => 'oauth', 'message' => 'Failed to retrieve valid user info. Please try again.'];
    error_log('Google userinfo request returned an invalid response.');
    header('Location: signup.php');
    exit;
}

$providerId = $user['sub'];
$email = $user['email'];
$name = $user['name'] ?? 'User_' . uniqid();
$username = substr(explode('@', $email)[0] . '_' . uniqid(), 0, 50);
$profilePicture = $user['picture'] ?? null;

// Check if user exists
$sql = "SELECT id FROM users WHERE provider_id = ? OR email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = ['type' => 'database', 'message' => 'Database error occurred. Please try again later.'];
    error_log('SQL prepare error: ' . $conn->error);
    header('Location: signup.php');
    exit;
}
$stmt->bind_param("ss", $providerId, $email);
$stmt->execute();
$result = $stmt->get_result();
$existingUser = $result->fetch_assoc();

if ($existingUser) {
    // Update existing user
    $sql = "UPDATE users SET username = ?, email = ?, provider = 'google', provider_id = ?, profile_picture = ?, force_password_change = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = ['type' => 'database', 'message' => 'Database error occurred. Please try again later.'];
        error_log('SQL prepare error: ' . $conn->error);
        header('Location: signup.php');
        exit;
    }
    $stmt->bind_param("ssssi", $username, $email, $providerId, $profilePicture, $existingUser['id']);
    $stmt->execute();
    $userId = $existingUser['id'];
} else {
    // Insert new user
    $sql = "INSERT INTO users (username, email, provider, provider_id, profile_picture, role, status, force_password_change, created_at) VALUES (?, ?, 'google', ?, ?, 'user', 1, 0, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = ['type' => 'database', 'message' => 'Database error occurred. Please try again later.'];
        error_log('SQL prepare error: ' . $conn->error);
        header('Location: signup.php');
        exit;
    }
    $stmt->bind_param("ssss", $username, $email, $providerId, $profilePicture);
    $stmt->execute();
    $userId = $conn->insert_id;
}

$stmt->close();
$conn->close();

// Set session
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
$_SESSION['role'] = 'user';
$_SESSION['last_activity'] = time();
error_log("Google signup success: username=$username, user_id=$userId");

header('Location: users/dashboard.php');
exit;
ob_end_flush();
?>
