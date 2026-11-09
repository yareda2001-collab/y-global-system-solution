<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('send_message.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));

error_log("send_message.php: Sender ID: $sender_id, Receiver ID: $receiver_id, Message: $message");

if (!$receiver_id || !$message) {
    error_log('send_message.php: Missing receiver ID or message');
    echo json_encode(['error' => 'Missing receiver ID or message']);
    exit;
}

// Verify receiver exists and is active
$sql = "SELECT id FROM users WHERE id = ? AND status = 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('send_message.php: Prepare failed: ' . $conn->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    error_log("send_message.php: Receiver ID: $receiver_id not found or inactive");
    echo json_encode(['error' => 'Receiver not found']);
    $stmt->close();
    exit;
}
$stmt->close();

// Insert message
$sql = "INSERT INTO messages (sender_id, receiver_id, message, is_admin, seen_by_user, created_at) VALUES (?, ?, ?, 0, 0, NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('send_message.php: Insert prepare failed: ' . $conn->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    error_log("send_message.php: Message sent from $sender_id to $receiver_id");
    echo json_encode(['success' => true]);
} else {
    error_log('send_message.php: Failed to send message');
    echo json_encode(['error' => 'Failed to send message']);
}

$conn->close();
?>