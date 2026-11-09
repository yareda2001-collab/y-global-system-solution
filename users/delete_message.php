<?php
session_start();
header('Content-Type: application/json');

error_log("delete_message.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['user_id'])) {
    error_log('delete_message.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

if ($conn->connect_error) {
    error_log('delete_message.php: Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

error_log("delete_message.php: User ID: $user_id, Message ID: $message_id");

if (!$message_id) {
    error_log('delete_message.php: Missing or invalid message_id');
    echo json_encode(['error' => 'Missing message ID']);
    exit;
}

try {
    // Verify the message exists and belongs to the user
    $sql = "SELECT sender_id FROM messages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('delete_message.php: Prepare failed for message check: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log('delete_message.php: Message ID not found: ' . $message_id);
        echo json_encode(['error' => 'Message not found']);
        exit;
    }
    $message = $result->fetch_assoc();
    if ($message['sender_id'] !== $user_id) {
        error_log('delete_message.php: User ID $user_id not authorized to delete message ID: ' . $message_id);
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    $stmt->close();

    // Delete the message
    $sql = "DELETE FROM messages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('delete_message.php: Prepare failed for delete: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("i", $message_id);
    if (!$stmt->execute()) {
        error_log('delete_message.php: Execute failed: ' . $stmt->error);
        echo json_encode(['error' => 'Database execution error']);
        exit;
    }
    $affected_rows = $stmt->affected_rows;
    error_log("delete_message.php: Deleted message ID: $message_id, affected rows: $affected_rows");
    echo json_encode(['success' => true]);
    $stmt->close();
} catch (Exception $e) {
    error_log('delete_message.php: Exception - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
} finally {
    $conn->close();
}
?>