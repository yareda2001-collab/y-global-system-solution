<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_unread_count.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$user_id = (int)$_SESSION['user_id'];

error_log("fetch_unread_count.php: User ID: $user_id");

try {
    $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND seen_by_user = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    error_log("fetch_unread_count.php: Unread count: {$result['unread_count']} for User ID: $user_id");
    echo json_encode(['unread_count' => (int)$result['unread_count']]);
    $stmt->close();
} catch (Exception $e) {
    error_log('fetch_unread_count.php: Error - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>