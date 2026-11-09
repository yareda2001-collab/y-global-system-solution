<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('delete_message.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$user_id = (int)$_SESSION['user_id'];
$message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

error_log("delete_message.php: User ID: $user_id, Message ID: $message_id");

if (!$message_id) {
    error_log('delete_message.php: Missing message ID');
    echo json_encode(['error' => 'Missing message ID']);
    exit;
}

// Verify the message exists and was sent by the user
$sql = "SELECT sender_id FROM messages WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('delete_message.php: Prepare failed: ' . $conn->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $message_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if ($result->num_rows === 0 || $row['sender_id'] !== $user_id) {
    error_log("delete_message.php: Message ID: $message_id not found or sender_id {$row['sender_id']} != user_id $user_id");
    $stmt->close();
    echo json_encode(['error' => 'Message not found or not deletable']);
    exit;
}
$stmt->close();

// Delete the message
$sql = "DELETE FROM messages WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('delete_message.php: Delete prepare failed: ' . $conn->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $message_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    error_log("delete_message.php: Message ID: $message_id deleted by User ID: $user_id");
    echo json_encode(['success' => true]);
} else {
    error_log("delete_message.php: Failed to delete message ID: $message_id");
    echo json_encode(['error' => 'Failed to delete message']);
}

$conn->close();
?>