<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('mark_messages_seen.php: Session user_id not set');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$user_id = (int)$_SESSION['user_id'];
$sender_id = filter_input(INPUT_POST, 'sender_id', FILTER_VALIDATE_INT);

error_log("mark_messages_seen.php: User ID: $user_id, Sender ID: $sender_id");

if (!$sender_id) {
    error_log('mark_messages_seen.php: Invalid sender ID');
    echo json_encode(['success' => false, 'error' => 'Invalid sender ID']);
    exit;
}

try {
    $sql = "UPDATE messages SET seen_by_user = 1 WHERE sender_id = ? AND receiver_id = ? AND seen_by_user = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ii", $sender_id, $user_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    error_log("mark_messages_seen.php: Marked $affected_rows messages as seen for Sender ID: $sender_id to User ID: $user_id");
    echo json_encode(['success' => true, 'affected_rows' => $affected_rows]);
} catch (Exception $e) {
    error_log('mark_messages_seen.php: Error - ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

$conn->close();
?>