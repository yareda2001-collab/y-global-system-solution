<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_users.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$admin_id = (int)$_SESSION['user_id'];

error_log("fetch_users.php: Admin ID: $admin_id");

try {
    $sql = "SELECT u.id, u.username, u.role,
                   (SELECT COUNT(*) FROM messages m WHERE m.receiver_id = ? AND m.sender_id = u.id AND m.seen_by_user = 0) as unread_count,
                   (SELECT MAX(created_at) FROM messages m WHERE (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id)) as last_message
            FROM users u
            WHERE u.id != ? AND u.status = 1
            ORDER BY last_message DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $admin_id, $admin_id, $admin_id, $admin_id);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    error_log("fetch_users.php: Fetched " . count($users) . " users");
    echo json_encode($users);
    $stmt->close();
} catch (Exception $e) {
    error_log('fetch_users.php: Error - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>