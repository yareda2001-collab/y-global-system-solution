<?php
session_start();
header('Content-Type: application/json');

error_log("get_unread_count.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

if (!$admin_id) {
    echo json_encode(['error' => 'Invalid admin ID: admin_id is zero or unset']);
    exit;
}

// Verify admin_id exists in admin table
$sql = "SELECT username FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Invalid admin ID: not found in admin table']);
    exit;
}
$admin = $result->fetch_assoc();
$admin_username = $admin['username'];
$stmt->close();

// Map admin.id to users.id
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'No matching user found for admin username']);
    exit;
}
$user = $result->fetch_assoc();
$user_id = (int)$user['id'];
$stmt->close();

// Fetch all users except admin
$sql = "SELECT id FROM users WHERE id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_counts = [];
foreach ($users as $user) {
    $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE sender_id = ? AND is_admin = 0 AND is_seen = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $unread_counts[$user['id']] = $result['unread_count'];
    $stmt->close();
}

echo json_encode($unread_counts);
$conn->close();
?>