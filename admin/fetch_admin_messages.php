<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['user_id'])) {
    error_log('fetch_admin_messages.php: Session invalid');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$admin_id = (int)$_SESSION['user_id'];
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

error_log("fetch_admin_messages.php: Admin ID: $admin_id, User ID: $user_id");

if (!$user_id) {
    error_log('fetch_admin_messages.php: Invalid user ID');
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    $sql = "SELECT id, sender_id, receiver_id, message, is_admin, created_at, is_seen, seen_by_user, is_edited
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $admin_id, $user_id, $user_id, $admin_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    error_log("fetch_admin_messages.php: Fetched " . count($messages) . " messages");
    echo json_encode($messages);
    $stmt->close();
} catch (Exception $e) {
    error_log('fetch_admin_messages.php: Error - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>